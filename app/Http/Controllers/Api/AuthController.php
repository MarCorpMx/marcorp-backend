<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Models\UserSubsystem;
use App\Models\UserSubsystemRole;
use App\Models\UserPlan;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * REGISTRO DE USUARIO
     */
    public function register(RegisterRequest $request)
    {
        return DB::transaction(function () use ($request) {

            // 1) Crear usuario
            $user = User::create([
                'username'     => $request->email,
                'first_name'   => $request->first_name,
                'last_name'    => $request->last_name,
                'name'         => $request->first_name . ' ' . $request->last_name,
                'email'        => $request->email,
                'phone'        => $request->phone,
                'password'     => Hash::make($request->password),
                'status'       => 'active',
                'email_verified' => false,
            ]);

            // 2) Obtener plan gratuito por defecto
            $freePlan = Plan::where('key', 'free')->firstOrFail();

            // 3) Obtener rol por defecto
            $defaultRole = Role::where('key', 'user')->firstOrFail();

            // 4) Obtener membresía por defecto (si aplica)
            $defaultMembership = Membership::where('key', 'basic')->first();

            if (!$defaultMembership) {
                $defaultMembership = Membership::first();
            }

            // 5) Asociar usuario con el subsistema elegido
            $userSubsystem = UserSubsystem::create([
                'user_id'      => $user->id,
                'subsystem_id' => $request->subsystem_id,
                'membership_id' => $defaultMembership->id,
                'role'         => 'user',
                'active'       => true,
                'activated_at' => now(),
            ]);

            // 6) Asignar rol en user_subsystem_roles
            UserSubsystemRole::create([
                'user_subsystem_id' => $userSubsystem->id,
                'role_id' => $defaultRole->id,
            ]);

            // 7) Asignar plan inicial gratuito
            UserPlan::create([
                'user_id'    => $user->id,
                'plan_id'    => $freePlan->id,
                'started_at' => now(),
                'is_active'  => true,
            ]);

            // 8) Generar token Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Usuario registrado correctamente',
                'user'    => $user,
                'subsystem' => $userSubsystem->subsystem_id,
                'plan' => $freePlan->key,
                'role' => $defaultRole->key,
                'token' => $token
            ], 201);
        });
    }

    /**
     * LOGIN
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son correctas.']
            ]);
        }

        // Revocamos tokens previos (opcional pero recomendado)
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'user' => $user,
            'token' => $token
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
