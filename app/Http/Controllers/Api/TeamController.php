<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\StaffMember;
use App\Models\OrganizationUser;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Services\SubsystemResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;


class TeamController extends Controller
{

    use ResolvesOrganization;

    public function __construct(
        protected SubsystemResolver $subsystemResolver
    ) {}

    public function index(Request $request)
    {
        $organization = $this->getOrganization($request);

        /*
        |--------------------------------------------------------------------------
        | 1. USERS (con acceso al sistema)
        |--------------------------------------------------------------------------
        */
        $users = DB::table('organization_users as ou')
            ->join('users as u', 'u.id', '=', 'ou.user_id')

            ->leftJoin('user_subsystem_roles as usr', function ($join) use ($organization) {
                $join->on('usr.user_id', '=', 'u.id')
                    ->where('usr.organization_id', '=', $organization->id);
            })

            ->leftJoin('roles as r', 'r.id', '=', 'usr.role_id')

            ->leftJoin('staff_members as sm', function ($join) use ($organization) {
                $join->on('sm.user_id', '=', 'u.id')
                    ->where('sm.organization_id', '=', $organization->id);
            })

            ->where('ou.organization_id', $organization->id)

            ->select([
                DB::raw('CONCAT("user-", u.id) as id'),

                // NOMBRE PRIORIDAD: staff → user → fallback
                DB::raw('COALESCE(NULLIF(CONCAT(sm.first_name, " ", sm.last_name), " "), u.name, "Sin nombre") as name'),

                DB::raw('COALESCE(sm.first_name, u.first_name) as first_name'),
                DB::raw('COALESCE(sm.last_name, u.last_name) as last_name'),

                // Email prioridad staff → user
                DB::raw('COALESCE(sm.email, u.email) as email'),

                'sm.phone',
                'sm.title',
                'sm.specialty',
                'sm.bio',
                DB::raw('COALESCE(sm.is_active, true) as is_active'),
                DB::raw('COALESCE(sm.is_public, true) as is_public'),

                'ou.status',

                DB::raw('COALESCE(r.key, "no-role") as role_key'),
                DB::raw('COALESCE(r.name, "Sin rol") as role_name'),

                DB::raw('CASE WHEN ou.status = "active" THEN true ELSE false END as has_access'),
                DB::raw('CASE WHEN sm.id IS NOT NULL THEN true ELSE false END as is_staff'),
            ])

            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2. STAFF SIN USUARIO
        |--------------------------------------------------------------------------
        */
        $staffOnly = DB::table('staff_members as sm')
            ->where('sm.organization_id', $organization->id)
            ->whereNull('sm.user_id')

            ->select([
                DB::raw('CONCAT("staff-", sm.id) as id'),

                // nombre  desde staff
                DB::raw('COALESCE(
                    NULLIF(TRIM(CONCAT(
                        COALESCE(sm.first_name, ""),
                        " ",
                        COALESCE(sm.last_name, "")
                    )), ""),
                    sm.name,
                    "Sin nombre"
                ) as name'),

                'sm.first_name',
                'sm.last_name',
                'sm.email',
                'sm.phone',
                'sm.title',
                'sm.specialty',
                'sm.bio',
                'sm.is_active',
                'sm.is_public',

                DB::raw('"active" as status'),
                DB::raw('"staff" as role_key'),
                DB::raw('COALESCE(sm.title, "Profesional") as role_name'),

                DB::raw('false as has_access'),
                DB::raw('true as is_staff'),
            ])

            ->get();

        /*
        |--------------------------------------------------------------------------
        | 3. UNIÓN
        |--------------------------------------------------------------------------
        */
        $members = $users
            ->concat($staffOnly)
            ->sortBy('name')
            ->values()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'first_name' => $member->first_name,
                    'last_name' => $member->last_name,
                    'phone' => $member->phone ? json_decode($member->phone) : null,
                    'title' => $member->title,
                    'specialty' => $member->specialty,
                    'bio' => $member->bio,
                    'is_active' => (bool) $member->is_active,
                    'is_public' => (bool) $member->is_public,
                    'email' => $member->email,
                    'role' => [
                        'key' => $member->role_key,
                        'name' => $member->role_name,
                    ],
                    'status' => $this->mapStatus($member->status),
                    'is_staff' => (bool) $member->is_staff,
                    'has_access' => (bool) $member->has_access,
                ];
            });

        return response()->json([
            'data' => $members
        ]);
    }



    public function store(Request $request)
    {

        $organization = $this->getOrganization($request);

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'email'      => 'nullable|email|max:150',
            'phone'      => 'nullable',
            'title'      => 'nullable|string|max:150',
            'specialty'  => 'nullable|string|max:150',
            'bio'        => 'nullable|string',

            'is_active'  => 'boolean',
            'is_public'  => 'boolean',

            'has_access' => 'boolean',
            'role'       => 'required|string',

            'subsystem'  => 'required|string',

            // solo si tiene acceso
            'username'   => 'nullable|string|max:50',
            'password'   => 'nullable|string|min:6',
        ]);

        DB::beginTransaction();

        $subsystemCode = $data['subsystem'];
        $subsystemId = $this->subsystemResolver->resolve($subsystemCode);
        if ($subsystemCode && !$subsystemId) {
            throw new \Exception("Subsystem '{$subsystemCode}' not found or inactive.");
        }

        //Log::info('El id de las citas es el: ' . $subsystemId);

        try {

            $user = null;

            // SI TIENE ACCESO → CREAR USER
            if ($data['has_access']) {

                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'username'      => $data['username'],
                        'name'          => $data['first_name'] . ' ' . $data['last_name'],
                        'first_name'    => $data['first_name'],
                        'last_name'     => $data['last_name'],
                        //'email'         => $data['email'],
                        'password'      => Hash::make($data['password']),
                        'phone' => $data['phone'],
                        'status'        => 'active',
                    ]
                );

                OrganizationUser::firstOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'user_id'         => $user->id,
                    ],
                    [
                        'status'    => 'active',
                        'joined_at' => now(),
                    ]
                );

                // ASIGNAR ROL
                DB::table('user_subsystem_roles')->insert([
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                    'subsystem_id' => $subsystemId,
                    'role_id' => $this->getRoleId($data['role']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $exists = StaffMember::where('organization_id', $organization->id)
                ->where('email', $data['email'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Este usuario ya pertenece a tu equipo'
                ], 422);
            }

            // CREAR STAFF MEMBER
            //$staff = StaffMember::create([
            $staff = StaffMember::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'email' => $data['email']
                ],
                [
                    //'organization_id' => $organization->id,
                    'user_id' => $user?->id,

                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'name' => trim($data['first_name'] . ' ' . $data['last_name']),

                    //'email' => $data['email'],
                    'phone' => $data['phone'],

                    'title' => $data['title'],
                    'specialty' => $data['specialty'],
                    'bio' => $data['bio'],

                    'is_active' => $data['is_active'] ?? true,
                    'is_public' => $data['is_public'] ?? true,
                ]
            );

            DB::commit();

            return response()->json([
                'message' => 'Miembro creado correctamente',
                'data' => $staff
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al crear miembro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $organization = $this->getOrganization($request);

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'email'      => 'nullable|email|max:150',
            'phone'      => 'nullable',
            'title'      => 'nullable|string|max:150',
            'specialty'  => 'nullable|string|max:150',
            'bio'        => 'nullable|string',

            'is_active'  => 'boolean',
            'is_public'  => 'boolean',

            'has_access' => 'required|boolean',
            'role'       => 'required|string',
            'subsystem'  => 'required|string',
        ]);

        DB::beginTransaction();

        try {

            [$type, $realId] = explode('-', $id);

            if ($type === 'staff') {
                $staff = StaffMember::where('organization_id', $organization->id)
                    ->findOrFail($realId);
            } else {
                $staff = StaffMember::where('organization_id', $organization->id)
                    ->where('user_id', $realId)
                    ->firstOrFail();
            }

            // UPDATE STAFF (SIEMPRE)
            $staff->update([
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'name'       => trim($data['first_name'] . ' ' . $data['last_name']),
                'email'      => $data['email'],
                'phone'      => $data['phone'],
                'title'      => $data['title'],
                'specialty'  => $data['specialty'],
                'bio'        => $data['bio'],
                'is_active'  => $data['is_active'] ?? true,
                'is_public'  => $data['is_public'] ?? true,
            ]);

            // SI TIENE USER → manejar acceso
            if ($staff->user_id) {

                $subsystemId = $this->subsystemResolver->resolve($data['subsystem']);

                if ($data['has_access']) {

                    // ACTIVAR ACCESO
                    OrganizationUser::updateOrCreate(
                        [
                            'organization_id' => $organization->id,
                            'user_id' => $staff->user_id,
                        ],
                        [
                            'status' => 'active',
                            'joined_at' => now(),
                        ]
                    );

                    // CREAR / ACTUALIZAR ROL
                    DB::table('user_subsystem_roles')->updateOrInsert(
                        [
                            'organization_id' => $organization->id,
                            'user_id' => $staff->user_id,
                            'subsystem_id' => $subsystemId,
                        ],
                        [
                            'role_id' => $this->getRoleId($data['role']),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                } else {

                    // DESACTIVAR ACCESO (NO BORRAR USER)
                    OrganizationUser::where([
                        'organization_id' => $organization->id,
                        'user_id' => $staff->user_id,
                    ])->update([
                        'status' => 'inactive'
                    ]);

                    // OPCIONAL (recomendado): quitar roles en ESTA ORG
                    DB::table('user_subsystem_roles')
                        ->where('organization_id', $organization->id)
                        ->where('user_id', $staff->user_id)
                        ->delete();
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Miembro actualizado correctamente'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al actualizar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function suspend(Request $request, $id)
    {
        $organization = $this->getOrganization($request);

        [$type, $realId] = explode('-', $id);

        $userId = null;

        if ($type === 'user') {
            $userId = $realId;
        } else {
            $staff = StaffMember::where('organization_id', $organization->id)
                ->findOrFail($realId);

            $userId = $staff->user_id;
        }

        // 🔥 VALIDAR ROLE SI HAY USER
        if ($userId) {

            $subsystemId = $this->subsystemResolver->resolve('citas');

            $role = DB::table('user_subsystem_roles as usr')
                ->join('roles as r', 'r.id', '=', 'usr.role_id')
                ->where('usr.organization_id', $organization->id)
                ->where('usr.user_id', $userId)
                ->where('usr.subsystem_id', $subsystemId)
                ->value('r.key');

            if ($role === 'owner') {
                return response()->json([
                    'message' => 'No puedes modificar al propietario'
                ], 403);
            }

            // 🔥 SUSPENDER
            DB::table('organization_users')
                ->where('organization_id', $organization->id)
                ->where('user_id', $userId)
                ->update([
                    'status' => 'suspended',
                    'updated_at' => now()
                ]);
        }

        return response()->json([
            'message' => 'Miembro suspendido correctamente'
        ]);
    }

    public function activate(Request $request, $id)
    {
        $organization = $this->getOrganization($request);

        [$type, $realId] = explode('-', $id);

        $userId = null;

        if ($type === 'user') {
            $userId = $realId;
        } else {
            $staff = StaffMember::where('organization_id', $organization->id)
                ->findOrFail($realId);

            $userId = $staff->user_id;
        }

        if ($userId) {
            DB::table('organization_users')
                ->where('organization_id', $organization->id)
                ->where('user_id', $userId)
                ->update([
                    'status' => 'active',
                    'updated_at' => now()
                ]);
        }

        return response()->json([
            'message' => 'Miembro activado correctamente'
        ]);
    }

    private function getRoleId(string $key)
    {
        return \App\Models\Role::where('key', $key)->value('id');
    }

    private function mapRole($role)
    {
        return match ($role) {
            'owner', 'admin' => 'Administrador',
            'therapist' => 'Terapeuta',
            default => 'Recepción',
        };
    }

    private function mapStatus($status)
    {
        return match ($status) {
            'active' => 'activo',
            'invited' => 'invitado',
            'suspended' => 'suspendido',
            default => 'invitado',
        };
    }
}
