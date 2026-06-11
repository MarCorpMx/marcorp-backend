<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OnboardingController;

use App\Http\Controllers\Api\OrganizationController;

use App\Http\Controllers\Api\MeController; // rombi falta verificar funciones
use App\Http\Controllers\Api\BranchController;

use App\Http\Controllers\Api\DashboardController;

use App\Http\Controllers\Api\AppointmentController;

use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\ServiceController;

use App\Http\Controllers\Api\StaffMemberController;
use App\Http\Controllers\Api\StaffMemberAgendaController;
use App\Http\Controllers\Api\StaffMemberNonWorkingDayController;
use App\Http\Controllers\Api\StaffMemberScheduleController;

use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\ClientController;

use App\Http\Controllers\Api\NotificationController;



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
/*Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
    });
});*/

Route::prefix('auth')->group(function () {

    // Registro
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1'); // 5 intentos por minuto

    // Login (IMPORTANTE proteger)
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    // Reenvío de verificación (PÚBLICO)
    Route::post('/resend-verification', [OnboardingController::class, 'resendPublic'])
        ->middleware('throttle:3,1'); // más agresivo

    // Autenticados
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // Reenvío autenticado
        Route::post('/onboarding/resend-verification', [OnboardingController::class, 'resendAuthenticated'])
            ->middleware('throttle:5,1');
    });
});

Route::middleware('auth:sanctum')
    ->prefix('onboarding')
    ->group(function () {

        // Estado actual (LA MAGIA DEL MAGO OSCURO)
        Route::get('/status', [OnboardingController::class, 'status']);

        // Avanzar paso manualmente (cuando completes forms)
        Route::post('/advance', [OnboardingController::class, 'advance']); // rombi - no se utiliza

        // (Opcional) marcar onboarding completo
        Route::post('/complete', [OnboardingController::class, 'complete']);
    });


