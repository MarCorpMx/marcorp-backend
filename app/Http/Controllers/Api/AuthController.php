<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;

use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationSubsystem;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\Plan;
use App\Models\OrganizationNotificationSetting;
use App\Models\Branch;
use App\Models\BranchUserAccess;
use App\Models\StaffMember;
use App\Models\BranchStaff;

use App\Services\SubsystemResolver;
use App\Services\FeatureService;
use App\Services\SubscriptionService;
use App\Services\AuthContextService;
use App\Services\NotificationService;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;



class AuthController extends Controller
{

    public function __construct(
        protected SubsystemResolver $subsystemResolver,
        protected SubscriptionService $subscriptionService,
        protected AuthContextService $authContextService,
        protected NotificationService $notificationService
    ) {}

    /**
     * REGISTRO DE USUARIO
     */
    /*public function register(RegisterRequest $request)
    {
        return DB::transaction(function () use ($request) {

            // 1 Crear usuario
            $user = User::firstOrCreate(
                ['email' => $request->email],
                [
                    'username' => $request->email,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'name' => "{$request->first_name} {$request->last_name}",
                    'phone' => $request->phone,
                    'password' => Hash::make($request->password),
                    'status' => 'active',
                    'email_verified' => false,
                ]
            );

            // 2 Crear organización inicial
            $organization = Organization::create([
                'name' => "Consultorio {$user->first_name}",
                'slug' => Str::slug("empresa-{$user->id}-{$user->first_name}"),
                'owner_user_id' => $user->id,
                'status' => 'active',
            ]);

            // 3 Relacionar usuario como OWNER
            OrganizationUser::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                //'role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            // 4 Obtener plan FREE
            $freePlan = Plan::where('key', 'free')->firstOrFail();

            // 5 Asignar subsistema a la organización
            OrganizationSubsystem::create([
                'organization_id' => $organization->id,
                'subsystem_id' => $request->subsystem_id,
                'plan_id' => $freePlan->id,
                'status' => 'active',
                'started_at' => now(),
                'is_paid' => false,
            ]);

            // 6 Generar token Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            // 7 Respuesta con contexto
            return response()->json([
                'message' => 'Registro exitoso',
                'token' => $token,
                'context' => [
                    'user' => $user,
                    'organization' => $organization,
                    'role' => 'owner',
                    'subsystem_id' => $request->subsystem_id,
                    'plan' => $freePlan->key,
                ]
            ], 201);
        });
    }*/

