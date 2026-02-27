<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\ServiceController;

use App\Http\Controllers\Api\ProfessionalController;
use App\Http\Controllers\Api\AgendaController;
use App\Http\Controllers\Api\NonWorkingDayController;

use App\Http\Controllers\Api\ScheduleSettingController; // Verificar uso

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
| Mensajes de Contacto
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {

    Route::post('/contact-messages', [ContactMessageController::class, 'store'])
        ->name('api.contact-messages.store');
});

/*
|--------------------------------------------------------------------------
| Mindfulness - GEMINI
|--------------------------------------------------------------------------
*/
Route::post('/mindfulness', \App\Http\Controllers\Api\MindfulnessController::class);


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

    Route::get('/organization', [MeController::class, 'organization']);
    // GET /api/me/organization
    Route::put('/organization', [MeController::class, 'updateOrganization']);
    // PUT /api/me/organization

    /*
    |--------------------------------------------------------------------------
    | SERVICES (Servicios de la organización)
    |--------------------------------------------------------------------------
    */
    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::get('/services/{service}', [ServiceController::class, 'show']);
    Route::put('/services/{service}', [ServiceController::class, 'update']);
    Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

    // v1 para Configuración - Agenda
    Route::get('/schedule-settings', [ScheduleSettingController::class, 'getSchedule']);
    Route::put('/schedule-settings', [ScheduleSettingController::class, 'updateSchedule']);

    // v2 para Configuración - Agenda
    Route::get('/professionals', [ProfessionalController::class, 'index']);
    Route::post('/professionals', [ProfessionalController::class, 'store']);
    Route::put('/professionals/{professional}', [ProfessionalController::class, 'update']);
    Route::delete('/professionals/{professional}', [ProfessionalController::class, 'destroy']);

    Route::get('/professionals/{professional}/agenda', [AgendaController::class, 'show']);
    Route::put('/professionals/{professional}/agenda', [AgendaController::class, 'update']);

    Route::get('/professionals/{professional}/non-working-days', [NonWorkingDayController::class, 'index']);
    Route::post('/professionals/{professional}/non-working-days', [NonWorkingDayController::class, 'store']);
    Route::delete('/professionals/{professional}/non-working-days/{day}', [NonWorkingDayController::class, 'destroy']);


    /*
    |--------------------------------------------------------------------------
    | CLIENTES
    |--------------------------------------------------------------------------
    */
    Route::get('/clients', [\App\Http\Controllers\Api\ClientController::class, 'index']);
    Route::post('/clients', [\App\Http\Controllers\Api\ClientController::class, 'store']);
    Route::get('/clients/{client}', [\App\Http\Controllers\Api\ClientController::class, 'show']);
    Route::put('/clients/{client}', [\App\Http\Controllers\Api\ClientController::class, 'update']);
    Route::delete('/clients/{client}', [\App\Http\Controllers\Api\ClientController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | CLIENTE - CITAS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/clients/{client}/appointments',
        [\App\Http\Controllers\Api\ClientAppointmentController::class, 'index']
    );

    /*
    |--------------------------------------------------------------------------
    | CLIENTE - NOTAS (futuro clínico)
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/clients/{client}/notes',
        [\App\Http\Controllers\Api\ClientNoteController::class, 'index']
    );

    Route::post(
        '/clients/{client}/notes',
        [\App\Http\Controllers\Api\ClientNoteController::class, 'store']
    );
});

/*
|--------------------------------------------------------------------------
| PUBLIC BOOKING (sin autenticación)
|--------------------------------------------------------------------------
*/

//\App\Http\Controllers\Api\SubsystemController
Route::prefix('v1/public')
    ->middleware(['throttle:60,1'])
    ->group(function () {

        Route::get(
            '{organization:slug}/services',
            [\App\Http\Controllers\Api\PublicBookingController::class, 'services']
        );

        Route::get(
            '{organization:slug}/availability',
            [\App\Http\Controllers\Api\PublicBookingController::class, 'availability']
        );

        Route::post(
            '{organization:slug}/appointments',
            [\App\Http\Controllers\Api\PublicBookingController::class, 'store']
        );
    });
