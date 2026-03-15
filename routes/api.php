<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\ServiceController;

use App\Http\Controllers\Api\StaffMemberController;
use App\Http\Controllers\Api\StaffMemberAgendaController;
use App\Http\Controllers\Api\StaffMemberNonWorkingDayController;
use App\Http\Controllers\Api\StaffMemberScheduleController;

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
    /*Route::post('/contact-messages', [ContactMessageController::class, 'store'])
        ->name('api.contact-messages.store');*/

    Route::post('/contact-messages', [ContactMessageController::class, 'store'])
        ->name('api.contact-messages.store')
        ->middleware('throttle:3,1'); // 3 mensajes por minuto
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
    | APPOINTMENTS (CITAS DEL USUARIO AUTENTICADO)
    |--------------------------------------------------------------------------
    */
    Route::get('/appointments', [\App\Http\Controllers\Api\AppointmentController::class, 'index']);
    Route::post('/appointments', [\App\Http\Controllers\Api\AppointmentController::class, 'store']);
    Route::get('/appointments/{appointment}', [\App\Http\Controllers\Api\AppointmentController::class, 'show']);
    Route::put('/appointments/{appointment}', [\App\Http\Controllers\Api\AppointmentController::class, 'update']);
    Route::delete('/appointments/{appointment}', [\App\Http\Controllers\Api\AppointmentController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | SERVICES (Servicios de la organización)
    |--------------------------------------------------------------------------
    */
    Route::get('/services', [ServiceController::class, 'index']); // Administración
    Route::get('/services/list', [ServiceController::class, 'list']); // select interno
    Route::get('/service-variants/list', [ServiceController::class, 'listVariants']); // select interno

    Route::get('/my-services', [ServiceController::class, 'my-services']); // servicios del staff autenticado
    Route::get('/service-variants/{id}/staff', [ServiceController::class, 'staff']); // Staff por variante de servicio
    // /public/services → web pública

    Route::post('/services', [ServiceController::class, 'store']);
    Route::get('/services/{service}', [ServiceController::class, 'show']);
    Route::put('/services/{service}', [ServiceController::class, 'update']);
    Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

    // v1 para Configuración - Agenda
    Route::get('/schedule-settings', [ScheduleSettingController::class, 'getSchedule']);
    Route::put('/schedule-settings', [ScheduleSettingController::class, 'updateSchedule']);

    /*
    |--------------------------------------------------------------------------
    | STAFF MEMBERS (Profesionales unificados)
    |--------------------------------------------------------------------------
    */

    Route::get('/staff-members', [StaffMemberController::class, 'index']);
    Route::post('/staff-members', [StaffMemberController::class, 'store']);
    Route::get('/staff-members/{staffMember}', [StaffMemberController::class, 'show']);
    Route::put('/staff-members/{staffMember}', [StaffMemberController::class, 'update']);
    Route::delete('/staff-members/{staffMember}', [StaffMemberController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | STAFF MEMBER - AGENDA
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/staff-members/{staffMember}/agenda',
        [StaffMemberAgendaController::class, 'show']
    );

    Route::put(
        '/staff-members/{staffMember}/agenda',
        [StaffMemberAgendaController::class, 'update']
    );

    /*
    |--------------------------------------------------------------------------
    | STAFF MEMBER - NON WORKING DAYS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/staff-members/{staffMember}/non-working-days',
        [StaffMemberNonWorkingDayController::class, 'index']
    );

    Route::post(
        '/staff-members/{staffMember}/non-working-days',
        [StaffMemberNonWorkingDayController::class, 'store']
    );

    Route::delete(
        '/staff-members/{staffMember}/non-working-days/{day}',
        [StaffMemberNonWorkingDayController::class, 'destroy']
    );

    /*
    |--------------------------------------------------------------------------
    | STAFF MEMBER - SCHEDULES (si los manejas separados)
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/staff-members/{staffMember}/schedules',
        [StaffMemberScheduleController::class, 'index']
    );

    Route::put(
        '/staff-members/{staffMember}/schedules',
        [StaffMemberScheduleController::class, 'update']
    );


    /*
    |--------------------------------------------------------------------------
    | CLIENTES
    |--------------------------------------------------------------------------
    */
    Route::get('/clients', [\App\Http\Controllers\Api\ClientController::class, 'index']);
    Route::get('/clients/list', [\App\Http\Controllers\Api\ClientController::class, 'list']); // select interno
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

        // Organización pública
        Route::get(
            '{organization:slug}',
            [\App\Http\Controllers\Api\PublicOrganizationController::class, 'show']
        );

        // Servicios públicos de la organización
        Route::get(
            '{organization:slug}/services',
            [\App\Http\Controllers\Api\PublicBookingController::class, 'services']
        );

        // Variantes del servicio
        Route::get(
            '{organization:slug}/services/{service}/variants',
            [\App\Http\Controllers\Api\PublicBookingController::class, 'variants']
        );

        // Staff disponible para esa variante
        Route::get(
            '{organization:slug}/service-variants/{variant}/staff',
            [\App\Http\Controllers\Api\PublicBookingController::class, 'staff']
        );

        // Disponibilidad de agenda - un día
        Route::get(
            '{organization:slug}/availability',
            [\App\Http\Controllers\Api\PublicBookingController::class, 'availability']
        );
        // Disponibilidad de agenda - varios días
        Route::get(
            '{organization:slug}/availability-range',
            [\App\Http\Controllers\Api\PublicBookingController::class, 'availabilityRange']
        );

        // Crear cita
        Route::post(
            '{organization:slug}/appointments',
            [\App\Http\Controllers\Api\PublicBookingController::class, 'store']
        )->middleware('throttle:booking');
        
        /*Route::post(
            '{organization:slug}/appointments',
            [\App\Http\Controllers\Api\PublicBookingController::class, 'store']
        )->middleware('throttle:30,1'); */

        /*Route::post('/appointments', [PublicAppointmentController::class, 'store'])
        ->middleware('throttle:5,1'); // 5 requests por minuto*/
    });
