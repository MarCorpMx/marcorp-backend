<?php

namespace App\Services;

use App\Models\OrganizationUser;
use App\Models\BranchUserAccess;
use App\Models\User;

class AuthContextService
{
    public function build(User $user)
    {
        $user->load([
            'staff'
        ]);

        /*
        |----------------------------------------------------------------------
        | 🔥 Obtener accesos por sucursal (fuente única de verdad)
        |----------------------------------------------------------------------
        */
        $branchAccesses = BranchUserAccess::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereHas('organization', fn($q) => $q->where('status', 'active'))
            ->with(['branch', 'role'])
            ->get();

        $featureService = app(FeatureService::class);

        /*
        |----------------------------------------------------------------------
        | 🔥 Sistemas (org + subsystem)
        |----------------------------------------------------------------------
        */
        $systems = OrganizationUser::where('user_id', $user->id)
            ->where('status', 'active')
            ->with([
                'organization.organizationSubsystems.subsystem',
                'organization.organizationSubsystems.plan',
            ])
            ->get()
            ->flatMap(function ($orgUser) use ($branchAccesses, $featureService, $user) {

                //return $orgUser->organization->subsystems->map(function ($orgSubsystem) use ($orgUser, $branchAccesses, $featureService, $user) {
                return $orgUser->organization->organizationSubsystems->map(function ($orgSubsystem) use ($orgUser, $branchAccesses, $featureService, $user) {

                    $plan = $orgSubsystem->plan;

                    /*
                    |----------------------------------------------------------------------
                    | 🔥 Branches del usuario en este org + subsystem
                    |----------------------------------------------------------------------
                    */
                    $branchesCollection = $branchAccesses
                        ->where('organization_id', $orgUser->organization_id)
                        ->where('subsystem_id', $orgSubsystem->subsystem_id);

                    if ($branchesCollection->isEmpty()) {
                        return null;
                    }

                    $branches = $branchesCollection
                        ->sortBy([
                            ['branch.is_primary', 'desc'],
                            ['branch.is_active', 'desc'],
                            ['branch.id', 'asc'],
                        ])
                        ->map(fn($access) => [
                            'branch_id' => $access->branch->id,
                            'branch_name' => $access->branch->name,
                            'branch_primary' => $access->branch->is_primary,
                            'branch_is_active' => $access->branch->is_active,
                            'branch_locked_by_plan' => $access->branch->locked_by_plan,
                            'role' => $access->role?->key,
                            'role_name' => $access->role?->name,
                        ])
                        ->values();

                    /*
                    |----------------------------------------------------------------------
                    | 🔥 Resolver rol global (jerarquía)
                    |----------------------------------------------------------------------
                    */
                    $roleKey = $this->resolveRoleFromBranches($branchesCollection);

                    /*
                    |----------------------------------------------------------------------
                    | 🔥 Default branch inteligente
                    |----------------------------------------------------------------------
                    */
                    $defaultBranch = $branchesCollection
                        ->sortByDesc(fn($a) => $a->branch->is_primary) // prioridad primary
                        ->first();

                    /*
                    |----------------------------------------------------------------------
                    | 🔥 Features (nuevo FeatureService)
                    |----------------------------------------------------------------------
                    */
                    $features = $featureService->getFeaturesFor(
                        $orgSubsystem->subsystem_id,
                        $plan?->id,
                        $orgUser->organization_id,
                        $user->id
                    );

                    return [
                        'organization_id' => $orgUser->organization->id,
                        'organization_name' => $orgUser->organization->name,

                        'subsystem' => [
                            'id' => $orgSubsystem->subsystem->id,
                            'key' => $orgSubsystem->subsystem->key,
                            'name' => $orgSubsystem->subsystem->name,
                        ],

                        'plan' => $plan ? [
                            'id' => $plan->id,
                            'key' => $plan->key,
                            'name' => $plan->name,
                            'price' => $plan->price,
                        ] : null,

                        'plan_key' => $plan?->key,

                        // IMPORTANTE: ahora viene de branch_user_access
                        'role' => $roleKey,

                        'branches' => $branches,

                        'default_branch_id' => $defaultBranch?->branch_id,

                        'features' => $features,
                    ];
                });
            })
            ->filter() // quita nulls
            ->values();

        /*
        |----------------------------------------------------------------------
        | 🔥 Organización actual (puedes mejorar esto luego)
        |----------------------------------------------------------------------
        */
        $organization = $user->organizations()
            ->wherePivot('status', 'active')
            ->first();

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'staff_member_id' => $user->staff?->id,
            ],

            'systems' => $systems,

            'organization' => $organization ? [
                'id' => $organization->id,
                'name' => $organization->name,
                'onboarding_step' => $organization->onboarding_step,
                'onboarding_completed_at' => $organization->onboarding_completed_at,
            ] : null,

            'meta' => [
                'organizations_count' => $systems->pluck('organization_id')->unique()->count(),
                'systems_count' => $systems->count(),
            ]
        ];
    }

    /*
    |----------------------------------------------------------------------
    | 🔥 Resolver rol por jerarquía (igual que FeatureService)
    |----------------------------------------------------------------------
    */
    protected function resolveRoleFromBranches($branchesCollection): ?string
    {
        $roles = $branchesCollection
            ->pluck('role.key')
            ->filter()
            ->unique()
            ->toArray();

        if (empty($roles)) {
            return null;
        }

        $hierarchy = ['root', 'owner', 'admin', 'receptionist', 'staff'];

        foreach ($hierarchy as $role) {
            if (in_array($role, $roles)) {
                return $role;
            }
        }

        return null;
    }
}
