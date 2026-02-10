<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;

/*use App\Models\UserSubsystem;
use App\Models\UserSubsystemRole;
use App\Models\UserPlan;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Membership;*/

use App\Models\Organization;
use App\Models\OrganizationSubsystem;
use App\Models\OrganizationUser;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * REGISTRO DE USUARIO
     */
    public function register(RegisterRequest $request)
    {
        return DB::transaction(function () use ($request) {

            // 1 Crear usuario
            $user = User::create([
                'username' => $request->email,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'name' => "{$request->first_name} {$request->last_name}",
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'status' => 'active',
                'email_verified' => false,
            ]);

            // 2 Crear organización inicial
            $organization = Organization::create([
                'name' => "Consultorio {$user->first_name}",
                'slug' => Str::slug("consultorio-{$user->id}-{$user->first_name}"),
                'owner_user_id' => $user->id,
                'status' => 'active',
            ]);

            // 3 Relacionar usuario como OWNER
            OrganizationUser::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => 'owner',
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
    }


    /**
     * LOGIN
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required', // puede ser username o email
            'password' => 'required'
        ]);

        // Buscar por email O username
        $user = User::where('email', $request->login)
            ->orWhere('username', $request->login)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        // Revocamos tokens previos (opcional pero recomendado)
        $user->tokens()->delete();

        // Crear token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        $systems = OrganizationUser::where('user_id', $user->id)
            ->with([
                'organization.subsystems.subsystem',
                'organization.subsystems.plan',
            ])
            ->get()
            ->flatMap(function ($orgUser) {
                return $orgUser->organization->subsystems->map(function ($orgSubsystem) use ($orgUser) {

                    $plan = $orgSubsystem->plan;

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

                        'has_active_plan' => (bool) $plan,
                        'plan_key' => $plan->key,
                        
                        'status' => $orgSubsystem->status,
                        'is_paid' => $orgSubsystem->is_paid,

                        'role' => $orgUser->role,
                    ];
                });
            })
            ->values();


        return response()->json([
            'message' => 'Login exitoso',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
            ],
            'systems' => $systems
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
