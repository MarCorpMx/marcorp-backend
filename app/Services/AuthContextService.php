<?php

namespace App\Services;

use App\Models\OrganizationUser;
use App\Models\BranchUserAccess;
use App\Models\User;

class AuthContextService
{
    public function build(User $user)
    {
        //$user->load('subsystemRoles.role');
        $user->load([
            'subsystemRoles.role',
            'staff'
        ]);

        $userRoles = $user->subsystemRoles
            ->keyBy(fn($r) => "{$r->organization_id}_{$r->subsystem_id}");

        $branchAccesses = BranchUserAccess::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereHas('organization', fn($q) => $q->where('status', 'active'))
            ->with(['branch', 'role'])
            ->get();

        $featureService = app(FeatureService::class);

        $systems = OrganizationUser::where('user_id', $user->id)
            ->where('status', 'active')
            ->with([
                'organization.subsystems.subsystem',
                'organization.subsystems.plan',
            ])
            ->get()
            ->flatMap(function ($orgUser) use ($branchAccesses, $userRoles, $featureService) {

                return $orgUser->organization->subsystems->map(function ($orgSubsystem) use ($orgUser, $branchAccesses, $userRoles, $featureService) {

                    $plan = $orgSubsystem->plan;

                    $role = $userRoles["{$orgUser->organization_id}_{$orgSubsystem->subsystem_id}"]?->role ?? null;

                    $branches = $branchAccesses
                        ->where('organization_id', $orgUser->organization_id)
                        ->where('subsystem_id', $orgSubsystem->subsystem_id)
                        ->map(fn($access) => [
                            'branch_id' => $access->branch->id,
                            'branch_name' => $access->branch->name,
                            'role' => $access->role?->key,
                            'role_name' => $access->role?->name,
                        ])
                        ->values();

                    $features = $featureService->getFeaturesFor(
                        $orgSubsystem->subsystem_id,
                        $plan?->id,
                        $role?->key
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

                        'role' => $role?->key,

                        'branches' => $branches,
                        'default_branch_id' => $branches->first()['branch_id'] ?? null,

                        'features' => $features,
                    ];
                });
            })
            ->filter(fn($s) => count($s['branches']) > 0)
            ->values();

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
}
