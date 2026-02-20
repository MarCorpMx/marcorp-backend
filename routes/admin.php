<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\OrganizationController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| ADMIN AUTH
|--------------------------------------------------------------------------
*/

/*
Route::prefix('admin/auth')->group(function () {

    Route::post('/login', [AdminAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AdminAuthController::class, 'logout']);

        Route::get('/me', function (Request $request) {
            return $request->user();
        });
    });
});

/*
|--------------------------------------------------------------------------
| ADMIN PROTECTED ROUTES
|--------------------------------------------------------------------------
*/

/*
Route::middleware(['auth:sanctum', 'ability:admin'])
    ->prefix('admin')
    ->group(function () {

        Route::get('/organizations', [OrganizationController::class, 'index']);
        Route::get('/organizations/{id}', [OrganizationController::class, 'show']);

        Route::get('/plans', fn() => response()->json(['ok' => true]));
        Route::get('/subsystems', fn() => response()->json(['ok' => true]));
});

*/
