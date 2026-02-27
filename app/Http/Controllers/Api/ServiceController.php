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
    | Store
    |--------------------------------------------------------------------------
    */

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

        $service = DB::transaction(function () use ($validated, $organization) {

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

                    // MULTI-TENANT PROTECTION REAL
                    $validStaffIds = $organization->staffMembers()
                        ->whereIn('id', $variantData['staff_ids'])
                        ->pluck('id')
                        ->toArray();

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
            'variants.*.name' => 'required|string|max:255',
            'variants.*.duration_minutes' => 'required|integer|min:1',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.max_capacity' => 'required|integer|min:1',
            'variants.*.mode' => 'required|in:online,presential,hybrid',
            'variants.*.includes_material' => 'boolean',
            'variants.*.active' => 'boolean',
        ]);

        DB::transaction(function () use ($service, $validated) {

            // 1️⃣ Actualizar datos base del service
            $service->update([
                'name' => $validated['name'] ?? $service->name,
                'description' => $validated['description'] ?? $service->description,
                'active' => $validated['active'] ?? $service->active,
            ]);

            // 2️⃣ Si vienen variantes → reemplazarlas
            if (isset($validated['variants'])) {

                // estrategia simple y consistente
                $service->variants()->delete();

                foreach ($validated['variants'] as $variantData) {
                    $service->variants()->create($variantData);
                }
            }
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
