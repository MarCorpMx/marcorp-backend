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

            // 1️⃣ Crear usuario
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

            // 2️⃣ Crear organización inicial
            $organization = Organization::create([
                'name' => "Consultorio {$user->first_name}",
                'slug' => Str::slug("consultorio-{$user->id}-{$user->first_name}"),
                'owner_user_id' => $user->id,
                'status' => 'active',
            ]);

            // 3️⃣ Relacionar usuario como OWNER
            OrganizationUser::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            // 4️⃣ Asignar subsistema a la organización
            OrganizationSubsystem::create([
                'organization_id' => $organization->id,
                'subsystem_id' => $request->subsystem_id,
                'status' => 'trial',
                'started_at' => now(),
                'is_paid' => false,
            ]);

            // 5️⃣ Generar token Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            // 6️⃣ Respuesta con contexto
            return response()->json([
                'message' => 'Registro exitoso',
                'lau' => 'es una puta',
                'token' => $token,
                'context' => [
                    'user' => $user,
                    'organization' => $organization,
                    'role' => 'owner',
                    'subsystem_id' => $request->subsystem_id,
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

        // Obtener sistemas del usuario con sus roles
        /*$systems = $user->userSubsystems()
            ->with(['subsystem', 'roles'])
            ->get()
            ->map(function ($us) {
                return [
                    'user_subsystem_id' => $us->id,
                    'subsystem_id' => $us->subsystem->id,
                    'subsystem_key' => $us->subsystem->key,
                        'subsystem_name' => $us->subsystem->name,
                    'is_paid' => (bool) $us->is_paid,
                    'roles' => $us->roles->pluck('key') // ['root','admin','teacher',...]
                ];
            });*/


        
        $systems = OrganizationUser::where('user_id', $user->id)
            ->with([
                'organization.subsystems.subsystem'
            ])
            ->get()
            ->flatMap(function ($orgUser) {
                return $orgUser->organization->subsystems->map(function ($orgSubsystem) use ($orgUser) {
                    return [
                        'organization_id' => $orgUser->organization->id,
                        'organization_name' => $orgUser->organization->name,
                        'subsystem_id' => $orgSubsystem->subsystem->id,
                        'subsystem_key' => $orgSubsystem->subsystem->key,
                        'subsystem_name' => $orgSubsystem->subsystem->name,
                        'is_paid' => $orgSubsystem->is_paid,
                        'roles' => $orgUser->role,
                    ];
                });
            })
            ->values();

            /*SELECT
    ou.organization_id,
    o.name AS organization_name,
    s.id AS subsystem_id,
    s.`key` AS subsystem_key,
    s.name AS subsystem_name,
    os.is_paid,
    ou.role
FROM organization_users ou
INNER JOIN organizations o
    ON o.id = ou.organization_id
INNER JOIN organization_subsystems os
    ON os.organization_id = o.id
INNER JOIN subsystems s
    ON s.id = os.subsystem_id
WHERE ou.user_id = :user_id;*/



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