/*
|--------------------------------------------------------------------------
| ME (contexto del usuario autenticado)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'organization', 'branch', 'subsystem'])->prefix('me')->group(function () {

    Route::get('/', [MeController::class, 'index']); // rombi
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

    /*
    |--------------------------------------------------------------------------
    | BRANCHES (Sucursales de la organización)
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'DataDashboard']);


    /*
    |--------------------------------------------------------------------------
    | Organización
    |--------------------------------------------------------------------------
    */
    Route::get('/organization', [OrganizationController::class, 'organization']);
    Route::put('/organization', [OrganizationController::class, 'updateOrganization']); // rombi - onboarding
    Route::post('/organization/logo', [OrganizationController::class, 'uploadLogo']);

    /*
    |--------------------------------------------------------------------------
    | BRANCHES (Sucursales de la organización)
    |--------------------------------------------------------------------------
    */
    Route::get('/branches', [BranchController::class, 'index']);
    Route::post('/branches', [BranchController::class, 'store']);
    Route::get('/branches/{branch}', [BranchController::class, 'show']);
    Route::put('/branches/{branch}', [BranchController::class, 'update']);
    Route::delete('/branches/{branchId}', [BranchController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | TEAM (admins, staff, recepcionistas, invitados)
    |--------------------------------------------------------------------------
    */
    Route::get('/team', [TeamController::class, 'index']); // rombi
    Route::post('/team', [TeamController::class, 'store']); // rombi
    Route::put('/team/{id}', [TeamController::class, 'update']); // rombi - sin uso aun
    // Activa / Desactiva a miembro del equipo
    Route::patch('/team/{id}/toggle-access', [TeamController::class, 'toggleAccess']);
    //Route::post('/team/{id}/suspend', [TeamController::class, 'suspend']);
    //Route::post('/team/{id}/activate', [TeamController::class, 'activate']);


    /*
    |--------------------------------------------------------------------------
    | APPOINTMENTS (CITAS DEL USUARIO AUTENTICADO)
    |--------------------------------------------------------------------------
    */
    Route::get('/appointments', [\App\Http\Controllers\Api\AppointmentController::class, 'index']);

    // Crear la cita michelle
    Route::post('/appointments', [AppointmentController::class, 'store']);


    Route::get('/appointments/{appointment}', [\App\Http\Controllers\Api\AppointmentController::class, 'show']);
    Route::put('/appointments/{appointment}', [\App\Http\Controllers\Api\AppointmentController::class, 'update']);
    Route::delete('/appointments/{appointment}', [\App\Http\Controllers\Api\AppointmentController::class, 'destroy']);
    Route::patch(
        '/appointments/{appointment}/status',
        [\App\Http\Controllers\Api\AppointmentController::class, 'updateStatus']
    );


    /*
    |--------------------------------------------------------------------------
    | SERVICES (Servicios de la organización)
    |--------------------------------------------------------------------------
    */
    Route::get('/services', [ServiceController::class, 'index']); // Administración
    //Route::get('/services/list', [ServiceController::class, 'list']); // select interno

    // Selector interno (Citas, Configuración Horario de Atención)
    Route::get('/service-variants/list', [ServiceController::class, 'listVariants']);

    //Route::get('/my-services', [ServiceController::class, 'my-services']); // servicios del staff autenticado

    // Cargar staff dependiendo de la variante seleccionada (Citas)
    Route::get('/service-variants/{variantId}/staff', [ServiceController::class, 'staff']);
    // /public/services → web pública

    Route::post('/services', [ServiceController::class, 'store']);
    Route::patch('/services/{service}/status', [ServiceController::class, 'updateStatus']);
    //Route::get('/services/{service}', [ServiceController::class, 'show']);

    // Actualizar servicio con variantes
    Route::put('/services/{service}', [ServiceController::class, 'update']);
    //Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

    // Cambiar estatus de una variante de servicio
    Route::patch('/service-variants/{variant}/status', [ServiceController::class, 'updateVariantStatus']);
    // Subir imagen
    Route::post('/service-variants/{variant}/image', [ServiceController::class, 'uploadImage']);
    // Eliminar imagen
    Route::delete('/service-variants/{variant}/image', [ServiceController::class, 'deleteImage']);

    // v1 para Configuración - Agenda
    Route::get('/schedule-settings', [ScheduleSettingController::class, 'getSchedule']);
    Route::put('/schedule-settings', [ScheduleSettingController::class, 'updateSchedule']);

    /*
    |--------------------------------------------------------------------------
    | STAFF MEMBERS (Profesionales unificados)
    |--------------------------------------------------------------------------
    */
    Route::get('/staff-members', [StaffMemberController::class, 'index']);
    //Route::post('/staff-members', [StaffMemberController::class, 'store']);
    //Route::get('/staff-members/{staffMember}', [StaffMemberController::class, 'show']);
    //Route::put('/staff-members/{staffMember}', [StaffMemberController::class, 'update']);
    //Route::delete('/staff-members/{staffMember}', [StaffMemberController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | STAFF MEMBERS (Servicios)
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/staff-members/{staffMember}/service-variants',
        [StaffMemberController::class, 'serviceVariants']
    );
    Route::put(
        '/staff-members/{staffMember}/service-variants',
        [StaffMemberController::class, 'syncServiceVariants']
    );

    /*
    |--------------------------------------------------------------------------
    | STAFF MEMBER - AGENDA
    |--------------------------------------------------------------------------
    */
    // CONFIGURACIÓN
    Route::get(
        '/staff-members/{staffMember}/agenda',
        [StaffMemberAgendaController::class, 'show']
    );

    Route::put(
        '/staff-members/{staffMember}/agenda',
        [StaffMemberAgendaController::class, 'update']
    );

    // BLOQUEOS MANUALES
    Route::post(
        '/staff-members/{staffMember}/blocked-slots',
        [StaffMemberAgendaController::class, 'storeBlock']
    );

    Route::put(
        '/staff-members/{staffMember}/blocked-slots/{block}',
        [StaffMemberAgendaController::class, 'updateBlock']
    );

    Route::delete(
        '/staff-members/{staffMember}/blocked-slots/{block}',
        [StaffMemberAgendaController::class, 'deleteBlock']
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

    /*Route::post(
        '/staff-members/{staffMember}/non-working-days',
        [StaffMemberNonWorkingDayController::class, 'store']
    );*/

    /*Route::delete(
        '/staff-members/{staffMember}/non-working-days/{day}',
        [StaffMemberNonWorkingDayController::class, 'destroy']
    );*/

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
    | NOTIFICATIONS (reglas para las notificaciones)
    |--------------------------------------------------------------------------
    */
    Route::get('/notification-rules', [NotificationController::class, 'index']);
    Route::patch('/notification-rules/{event}/{channel}', [NotificationController::class, 'toggle']);
    Route::patch('/notification-rules/{event}/{channel}/recipients', [NotificationController::class, 'updateRecipients']);


    /*
    |--------------------------------------------------------------------------
    | CLIENTES
    |--------------------------------------------------------------------------
    */
    Route::get('/clients', [ClientController::class, 'index']);
    // Select interno (consultar la lista de clientes activos y no bloqueados)
    Route::get('/clients/list', [ClientController::class, 'list']);
    // Crear cliente (con sus mascotas de ser el caso)
    Route::post('/clients', [ClientController::class, 'store']);
    // Crear cliente rápido (para módulo de citas)
    Route::post('/clients/quick-create', [ClientController::class, 'quickCreate']);
    // Mostrar detalle de cliente
    Route::get('/clients/{client}', [ClientController::class, 'show']);
    // Actualizar cliente
    Route::put('/clients/{client}', [ClientController::class, 'update']);
    // Elimnar cliente (soft delete o force delete dependiendo el caso)
    Route::delete('/clients/{client}', [ClientController::class, 'destroy']);
    // Actualizar estatus
    Route::patch('/clients/{clientId}/status', [ClientController::class, 'updateStatus']);
    // Bloquear / Desbloquear
    Route::patch('/clients/{clientId}/block', [ClientController::class, 'updateBlock']);
    // Buscar las mascotas de un cliente
    Route::get('/clients/{client}/pets', [ClientController::class, 'pets']);

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
        /*Route::get(
            '{organization:slug}/services/{service}/variants',
            [\App\Http\Controllers\Api\PublicBookingController::class, 'variants']
        );*/

        // Staff disponible para esa variante
        /*Route::get(
            '{organization:slug}/service-variants/{variant}/staff',
            [\App\Http\Controllers\Api\PublicBookingController::class, 'staff']
        );*/

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

        // Confirmar/Cancelar desde mail - organización
        Route::post(
            '/appointment-actions/{token}',
            [\App\Http\Controllers\Api\AppointmentActionController::class, 'handle']
        );

        // Obtener cita por reference_code (manage [booking-public])
        Route::get(
            'appointments/manage/{reference_code}',
            [\App\Http\Controllers\Api\PublicAppointmentManageController::class, 'show']
        );

        // Cancelar cita por refrence_code (manage [booking-public])
        Route::post(
            'appointments/manage/{reference_code}/cancel',
            [\App\Http\Controllers\Api\PublicAppointmentManageController::class, 'cancel']
        );

        // Reagendar cita por refrence_code (manage [booking-public])
        Route::post(
            'appointments/manage/{reference_code}/reschedule',
            [\App\Http\Controllers\Api\PublicAppointmentManageController::class, 'reschedule']
        );
    });
