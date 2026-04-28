<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\StaffMember;
use App\Models\OrganizationUser;
use App\Models\BranchUserAccess;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Services\SubsystemResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

use App\Services\FeatureService;


class TeamController extends Controller
{

    use ResolvesOrganization;

    public function __construct(
        protected SubsystemResolver $subsystemResolver,
        protected FeatureService $featureService,
    ) {}


    public function index(Request $request)
    {
        $search = trim($request->get('search', ''));

        $organization = $this->getOrganization($request);
        $branch = $request->attributes->get('branch');
        $subsystem = $request->attributes->get('subsystem');

        $user = $request->user();

        // 🔥 FEATURE CHECK (plan + rol)
        if (!$this->featureService->can($organization, $user->id, 'citas.team')) {
            return response()->json([
                'message' => 'No tienes acceso a esta funcionalidad'
            ], 403);
        }

        // 🔥 OWNER/ROOT pueden ver toda la organización
        $viewAll = $request->boolean('view_all', false);

        /*
        |--------------------------------------------------------------------------
        | QUERY BASE
        |--------------------------------------------------------------------------
        | IMPORTANTE:
        | NO hacemos join directo a branch_staff para evitar duplicados.
        | Las sucursales se obtienen vía subquery.
        |--------------------------------------------------------------------------
        */

        $query = DB::table('staff_members as sm')

            ->leftJoin('branch_user_access as bua', function ($join) use ($subsystem) {

                $join->on('bua.staff_member_id', '=', 'sm.id')
                    ->where('bua.subsystem_id', '=', $subsystem->id)
                    ->where('bua.is_active', true);
            })

            ->leftJoin('users as u', 'u.id', '=', 'bua.user_id')

            // 🔥 AQUÍ VA
            ->leftJoin('organization_users as ou', function ($join) use ($organization) {
                $join->on('ou.user_id', '=', 'u.id') // 👈 importante usar u.id
                    ->where('ou.organization_id', '=', $organization->id);
            })

            ->leftJoin('roles as r', 'r.id', '=', 'bua.role_id')

            ->where('sm.organization_id', $organization->id);

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($search !== '') {

            $query->where(function ($q) use ($search) {

                $q->where('sm.first_name', 'like', "%{$search}%")

                    ->orWhere('sm.last_name', 'like', "%{$search}%")

                    ->orWhere(
                        DB::raw('CONCAT(sm.first_name, " ", sm.last_name)'),
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere('sm.email', 'like', "%{$search}%")

                    ->orWhere('sm.title', 'like', "%{$search}%")

                    ->orWhere('sm.specialty', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | SOLO SUCURSAL ACTUAL
        |--------------------------------------------------------------------------
        */

        if (!$viewAll) {

            $query->whereExists(function ($q) use ($branch) {

                $q->select(DB::raw(1))
                    ->from('branch_staff as xbs')
                    ->whereColumn('xbs.staff_member_id', 'sm.id')
                    ->where('xbs.branch_id', $branch->id);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | MEMBERS
        |--------------------------------------------------------------------------
        */

        $members = $query
            ->select([

                DB::raw('CONCAT("staff-", sm.id) as id'),

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

                DB::raw('COALESCE(r.key, "noAccess") as role_key'),

                DB::raw('COALESCE(r.name, "Sin acceso") as role_name'),

                //DB::raw('CASE WHEN bua.id IS NOT NULL THEN true ELSE false END as has_access'),
                DB::raw('CASE 
                    WHEN bua.id IS NOT NULL AND bua.is_active = 1 AND ou.status = "active" 
                    THEN true 
                    ELSE false 
                    END as has_access'),

                //DB::raw('"active" as status'),
                DB::raw('CASE 
                    WHEN bua.id IS NULL THEN 
                    CASE 
                        WHEN sm.is_active = 1 THEN "active"
                        ELSE "inactive"
                    END

                    WHEN ou.status = "suspended" THEN "suspended"

                    WHEN bua.is_active = 1 AND ou.status = "active" THEN "active"

                    ELSE "inactive"
                    END as status'),

                DB::raw('true as is_staff'),

                /*
            |--------------------------------------------------------------------------
            | SUCURSALES
            |--------------------------------------------------------------------------
            */

                DB::raw('(
                SELECT GROUP_CONCAT(
                    DISTINCT b.name
                    ORDER BY b.name
                    SEPARATOR ", "
                )
                FROM branch_staff bs2
                INNER JOIN branches b
                    ON b.id = bs2.branch_id
                WHERE bs2.staff_member_id = sm.id
            ) as branch_names')
            ])

            /*
        |--------------------------------------------------------------------------
        | DISTINCT evita duplicados por accesos
        |--------------------------------------------------------------------------
        */

            ->distinct()

            ->orderBy('name')

            ->get();

        /*
        |--------------------------------------------------------------------------
        | NORMALIZACIÓN
        |--------------------------------------------------------------------------
        */

        $members = $members->map(function ($m) {

            return [

                'id' => $m->id,

                'name' => $m->name,

                'first_name' => $m->first_name,
                'last_name' => $m->last_name,

                'email' => $m->email,

                'phone' => $m->phone
                    ? json_decode($m->phone)
                    : null,

                'title' => $m->title,
                'specialty' => $m->specialty,
                'bio' => $m->bio,

                'is_active' => (bool) $m->is_active,
                'is_public' => (bool) $m->is_public,

                'role' => [
                    'key' => $m->role_key,
                    'name' => $m->role_name,
                ],

                'status' => $m->status,

                'is_staff' => (bool) $m->is_staff,

                'has_access' => (bool) $m->has_access,

                /*
                |--------------------------------------------------------------------------
                | Sucursales visibles en frontend
                |--------------------------------------------------------------------------
                */

                'branch_names' => $m->branch_names,
            ];
        });

        return response()->json([
            'data' => $members,

            'meta' => [
                'view_all' => $viewAll
            ]
        ]);
    }


    public function store(Request $request)
    {
        $organization = $this->getOrganization($request);
        $branch = $request->attributes->get('branch');
        $subsystem = $request->attributes->get('subsystem');
        $userAuth = $request->user();

        /*
        |----------------------------------------------------------
        | Validamos permisos de acceso
        |----------------------------------------------------------
        */
        if (!$this->featureService->can($organization, $request->user()->id, 'citas.team')) {
            abort(403, 'No tienes acceso a esta funcionalidad');
        }


        if (!$branch || !$subsystem) {
            return response()->json([
                'message' => 'Contexto inválido (branch/subsystem)'
            ], 400);
        }

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

            // multi-branch (opcional)
            'branches'   => 'nullable|array',
            'branches.*' => 'integer|exists:branches,id',

            'username'   => 'nullable|string|min:4|max:50',
            'password'   => 'nullable|string|min:8|max:15',
        ]);

        DB::beginTransaction();

        try {

            /*
            |------------------------------------------------------------------
            | 1. VALIDAR LÍMITE (ORGANIZACIÓN)
            |------------------------------------------------------------------
            */
            $limit = $this->featureService->limit(
                $organization,
                $userAuth->id,
                'citas.team'
            );

            $currentCount = DB::table('staff_members as sm')

                ->leftJoin('branch_user_access as bua', 'bua.staff_member_id', '=', 'sm.id')
                ->leftJoin('roles as r', 'r.id', '=', 'bua.role_id')

                ->where('sm.organization_id', $organization->id)
                ->where('sm.is_active', true)

                ->where(function ($q) {
                    $q->whereNull('r.key')
                        ->orWhereNotIn('r.key', ['owner', 'root']);
                })

                ->distinct('sm.id')
                ->count('sm.id');

            if ($limit !== null && $currentCount >= $limit) {
                return response()->json([
                    'message' => 'Has alcanzado el límite de personal activo de tu plan. Desactiva un miembro o mejora tu plan.'
                ], 403);
            }

            /*
            |------------------------------------------------------------------
            | 2. VALIDAR DUPLICADO EN ORG
            |------------------------------------------------------------------
            */
            if (!empty($data['email'])) {
                $existsInOrg = StaffMember::where('organization_id', $organization->id)
                    ->where('email', $data['email'])
                    ->exists();

                if ($existsInOrg) {
                    return response()->json([
                        'message' => 'Este usuario ya pertenece a tu equipo'
                    ], 422);
                }
            }

            /*
            |------------------------------------------------------------------
            | 3. USER GLOBAL
            |------------------------------------------------------------------
            */
            $user = null;

            if ($data['has_access']) {

                if (empty($data['email'])) {
                    return response()->json([
                        'message' => 'El email es obligatorio cuando hay acceso'
                    ], 422);
                }

                if (empty($data['username'])) {
                    return response()->json([
                        'message' => 'El username es obligatorio'
                    ], 422);
                }

                //  validar username único global
                $usernameExists = User::where('username', $data['username'])->exists();
                if ($usernameExists) {
                    return response()->json([
                        'message' => 'El nombre de usuario ya está en uso'
                    ], 422);
                }

                //  buscar si ya existe user global
                $user = User::where('email', $data['email'])->first();

                if (!$user) {
                    $user = User::create([
                        'username'   => $data['username'],
                        'name'       => trim($data['first_name'] . ' ' . $data['last_name']),
                        'first_name' => $data['first_name'],
                        'last_name'  => $data['last_name'],
                        'email'      => $data['email'],
                        'password'   => Hash::make($data['password']),
                        'phone'      => $data['phone'],
                        'status'     => 'active',
                    ]);
                }

                // relación con org
                OrganizationUser::firstOrCreate([
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                ]);
            }

            /*
            |------------------------------------------------------------------
            | 4. STAFF MEMBER (UNICO POR ORG)
            |------------------------------------------------------------------
            */
            $staff = StaffMember::create([
                'organization_id' => $organization->id,
                'user_id'    => $user?->id,

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

            /*
            |------------------------------------------------------------------
            | 5. ASIGNAR A SUCURSALES
            |------------------------------------------------------------------
            */
            $branches = $data['branches'] ?? [$branch->id];

            foreach ($branches as $branchId) {

                DB::table('branch_staff')->updateOrInsert(
                    [
                        'branch_id' => $branchId,
                        'staff_member_id' => $staff->id,
                    ],
                    [
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                /*
                |--------------------------------------------------------------
                | 6. ACCESS POR BRANCH
                |--------------------------------------------------------------
                */
                if ($data['has_access']) {

                    DB::table('branch_user_access')->updateOrInsert(
                        [
                            'organization_id' => $organization->id,
                            'user_id' => $user->id,
                            'branch_id' => $branchId,
                            'subsystem_id' => $subsystem->id,
                        ],
                        [
                            'role_id' => $this->getRoleId($data['role']),
                            'staff_member_id' => $staff->id,
                            'is_active' => true,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }

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
        $branch = $request->attributes->get('branch');
        $subsystem = $request->attributes->get('subsystem');

        if (!$branch || !$subsystem) {
            return response()->json([
                'message' => 'Contexto inválido (branch/subsystem)'
            ], 400);
        }

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

            // SOLO si va a dar acceso
            'username'   => 'nullable|string|min:4|max:50',
            'password'   => 'nullable|string|min:6|max:50',
        ]);

        DB::beginTransaction();

        try {

            [$type, $realId] = explode('-', $id);

            $staff = StaffMember::where('organization_id', $organization->id)
                ->findOrFail($realId);

            /*
            |-------------------------------------------------------
            | 1. UPDATE STAFF (NEGOCIO)
            |-------------------------------------------------------
            */
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

            /*
            |-------------------------------------------------------
            | 2. ACCESO AL SISTEMA (branch_user_access)
            |-------------------------------------------------------
            */

            if ($data['has_access']) {

                /*
                |---------------------------------------------
                | A. ASEGURAR USER
                |---------------------------------------------
                */
                if (!$staff->user_id) {

                    // 🔥 VALIDAR CREDENCIALES SOLO SI NO EXISTE USER
                    if (empty($data['email']) || empty($data['password']) || empty($data['username'])) {
                        return response()->json([
                            'message' => 'Username, email y password son obligatorios para dar acceso'
                        ], 422);
                    }

                    // 🔥 SI YA EXISTE USER CON ESE EMAIL → LO USAMOS
                    $user = User::where('email', $data['email'])->first();

                    if (!$user) {
                        $user = User::create([
                            'username'   => $data['username'],
                            'name'       => $staff->name,
                            'first_name' => $data['first_name'],
                            'last_name'  => $data['last_name'],
                            'email'      => $data['email'],
                            'password'   => Hash::make($data['password']),
                            'phone'      => $data['phone'],
                            'status'     => 'active',
                        ]);
                    }

                    // linkear staff → user
                    $staff->update([
                        'user_id' => $user->id
                    ]);
                } else {
                    $user = User::find($staff->user_id);
                }

                /*
                |---------------------------------------------
                | B. RELACIÓN CON ORGANIZACIÓN
                |---------------------------------------------
                */
                OrganizationUser::updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'status' => 'active',
                        'joined_at' => now(),
                    ]
                );

                /*
                |---------------------------------------------
                | C. ACCESS REAL (branch + subsystem)
                |---------------------------------------------
                */
                DB::table('branch_user_access')->updateOrInsert(
                    [
                        'organization_id' => $organization->id,
                        'user_id' => $user->id,
                        'branch_id' => $branch->id,
                        'subsystem_id' => $subsystem->id,
                    ],
                    [
                        'role_id' => $this->getRoleId($data['role']),
                        'staff_member_id' => $staff->id,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            } else {

                /*
                |---------------------------------------------
                | QUITAR ACCESO SOLO EN ESTE CONTEXTO
                |---------------------------------------------
                */
                if ($staff->user_id) {

                    DB::table('branch_user_access')
                        ->where('organization_id', $organization->id)
                        ->where('user_id', $staff->user_id)
                        ->where('branch_id', $branch->id)
                        ->where('subsystem_id', $subsystem->id)
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

    public function toggleAccess(Request $request, $id)
    {
        $organization = $this->getOrganization($request);

        $data = $request->validate([
            'is_active' => 'required|boolean'
        ]);

        [$type, $realId] = explode('-', $id);

        $staff = StaffMember::where('organization_id', $organization->id)
            ->findOrFail($realId);

        /*if (!$staff->user_id) {
            return response()->json([
                'message' => 'Este miembro no tiene acceso al sistema'
            ], 422);
        }*/

        /*
        |-------------------------------------------------------
        | 🔥 NO DESACTIVAR OWNER
        |-------------------------------------------------------
        */
        if (!$data['is_active']) {

            $isOwner = BranchUserAccess::where('user_id', $staff->user_id)
                ->where('organization_id', $organization->id)
                ->whereHas('role', fn($q) => $q->whereIn('key', ['owner', 'root']))
                ->exists();

            if ($isOwner) {
                return response()->json([
                    'message' => 'No puedes desactivar al propietario'
                ], 403);
            }
        }

        /*
        |-------------------------------------------------------
        | 🔥 TOGGLE GLOBAL (SIMPLE Y EFECTIVO)
        |-------------------------------------------------------
        */

        // 🔥 1. organization_users (LOGIN CONTROL)
        DB::table('organization_users')
            ->where('organization_id', $organization->id)
            ->where('user_id', $staff->user_id)
            ->update([
                'status' => $data['is_active'] ? 'active' : 'inactive',
                'updated_at' => now()
            ]);

        // 🔥 2. branch_user_access (ACCESO REAL)
        DB::table('branch_user_access')
            ->where('organization_id', $organization->id)
            ->where('user_id', $staff->user_id)
            ->update([
                'is_active' => $data['is_active'],
                'updated_at' => now()
            ]);

        // 🔥 3. staff_members (UI)
        $staff->update([
            'is_active' => $data['is_active']
        ]);

        return response()->json([
            'message' => $data['is_active']
                ? 'Usuario activado correctamente'
                : 'Usuario desactivado correctamente'
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
