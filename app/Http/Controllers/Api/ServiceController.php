<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function getOrganization(Request $request)
    {
        $organization = $request->user()->currentOrganization();

        if (!$organization) {
            abort(403, 'No organization context.');
        }

        return $organization;
    }

    private function authorizeService(Request $request, Service $service)
    {
        $organization = $this->getOrganization($request);

        if ($service->organization_id !== $organization->id) {
            abort(403, 'Unauthorized.');
        }

        return $organization;
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $organization = $this->getOrganization($request);

        $services = $organization->services()
            ->with(['variants.staff'])
            ->latest()
            ->get();

        return response()->json($services);
    }

    /*
    |--------------------------------------------------------------------------
    | myServices
    |--------------------------------------------------------------------------
    */
    public function myServices(Request $request)
    {
        $user = $request->user();
        $organization = $this->getOrganization($request);

        return $organization->services()
            ->whereHas('variants.staff', function ($q) use ($user) {
                $q->where('staff_id', $user->staff->id);
            })
            ->with('variants')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | List
    |--------------------------------------------------------------------------
    */
    public function list(Request $request)
    {
        $organization = $this->getOrganization($request);
        //$user = $request->user();

        $servicesQuery = $organization->services()
            ->where('active', true);

        // Si NO es admin
        /*if ($user->role !== 'admin') {

            $servicesQuery->whereHas('variants.staff', function ($q) use ($user) {
                $q->where('staff_id', $user->staff->id);
            });
        }*/

        return $servicesQuery
            ->orderBy('name')
            ->limit(100)
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                ];
            })
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | List Variants
    |--------------------------------------------------------------------------
    */
    public function listVariants(Request $request)
    {
        $organization = $this->getOrganization($request);
        //$user = $request->user();

        $variantsQuery = \App\Models\ServiceVariant::query()
            ->whereHas('service', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id)
                    ->where('active', true);
            })
            ->where('active', true);

        // Si no es admin → solo variantes donde él puede dar el servicio
        /*if ($user->role !== 'admin') {

            $variantsQuery->whereHas('staff', function ($q) use ($user) {
                $q->where('staff_member_id', $user->staff->id);
            });
        }*/

        return $variantsQuery
            ->with('service:id,name')
            ->orderBy('name')
            ->limit(100)
            ->get()
            ->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'label' => $variant->service->name . ' - ' . $variant->name,
                    'duration' => $variant->duration_minutes,
                    'price' => $variant->price,
                ];
            })
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Staff
    |--------------------------------------------------------------------------
    */
    public function staff(Request $request, $variantId)
    {
        $organization = $this->getOrganization($request);

        $variant = \App\Models\ServiceVariant::query()
            ->whereHas('service', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            })
            ->with('staff:id,name')
            ->findOrFail($variantId);

        return $variant->staff->map(function ($staff) {
            return [
                'id' => $staff->id,
                'name' => $staff->name,
            ];
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    /* rombi -> mejora
hacer que cuando un admin cree un servicio se asigne automáticamente a TODO el staff activo (como hacen plataformas tipo agenda profesional).
Eso evita un problema común en sistemas de reservas.*/

    public function store(Request $request)
    {
        $organization = $this->getOrganization($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',

            'variants' => 'required|array|min:1',

            'variants.*.name' => 'required|string|max:255',
            'variants.*.duration_minutes' => 'required|integer|min:1',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.max_capacity' => 'required|integer|min:1',
            'variants.*.mode' => 'required|in:online,presential,hybrid',
            'variants.*.includes_material' => 'boolean',
            'variants.*.active' => 'boolean',
            'variants.*.staff_ids' => 'array',
            'variants.*.staff_ids.*' => 'integer|exists:staff_members,id',
        ]);

        $user = $request->user();
        $staffId = $organization->staffMembers()
            ->where('user_id', $user->id)
            ->value('id');

        $service = DB::transaction(function () use ($validated, $organization, $staffId) {

            $service = $organization->services()->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'active' => $validated['active'] ?? true,
            ]);

            foreach ($validated['variants'] as $variantData) {

                $variant = $service->variants()->create([
                    'name' => $variantData['name'],
                    'duration_minutes' => $variantData['duration_minutes'],
                    'price' => $variantData['price'] ?? null,
                    'max_capacity' => $variantData['max_capacity'],
                    'mode' => $variantData['mode'],
                    'includes_material' => $variantData['includes_material'] ?? false,
                    'active' => $variantData['active'] ?? true,
                ]);

                if (!empty($variantData['staff_ids'])) {

                    $validStaffIds = $organization->staffMembers()
                        ->whereIn('id', $variantData['staff_ids'])
                        ->pluck('id')
                        ->toArray();
                } else {

                    $validStaffIds = $staffId ? [$staffId] : [];
                }

                if (!empty($validStaffIds)) {
                    $variant->staff()->sync($validStaffIds);
                }
            }

            return $service;
        });

        return response()->json(
            $service->load(['variants.staff']),
            201
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(Request $request, Service $service)
    {
        $this->authorizeService($request, $service);

        return response()->json(
            $service->load(['variants.staff'])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, Service $service)
    {
        $organization = $this->authorizeService($request, $service);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',

            'variants' => 'sometimes|array|min:1',

            'variants.*.id' => 'nullable|integer|exists:service_variants,id',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.duration_minutes' => 'required|integer|min:1',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.max_capacity' => 'required|integer|min:1',
            'variants.*.mode' => 'required|in:online,presential,hybrid',
            'variants.*.includes_material' => 'boolean',
            'variants.*.active' => 'boolean',

            'variants.*.staff_ids' => 'array',
            'variants.*.staff_ids.*' => 'integer|exists:staff_members,id',
        ]);

        DB::transaction(function () use ($service, $validated, $organization) {

            /*
        |---------------------------------------------------------
        | 1️⃣ actualizar datos base del servicio
        |---------------------------------------------------------
        */

            $service->update([
                'name' => $validated['name'] ?? $service->name,
                'description' => $validated['description'] ?? $service->description,
                'active' => $validated['active'] ?? $service->active,
            ]);

            if (!isset($validated['variants'])) {
                return;
            }

            /*
        |---------------------------------------------------------
        | 2️⃣ obtener variantes actuales
        |---------------------------------------------------------
        */

            $existingVariants = $service->variants()->get()->keyBy('id');

            $receivedIds = [];

            foreach ($validated['variants'] as $variantData) {

                /*
            |---------------------------------------------------------
            | 3️⃣ UPDATE variante existente
            |---------------------------------------------------------
            */

                if (!empty($variantData['id']) && $existingVariants->has($variantData['id'])) {

                    $variant = $existingVariants[$variantData['id']];

                    $variant->update([
                        'name' => $variantData['name'],
                        'duration_minutes' => $variantData['duration_minutes'],
                        'price' => $variantData['price'] ?? null,
                        'max_capacity' => $variantData['max_capacity'],
                        'mode' => $variantData['mode'],
                        'includes_material' => $variantData['includes_material'] ?? false,
                        'active' => $variantData['active'] ?? true,
                    ]);

                    $receivedIds[] = $variant->id;
                } else {

                    /*
                |---------------------------------------------------------
                | 4️⃣ CREATE nueva variante
                |---------------------------------------------------------
                */

                    $variant = $service->variants()->create([
                        'name' => $variantData['name'],
                        'duration_minutes' => $variantData['duration_minutes'],
                        'price' => $variantData['price'] ?? null,
                        'max_capacity' => $variantData['max_capacity'],
                        'mode' => $variantData['mode'],
                        'includes_material' => $variantData['includes_material'] ?? false,
                        'active' => $variantData['active'] ?? true,
                    ]);

                    $receivedIds[] = $variant->id;
                }

                /*
            |---------------------------------------------------------
            | 5️⃣ sincronizar staff
            |---------------------------------------------------------
            */

                if (isset($variantData['staff_ids'])) {

                    $validStaffIds = $organization->staffMembers()
                        ->whereIn('id', $variantData['staff_ids'])
                        ->pluck('id')
                        ->toArray();

                    $variant->staff()->sync($validStaffIds);
                }
            }

            /*
        |---------------------------------------------------------
        | 6️⃣ DESACTIVAR variantes eliminadas (no borrar)
        |---------------------------------------------------------
        */

            $service->variants()
                ->whereNotIn('id', $receivedIds)
                ->update([
                    'active' => false
                ]);
        });

        return response()->json(
            $service->load(['variants.staff'])
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(Request $request, Service $service)
    {
        $this->authorizeService($request, $service);

        $service->delete();

        return response()->json([
            'message' => 'Service deleted successfully.'
        ]);
    }
}
