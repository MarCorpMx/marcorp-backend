<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Models\Professional;
use Illuminate\Http\Request;

class ProfessionalController extends Controller
{
    use ResolvesOrganization;

    public function index(Request $request)
    {
        $organization = $this->getOrganization($request);

        return response()->json([
            'data' => Professional::where(
                'organization_id',
                $organization->id
            )->get()
        ]);
    }

    public function store(Request $request)
    {
        $organization = $this->getOrganization($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'is_active' => 'boolean'
        ]);

        $validated['organization_id'] = $organization->id;

        $professional = Professional::create($validated);

        return response()->json([
            'message' => 'Professional created',
            'data' => $professional
        ], 201);
    }

    public function update(Request $request, Professional $professional)
    {
        $this->authorizeProfessional($request, $professional);

        $professional->update($request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'is_active' => 'boolean'
        ]));

        return response()->json([
            'message' => 'Professional updated',
            'data' => $professional
        ]);
    }

    public function destroy(Request $request, Professional $professional)
    {
        $this->authorizeProfessional($request, $professional);

        $professional->delete();

        return response()->json([
            'message' => 'Professional deleted'
        ]);
    }

    private function authorizeProfessional($request, $professional)
    {
        abort_if(
            $professional->organization_id !== $request->user()->organization_id,
            403
        );
    }
}
