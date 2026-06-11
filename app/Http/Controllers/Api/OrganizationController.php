<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\BranchUserAccess;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Services\FeatureService;

// Para imágenes
use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;


class OrganizationController extends Controller
{
    use ResolvesOrganization;

    public function __construct(
        protected FeatureService $featureService
    ) {}

    /**
     * GET /api/me/organization
     * Obtener los datos de la organización
     */
    /*public function organization(Request $request)
    {
        $organization = $request->user()->currentOrganization();

        return response()->json([
            ...$organization->toArray(),
            'logo_url' => $organization->logo_url
                ? url(Storage::url($organization->logo_url))
                : null,
        ]);
    }*/

    public function organization(Request $request)
    {
        return response()->json(
            $request->user()->currentOrganization()
        );
    }

    /**
     * PUT /api/me/organization
     * Actualizar los datos de la organización
     */
    public function updateOrganization(Request $request)
    {

        $organization = $this->getOrganization($request);

        /*
        |----------------------------------------------------------
        | DETECTAR MODO
        |----------------------------------------------------------
        */
        $isOnboarding = !$organization->onboarding_completed_at
            && $organization->onboarding_step === Organization::ONBOARDING_BUSINESS_SETUP;

        /*
        |----------------------------------------------------------
        | VALIDACIÓN DINÁMICA
        |----------------------------------------------------------
        */
        if ($isOnboarding) {

            $data = $request->validate([
                'name' => ['required', 'string', 'min:3', 'max:120'],
                'business_niche' => ['required', 'string', Rule::in([
                    'beauty',
                    'barbershop',
                    'hair_salon',
                    'nails',
                    'medical',
                    'psychology',
                    'dentist',
                    'nutrition',
                    'therapy',
                    'spa',
                    'fitness',
                    'education',
                    'consulting',
                    'coaching',
                    'pet_grooming',
                    'tattoo',
                    'other'
                ])],
                'phone' => ['required', 'array'],
                'country' => ['required', 'string', 'size:2'],
                'state' => ['nullable', 'string', 'max:100'],
                'city' => ['nullable', 'string', 'max:100'],
            ]);
        } else {

            $data = $request->validate(
                [
                    'name' => ['required', 'string', 'min:3', 'max:120'],
                    'slogan' => ['nullable', 'string', 'max:200'],
                    'business_niche' => ['required', 'string', Rule::in([
                        'beauty',
                        'barbershop',
                        'hair_salon',
                        'nails',
                        'medical',
                        'psychology',
                        'dentist',
                        'nutrition',
                        'therapy',
                        'spa',
                        'fitness',
                        'education',
                        'consulting',
                        'coaching',
                        'pet_grooming',
                        'tattoo',
                        'other'
                    ])],
                    'slug' => [
                        'required',
                        'string',
                        'min:3',
                        'max:120',
                        'alpha_dash',
                        'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                        'unique:organizations,slug,' . $organization->id,
                    ],
                    'reference_prefix' => [
                        'required',
                        'string',
                        'min:2',
                        'max:5',
                        'alpha_dash',
                        'regex:/^(?=.*[A-Z])[A-Z0-9]+$/',
                    ],

                    'email' => ['nullable', 'email', 'max:255'],

                    'phone' => ['nullable', 'array'],

                    'website' => ['nullable', 'url', 'max:255'],

                    // Dirección
                    'country' => ['nullable', 'string', 'size:2'],
                    'state' => ['nullable', 'string', 'max:100'],
                    'city' => ['nullable', 'string', 'max:100'],
                    'zip_code' => ['nullable', 'string', 'max:20'],
                    'address' => ['nullable', 'string', 'max:255'],

                    // Branding
                    'primary_color' => ['nullable', 'string', 'max:20'],
                    'secondary_color' => ['nullable', 'string', 'max:20'],
                    'logo_url' => ['nullable', 'url'],

                    // Sistema
                    //'timezone' => ['required', 'string'],
                    'timezone' => ['nullable', 'string'],

                    // Dominios
                    'primary_domain' => ['nullable', 'string', 'max:255'],
                    'domains' => ['nullable', 'array'],

                    // FACTURACIÓN (NUEVO)
                    'legal_name' => ['nullable', 'string', 'max:255'],
                    'tax_id' => ['nullable', 'string', 'max:20'],
                    'tax_regime' => ['nullable', 'string', 'max:10'],
                    'invoice_zip_code' => ['nullable', 'string', 'max:10'],
                    'cfdi_email' => ['nullable', 'email', 'max:150'],
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

            $billingEnabled = false;
            if (!$billingEnabled) {
                unset(
                    $data['legal_name'],
                    $data['tax_id'],
                    $data['tax_regime'],
                    $data['invoice_zip_code'],
                    $data['cfdi_email']
                );
            }

            if (isset($data['slug'])) {
                $data['slug'] = strtolower($data['slug']);
            }

            if (isset($data['reference_prefix'])) {
                $data['reference_prefix'] = strtoupper($data['reference_prefix']);
            }
        }

        /*
        |----------------------------------------------------------
        | UPDATE ORGANIZATION
        |----------------------------------------------------------
        */
        $organization->update($data);

        /*
        |----------------------------------------------------------
        | SYNC CON PRIMARY BRANCH (SOLO ONBOARDING)
        |----------------------------------------------------------
        */
        if ($isOnboarding) {

            $primaryBranch = $organization->branches()
                ->where('is_primary', true)
                ->first();

            if ($primaryBranch) {
                $primaryBranch->update([
                    //'name' => $data['name'],
                    'name' => 'Principal',
                    'phone' => $data['phone'] ?? null,
                    'country' => $data['country'],
                    'state' => $data['state'] ?? null,
                    'city' => $data['city'] ?? null,
                ]);
            }
        }

        /*
        |----------------------------------------------------------
        | ONBOARDING FLOW
        |----------------------------------------------------------
        */
        if ($isOnboarding) {

            if (
                $organization->name &&
                $organization->phone &&
                $organization->country
            ) {
                $organization->advanceOnboarding(
                    Organization::ONBOARDING_SERVICE_CREATED
                );
            }

            return response()->json([
                'message' => 'Negocio configurado correctamente',
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'business_niche' => $organization->business_niche,
                    'onboarding_step' => $organization->onboarding_step,
                    'onboarding_completed_at' => $organization->onboarding_completed_at,
                ]
            ]);
        }

        /*
        |----------------------------------------------------------
        | FLOW NORMAL
        |----------------------------------------------------------
        */
        return response()->json([
            'message' => 'Organización actualizada correctamente',
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ]
        ]);
    }

