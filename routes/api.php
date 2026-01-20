<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Test
Route::get('/ping', function () {
    return response()->json([
        'message' => 'Marcorp API is running',
        'version' => app()->version()
    ]);
});

// Systems
Route::get('/subsystems', [\App\Http\Controllers\Api\SubsystemController::class, 'index']);

// Auth
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


/*Route::middleware('auth:sanctum')->group(function(){
    Route::get('/user/getData', [subsystemUsersController::class, 'user']);
    Route::post('/user/logout', [subsystemUsersController::class, 'logout']);

});
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});*/