    public function register(RegisterRequest $request)
    {

        $subsystemCode = $request->subsystem;
        $subsystemId = $this->subsystemResolver->resolve($subsystemCode);

        if ($subsystemCode && !$subsystemId) {
            throw new \Exception("Subsystem '{$subsystemCode}' not found or inactive.");
        }


        return DB::transaction(function () use ($request, $subsystemCode, $subsystemId) {
            /*
            |--------------------------------------------------------------------------
            | Crear usuario
            |--------------------------------------------------------------------------
            */
            try {
                $user = User::create([
                    'email' => $request->email,
                    'username' => trim($request->email),
                    'first_name' => trim($request->first_name),
                    'name' => trim($request->first_name),
                    'password' => Hash::make(trim($request->password)),
                    'status' => 'active',
                    'email_verified' => false,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {

                if ($e->getCode() === '23000') {
                    return response()->json([
                        'message' => 'Este correo ya está registrado'
                    ], 422);
                }

                throw $e;
            }

            /*
            |--------------------------------------------------------------------------
            | Crear organización inicial (al crear la organización se crea la sucursal principal)
            |--------------------------------------------------------------------------
            */
            $basePrefix = Str::upper(Str::substr($user->first_name, 0, 3) ?: 'ORG');
            $referencePrefix = $basePrefix . $user->id;

            // Normalizar los datos
            $firstName = trim(explode(' ', $user->first_name)[0]);
            $cleanName = Str::limit($firstName, 20, '');

            $organizationName = "Negocio de {$cleanName}";

            $slugBase = Str::slug($cleanName);
            //$organizationSlug = "org-{$user->id}-{$slugBase}";
            $organizationSlug = "org-{$user->id}";

            //$branchName = $organizationName;
            //$branchSlug = "{$organizationSlug}-principal";

            $timezone = $request->input('timezone', 'UTC');

            if (!in_array($timezone, timezone_identifiers_list())) {
                $timezone = 'UTC';
            }

            $organization = Organization::create([
                'name' => $organizationName,
                'slug' => $organizationSlug,
                'reference_prefix' => $referencePrefix,
                'owner_user_id' => $user->id,
                'status' => 'active',
                'timezone' => $timezone,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Relacionar usuario como OWNER
            |--------------------------------------------------------------------------
            */
            OrganizationUser::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Obtener plan FREE por subsistema
            |--------------------------------------------------------------------------
            */
            $freePlan = Plan::where('subsystem_id', $subsystemId)
                ->where('key', 'free')
                ->firstOrFail();

            $dates = $this->subscriptionService->getSubscriptionDates($freePlan);

            /*
            |--------------------------------------------------------------------------
            | Asignar subsistema a la organización
            |--------------------------------------------------------------------------
            */
            OrganizationSubsystem::create([
                'organization_id' => $organization->id,
                'subsystem_id' => $subsystemId,
                'plan_id' => $freePlan->id,
                'status' => 'active',
                'started_at'  => $dates['started_at'],
                'renews_at'   => $dates['renews_at'],
                'expires_at'  => $dates['expires_at'],
                'is_paid' => false,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Asignar acceso a sucursal
            |--------------------------------------------------------------------------
            */
            $mainBranch = Branch::where('organization_id', $organization->id)
                ->where('is_primary', true)
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Crear owner staff
            |--------------------------------------------------------------------------
            */
            $ownerStaff = StaffMember::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                ],
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => true,
                ]
            );

            // Crear branch_staff
            BranchStaff::firstOrCreate([
                'branch_id' => $mainBranch->id,
                'staff_member_id' => $ownerStaff->id,
            ]);

            $ownerRole = Role::where('key', 'owner')->firstOrFail();

            BranchUserAccess::updateOrCreate([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'branch_id' => $mainBranch->id,
                'subsystem_id' => $subsystemId,
                'role_id' => $ownerRole->id,
            ], [
                'staff_member_id' => $ownerStaff->id,
                'is_active' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Configuración base (email / notificaciones)
            |--------------------------------------------------------------------------
            */
            OrganizationNotificationSetting::updateOrCreate(
                ['organization_id' => $organization->id],
                [
                    'notification_to' => [trim($user->email)],
                    'notification_bcc' => [],
                    'auto_reply_enabled' => false,
                    'emergency_footer_enabled' => false,
                    'office_hours' => [
                        'start' => '09:00',
                        'end' => '18:00',
                        'timezone' => $organization->timezone,
                    ],
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Token (login automático)
            |--------------------------------------------------------------------------
            */
            $token = $user->createToken('auth_token')->plainTextToken;

            $context = $this->authContextService->build($user);

            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $user->id,
                    'hash' => sha1($user->email),
                ]
            );

            // Enviar correos de bienvenida
            DB::afterCommit(function () use ($user, $organization, $subsystemCode, $verificationUrl) {

                // Correo para cliente
                $CITARA_url = config('services.citara.front_url');

                $this->notificationService->trigger(
                    type: 'auth_welcome_user',
                    data: [
                        'name' => $user->first_name,
                        'email' => $user->email,
                        'verification_url' => $verificationUrl
                    ],
                    organization: $organization,
                    recipient: $user->email,
                    recipientName: $user->first_name,
                    notifiable: $user,
                    subsystemCode: $subsystemCode
                );

                // Correo(s) Interno(s)
                foreach (config('mail.admin_addresses') as $adminEmail) {
                    $this->notificationService->trigger(
                        type: 'auth_user_registered_internal',
                        data: [
                            'name' => $user->first_name,
                            'email' => $user->email,
                            'date' => now()->format('d/m/Y'),
                            'time' => now()->format('H:i'),
                        ],
                        organization: null,
                        recipient: trim($adminEmail),
                        recipientName: 'Admin',
                    );
                }
            });

            /*
            |--------------------------------------------------------------------------
            | Respuesta SaaS context-aware
            |--------------------------------------------------------------------------
            */
            return response()->json([
                'message' => 'Registro exitoso',
                'token' => $token,
                ...$context
            ], 201);
        });
    }


    /**
     * LOGIN
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required',
            'password' => 'required'
        ]);

        /*
        |--------------------------------------------------------------------------
        | Buscar usuario (email o username)
        |--------------------------------------------------------------------------
        */
        $user = User::where('email', $request->login)
            ->orWhere('username', $request->login)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Validar acceso activo a organizaciones
        |--------------------------------------------------------------------------
        */
        $activeOrganizations = OrganizationUser::where('user_id', $user->id)
            ->where('status', 'active')
            ->count();

        if ($activeOrganizations === 0) {
            return response()->json([
                'message' => 'Tu acceso ha sido desactivado. Contacta al administrador.'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Revocar tokens anteriores (seguridad)
        |--------------------------------------------------------------------------
        */
        $user->tokens()->delete();

        /*
        |--------------------------------------------------------------------------
        | Crear token
        |--------------------------------------------------------------------------
        */
        $token = $user->createToken('auth_token')->plainTextToken;

        $context = $this->authContextService->build($user);

        /*
        |--------------------------------------------------------------------------
        | Respuesta final
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'message' => 'Login exitoso',
            'token' => $token,
            ...$context
        ]);
    }

    /**
     * LOGOUT
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ]);
    }
}
