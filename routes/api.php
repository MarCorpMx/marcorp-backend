<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MeController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| TEST
|--------------------------------------------------------------------------
*/
Route::get('/ping', function () {
    return response()->json([
        'message' => 'Marcorp_core API is running',
        'version' => app()->version()
    ]);
});

/*
|--------------------------------------------------------------------------
| LISTA DE SISTEMAS ACTIVOS
|--------------------------------------------------------------------------
*/
Route::get('/subsystems', [\App\Http\Controllers\Api\SubsystemController::class, 'index']);

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', function (Request $request) {
            //return response()->json($request->user());
            return $request->user();
        });
    });
});


/*
|--------------------------------------------------------------------------
| ME (contexto del usuario autenticado)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('me')->group(function () {
   
    Route::get('/', [MeController::class, 'index']);
    // GET /api/me

    Route::get('/systems', [MeController::class, 'systems']);
    // GET /api/me/systems

    Route::get('/features', [MeController::class, 'features']);
    // GET /api/me/features

    Route::get('/usage', [MeController::class, 'usage']);
    // GET /api/me/usage (opcional, futuro)

    Route::get('/subscription', [MeController::class, 'subscription']);
    // GET /api/me/subscription

    Route::get('/plans', [MeController::class, 'plans']);
    // GET /api/me/plans

});
