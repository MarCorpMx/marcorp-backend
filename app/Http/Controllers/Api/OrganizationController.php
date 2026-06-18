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

    public function organization(Request $request)
    {

        $organization = $request->attributes->get('organization');

        return response()->json($organization);
    }

    /**
     * PUT /api/me/organization
     * Actualizar los datos de la organización
     */
    public function updateOrganization(Request $request)
    {

        $organization = $this->getOrganization($request);
        $user = $request->user();

        // Normalizar website antes de validar
        $request->merge([
            'website' => $this->normalizeUrl($request->website)
        ]);

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

            $data = $request->validate(
                [
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
                ],
                [
                    'name.required' => 'El nombre es obligatorio.',
                    'name.min' => 'El nombre debe tener al menos 3 caracteres.',

                    'business_niche.required' => 'Selecciona una categoría.',
                    'business_niche.in' => 'Selecciona una categoría válida.',

                    'phone.required' => 'El teléfono es obligatorio.',

                    'country.required' => 'Selecciona un país.',
                    'country.size' => 'Selecciona un país válido.',
                ]
            );
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
                    // NAME
                    'name.required' => 'El nombre es obligatorio.',
                    'name.min' => 'El nombre debe tener al menos 3 caracteres.',
                    'name.max' => 'El nombre no puede superar 120 caracteres.',

                    // BUSINESS NICHE
                    'business_niche.required' => 'Selecciona el giro de tu negocio.',
                    'business_niche.in' => 'Selecciona una categoría válida.',

                    // SLUG
                    'slug.required' => 'El enlace es obligatorio.',
                    'slug.min' => 'El enlace debe tener al menos 3 caracteres.',
                    'slug.max' => 'El enlace no puede superar los 120 caracteres.',
                    'slug.regex' => 'Solo minúsculas, números y guiones (ej: punto-de-calma).',
                    'slug.unique' => 'El enlace ya está siendo usado. Prueba con otro.',
                    'slug.alpha_dash' => 'Solo letras, números y guiones.',

                    // PREFIX
                    'reference_prefix.required' => 'El prefijo es obligatorio.',
                    'reference_prefix.min' => 'Mínimo 2 caracteres.',
                    'reference_prefix.max' => 'Máximo 5 caracteres.',
                    'reference_prefix.regex' => 'Solo letras mayúsculas y números (ej: PDC).',
                    'reference_prefix.alpha_dash' => 'Solo letras, números y guiones.',

                    // EMAIL
                    'email.email' => 'Ingresa un correo válido.',
                    'email.max' => 'El correo no puede superar 255 caracteres.',

                    // WEBSITE
                    'website.url' => 'Ingresa una URL válida.',
                    'website.max' => 'La URL no puede superar 255 caracteres.',

                    // ADDRESS
                    'country.size' => 'Selecciona un país válido.',
                    'state.max' => 'El estado no puede superar 100 caracteres.',
                    'city.max' => 'La ciudad no puede superar 100 caracteres.',
                    'zip_code.max' => 'El código postal no puede superar 20 caracteres.',
                    'address.max' => 'La dirección no puede superar 255 caracteres.',

                    // COLORS
                    'primary_color.max' => 'Color inválido.',
                    'secondary_color.max' => 'Color inválido.',

                    // DOMAINS
                    'primary_domain.max' => 'El dominio es demasiado largo.',

                    // BILLING
                    'legal_name.max' => 'La razón social no puede superar 255 caracteres.',
                    'tax_id.max' => 'El RFC no puede superar 20 caracteres.',
                    'tax_regime.max' => 'El régimen fiscal no puede superar 10 caracteres.',
                    'invoice_zip_code.max' => 'El código postal fiscal no puede superar 10 caracteres.',
                    'cfdi_email.email' => 'El correo fiscal no es válido.',
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
        if ($isOnboarding) {
            $organization->update($data);
        } else {
            $data['updated_by'] = $user->id;
            $organization->update($data);
        }

        $organization->refresh();


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
            'logo_url' => $path,
            'updated_by' => $user->id
        ]);

        $organization->refresh();

        return response()->json([
            'message' => 'Logo actualizado',
            /*'logo_url' => $organization->logo_url
                ? url(Storage::url($organization->logo_url))
                : null,*/
            'logo_url' => $organization->logo_url

        ]);
    }

    // Eliminar logo
    public function deleteLogo(Request $request)
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

        /*
        |----------------------------------------------------------
        | Eliminar archivo físico
        |----------------------------------------------------------
        */
        if ($organization->getRawOriginal('logo_url')) {

            Storage::disk('public')
                ->delete(
                    $organization->getRawOriginal('logo_url')
                );
        }

        /*
        |----------------------------------------------------------
        | Limpiar BD
        |----------------------------------------------------------
        */
        $organization->update([
            'logo_url' => null,
            'updated_by' => $user->id
        ]);

        $organization->refresh();

        return response()->json([
            'message' => 'Logo eliminado'
        ]);
    }

    // Cambiar valor de bookingEnabled
    public function bookingEnabled(Request $request)
    {
        $organization = $this->getOrganization($request);
        $user = $request->user();

        $request->validate(
            [
                'online_booking_enabled' => ['required', 'boolean']
            ],
            [
                'online_booking_enabled.required' => 'No fue posible actualizar la agenda online.',
                'online_booking_enabled.boolean' => 'El estado seleccionado para la agenda online no es válido.'
            ]
        );

        /*
        |----------------------------------------------------------
        | Validamos permisos de acceso
        |----------------------------------------------------------
        */
        if (!$this->featureService->can($organization, $request->user()->id, 'citas.profile')) {
            abort(403, 'No tienes acceso a la acción solicitada');
        }

        $userRoles = BranchUserAccess::where('user_id', $user->id)
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->with('role')
            ->get()
            ->pluck('role.key')
            ->unique()
            ->toArray();

        $allowedRoles = ['root', 'owner', 'admin'];

        if (!collect($userRoles)->intersect($allowedRoles)->isNotEmpty()) {
            abort(403, 'No tienes permisos para realizar la acción solicitada');
        }


        $organization->update([
            'online_booking_enabled' => $request->boolean('online_booking_enabled'),
            'updated_by' => $user->id
        ]);

        $organization->refresh();

        return response()->json([
            'data' => ['online_booking_enabled' => (bool) $organization->online_booking_enabled]
        ]);
    }


    private function normalizeUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (!preg_match('/^https?:\/\//i', $url)) {
            return 'https://' . $url;
        }

        return $url;
    }
}
