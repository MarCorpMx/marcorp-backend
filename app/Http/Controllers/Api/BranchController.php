<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Services\FeatureService;

use App\Models\Branch;
use App\Models\BranchUserAccess;
use App\Models\StaffMember;
use App\Models\BranchStaff;
use App\Models\Role;
use App\Models\Subsystem;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BranchController extends Controller
{

    use ResolvesOrganization;

    public function __construct(
        protected FeatureService $featureService
    ) {}

    /**
     * GET /me/branches
     * Listar sucursales de la organización
     */
    public function index(Request $request)
    {
        $org = $this->getOrganization($request);

        //dd($this->featureService->get($org, $request->user()->id, 'citas', 'branches'));

        if (!$this->featureService->can($org, $request->user()->id, 'citas.branches')) {
            abort(403, 'No tienes acceso a sucursales');
        }

        $branches = Branch::where('organization_id', $org->id)
            ->orderByDesc('is_primary')   // primero primary
            ->orderByDesc('is_active')    // luego activas
            ->orderBy('id')               // orden natural
            ->get();

        return response()->json([
            'data' => $branches->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'slug' => $branch->slug,
                    'reference_prefix' => $branch->reference_prefix,

                    'address' => $branch->address,
                    'city' => $branch->city,
                    'state' => $branch->state,
                    'country' => $branch->country,
                    'zip_code' => $branch->zip_code,

                    'phone' => $branch->phone,
                    'email' => $branch->email,
                    'website' => $branch->website,

                    'is_active' => (bool) $branch->is_active,
                    'is_primary' => (bool) $branch->is_primary,
                    'locked_by_plan' => (bool) $branch->locked_by_plan,

                    'timezone' => $branch->timezone,

                    'manager' => null, // futuro (relación)
                ];
            }),
            'meta' => [
                'organization_branches_count' => Branch::where('organization_id', $org->id)->count()
            ],
        ]);
    }

    /**
     * POST /me/branches
     * Crear nueva sucursal
     */
    public function store(Request $request)
    {
        $org = $this->getOrganization($request);
        $user = $request->user();

        /*
        |----------------------------------------------------------
        | Validamos permisos de acceso
        |----------------------------------------------------------
        */
        if (!$this->featureService->can($org, $request->user()->id, 'citas.branches')) {
            abort(403, 'No tienes acceso a sucursales');
        }

        /*
        |----------------------------------------------------------
        | Validaciones generales
        |----------------------------------------------------------
        */
        $data = $request->validate(
            [
                'name' => 'required|string|max:120',

                'slug' => [
                    'required',
                    'string',
                    'max:120',
                    'alpha_dash',
                    'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                    'unique:branches,slug,NULL,id,organization_id,' . $org->id
                ],
                'reference_prefix' => [
                    'required',
                    'string',
                    'min:2',
                    'max:5',
                    'alpha_dash',
                    'regex:/^(?=.*[A-Z])[A-Z0-9]+$/',
                ],

                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:120',
                'state' => 'nullable|string|max:120',
                'country' => 'nullable|string|max:120',
                'zip_code' => 'nullable|string|max:20',

                'phone' => 'nullable|array',
                'email' => 'nullable|email|max:120',
                'website' => 'nullable|url|max:255',

                'timezone' => 'nullable|string|max:60',

                'is_primary' => 'nullable|boolean',
            ],
            [
                'slug.required' => 'El enlace es obligatorio.',
                'slug.min' => 'El enlace debe tener al menos 3 caracteres.',
                'slug.max' => 'El enlace no puede superar los 120 caracteres.',
                'slug.regex' => 'Solo minúsculas, números y guiones (ej: punto-de-calma).',
                'slug.unique' => 'El enlace ya está siendo usado. Prueba con otro.',
                'slug.alpha_dash' => 'Solo letras, números y guiones.',

                'reference_prefix.required' => 'El prefijo es obligatorio.',
                'reference_prefix.min' => 'Mínimo 2 caracteres.',
                'reference_prefix.max' => 'Máximo 5 caracteres.',
                'reference_prefix.regex' => 'Solo letras mayúsculas y números (ej: PDC).',
                'reference_prefix.alpha_dash' => 'Solo letras, números y guiones.',
            ]
        );

        /*
        |----------------------------------------------------------
        | Validamos permisos de creación por rol
        |----------------------------------------------------------
        */
        $role = BranchUserAccess::where('user_id', $user->id)
            ->where('organization_id', $org->id)
            ->where('is_active', true)
            ->with('role')
            ->get()
            ->pluck('role.key')
            ->unique()
            ->toArray();

        $allowedRoles = ['root', 'owner'];

        if (!collect($role)->intersect($allowedRoles)->isNotEmpty()) {
            abort(403, 'No tienes permisos para crear sucursales');
        }

        /*
        |----------------------------------------------------------
        | Límite de creación de sucursales por plan
        |----------------------------------------------------------
        */
        $limit = $this->featureService->limit(
            $org,
            $user->id,
            'citas.branches'
        );

        $currentCount = Branch::where('organization_id', $org->id)->count();

        if (!is_null($limit) && $currentCount >= $limit) {
            return response()->json([
                'message' => 'Has alcanzado el límite de sucursales de tu plan'
            ], 422);
        }

        /*
        |----------------------------------------------------------
        | Creamos sucursal
        |----------------------------------------------------------
        */

        return DB::transaction(function () use ($data, $org, $user) {

            // 1. Staff global
            $staff = StaffMember::firstOrCreate(
                [
                    'organization_id' => $org->id,
                    'user_id' => $user->id,
                ],
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => true,
                ]
            );

            // 2. Crear sucursal
            $branch = Branch::create([
                ...$data,
                'organization_id' => $org->id,
                'is_active' => true,
                'timezone' => $data['timezone'] ?? $org->timezone,
            ]);

            // Sucursal primaria única
            if ($branch->is_primary) {
                Branch::where('organization_id', $org->id)
                    ->where('id', '!=', $branch->id)
                    ->update(['is_primary' => false]);
            }

            // 3. Relación staff - sucursal
            BranchStaff::firstOrCreate([
                'branch_id' => $branch->id,
                'staff_member_id' => $staff->id,
            ]);

            // 4. Acceso con staff_member_id
            BranchUserAccess::updateOrCreate(
                [
                    'organization_id' => $org->id,
                    'user_id' => $user->id,
                    'branch_id' => $branch->id,
                    'subsystem_id' => Subsystem::where('key', 'citas')->first()->id,
                ],
                [
                    'role_id' => Role::where('key', 'owner')->first()->id,
                    'staff_member_id' => $staff->id,
                    'is_active' => true,
                ]
            );

            return response()->json([
                'data' => $branch
            ], 201);
        });
    }

    /**
     * GET /me/branches/{branch}
     * Obtener detalle de una sucursal
     */
    public function show($branch)
    {
        return response()->json([
            'message' => 'Show branch - not implemented yet'
        ]);
    }

    /**
     * PUT /me/branches/{branch}
     * Actualizar sucursal
     */
    public function update(Request $request, Branch $branch)
    {

        $org = $this->getOrganization($request);
        $user = $request->user();

        /*
        |----------------------------------------------------------
        | Validamos permisos de acceso
        |----------------------------------------------------------
        */
        if ($branch->organization_id !== $org->id) {
            abort(404);
        }

        if (!$this->featureService->can($org, $request->user()->id, 'citas.branches')) {
            abort(403, 'No tienes acceso a sucursales');
        }

        // Detactamos el tipo de operación ($isToggle = activar/desactivar)
        $isToggle = $request->has('is_active') && count($request->all()) === 1;

        if ($request->has('is_active') && count($request->all()) > 1) {
            return response()->json([
                'message' => 'Operación inválida'
            ], 422);
        }

        if ($isToggle) {

            if ($request->boolean('is_active')) {

                $limit = $this->featureService->limit($org, $user->id, 'citas.branches');

                $activeCount = Branch::where('organization_id', $org->id)
                    ->where('is_active', true)
                    ->count();

                // BLOQUEADA POR PLAN
                if ($branch->locked_by_plan) {
                    return response()->json([
                        'message' => 'Esta sucursal está bloqueada por tu plan actual',
                        'meta' => [
                            'reason' => 'locked_by_plan'
                        ]
                    ], 403);
                }



                if (!is_null($limit)) {

                    if (!$branch->is_active && $activeCount >= $limit) {
                        return response()->json([
                            'message' => 'Has alcanzado el límite de sucursales activas de tu plan',
                            'meta' => [
                                'limit' => $limit,
                                'active' => $activeCount
                            ]
                        ], 422);
                    }
                }
            }

            // No permitir desactivar primaria
            if ($branch->is_primary && !$request->boolean('is_active')) {
                return response()->json([
                    'message' => 'No puedes desactivar la sucursal principal'
                ], 422);
            }

            $branch->update([
                'is_active' => $request->boolean('is_active')
            ]);

            return response()->json([
                'data' => ['is_active' => (bool) $branch->is_active]
            ]);
        }

        if (!$branch->is_active) {
            return response()->json([
                'message' => 'Esta sucursal está desactivada por tu plan actual'
            ], 403);
        }

        $data = $request->validate(
            [
                'name' => 'required|string|max:120',

                'slug' => [
                    'required',
                    'string',
                    'max:120',
                    'alpha_dash',
                    'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                    'unique:branches,slug,' . $branch->id . ',id,organization_id,' . $org->id
                ],
                'reference_prefix' => [
                    'required',
                    'string',
                    'min:2',
                    'max:5',
                    'alpha_dash',
                    'regex:/^(?=.*[A-Z])[A-Z0-9]+$/',
                ],

                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:120',
                'state' => 'nullable|string|max:120',
                'country' => 'nullable|string|max:120',
                'zip_code' => 'nullable|string|max:20',

                'phone' => 'nullable|array',
                'email' => 'nullable|email|max:120',
                'website' => 'nullable|url|max:255',

                'timezone' => 'nullable|string|max:60',

            ],
            [
                'slug.required' => 'El enlace es obligatorio.',
                'slug.min' => 'El enlace debe tener al menos 3 caracteres.',
                'slug.max' => 'El enlace no puede superar los 120 caracteres.',
                'slug.regex' => 'Solo minúsculas, números y guiones (ej: punto-de-calma).',
                'slug.unique' => 'El enlace ya está siendo usado. Prueba con otro.',
                'slug.alpha_dash' => 'Solo letras, números y guiones.',

                'reference_prefix.required' => 'El prefijo es obligatorio.',
                'reference_prefix.min' => 'Mínimo 2 caracteres.',
                'reference_prefix.max' => 'Máximo 5 caracteres.',
                'reference_prefix.regex' => 'Solo letras mayúsculas y números (ej: PDC).',
                'reference_prefix.alpha_dash' => 'Solo letras, números y guiones.',
            ]
        );

        // Bloquear campo críticos
        unset(
            //$data['slug'],
            //$data['reference_prefix'],
            $data['organization_id'],
            $data['is_primary'],
            $data['locked_by_plan']
        );


        $branch->update($data);

        return response()->json([
            'data' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
                'reference_prefix' => $branch->reference_prefix,

                'address' => $branch->address,
                'city' => $branch->city,
                'state' => $branch->state,
                'country' => $branch->country,
                'zip_code' => $branch->zip_code,

                'phone' => $branch->phone,
                'email' => $branch->email,
                'website' => $branch->website,

                'is_active' => (bool) $branch->is_active,
                'is_primary' => (bool) $branch->is_primary,
                'locked_by_plan' => (bool) $branch->locked_by_plan,

                'timezone' => $branch->timezone,
            ]
        ]);
    }

    /**
     * DELETE /me/branches/{branch}
     * Eliminar sucursal (soft delete en futuro)
     */
    public function destroy($branch)
    {
        return response()->json([
            'message' => 'Delete branch - not implemented yet'
        ]);
    }
}