    // Guardar / Actualizar logo
    public function uploadLogo(Request $request)
    {
        $organization = $this->getOrganization($request);
        $user = $request->user();

        /*
        |----------------------------------------------------------
        | Validamos permisos de acceso
        |----------------------------------------------------------
        */
        if (!$this->featureService->can($organization, $request->user()->id, 'citas.profile')) {
            abort(403, 'No tienes acceso a la acción solicitada');
        }

        $role = BranchUserAccess::where('user_id', $user->id)
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->with('role')
            ->get()
            ->pluck('role.key')
            ->unique()
            ->toArray();

        $allowedRoles = ['root', 'owner'];

        if (!collect($role)->intersect($allowedRoles)->isNotEmpty()) {
            abort(403, 'No tienes permisos para realizar la acción solicitada');
        }

        // Validaciones generales
        $request->validate(
            [
                'logo' => [
                    'required',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:2048',
                ]
            ],
            [
                'logo.required' => 'Debes seleccionar una imagen.',
                'logo.image' => 'El archivo seleccionado no es una imagen válida.',
                'logo.mimes' => 'La imagen debe estar en formato JPG, JPEG, PNG o WEBP.',
                'logo.max' => 'La imagen no puede superar los 2 MB.',
            ]
        );

        // Borrar logo anterior
        if ($organization->logo_url) {

            Storage::disk('public')
                ->delete($organization->logo_url);
        }

        // Crear manager
        $manager = new ImageManager(
            new Driver()
        );

        // Leer imagen
        $image = $manager->decode(
            $request->file('logo')
        );

        // Redimencionar
        /*$image->cover(
            512,
            512
        );*/

        $image->scaleDown(
            width: 512,
            height: 512
        );

        // Convertir a webp
        $encoded = $image->encode(
            new WebpEncoder(
                quality: 80
            )
        );

        // Nombre fijo
        $filename =
            'organization-' .
            $organization->id .
            '.webp';

        // Ruta
        $path =
            'organizations/logos/' .
            $filename;

        // Guardar
        Storage::disk('public')
            ->put(
                $path,
                (string) $encoded
            );

        // Actualizar BD
        $organization->update([
            'logo_url' => $path
        ]);

        return response()->json([
            'message' => 'Logo actualizado',
            /*'logo_url' => $organization->logo_url
                ? url(Storage::url($organization->logo_url))
                : null,*/
            'logo_url' => $organization->logo_url

        ]);
    }
}
