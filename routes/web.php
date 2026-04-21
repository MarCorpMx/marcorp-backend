<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Models\Organization;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {

    // 1. Validar firma
    if (! URL::hasValidSignature($request)) {
        return redirect(config('services.citara.front_url') . '/onboarding/email-expired');
    }
    
    // 2. Buscar usuario
    $user = User::findOrFail($id);

    // 3. Validar hash
    if (! hash_equals((string) $hash, sha1($user->email))) {
        return redirect(config('services.citara.front_url') . '/onboarding/email-expired');
    }

    // 4. Verificar email
    if (! $user->hasVerifiedEmail()) {

        $user->markEmailAsVerified();

        // 5. Avanzar onboarding
        $organization = $user->organizations()->first();

        if ($organization && $organization->onboarding_step === 'email_pending') {
            $organization->update([
                'onboarding_step' => 'business_setup'
            ]);
        }

        $organization->advanceOnboarding(Organization::ONBOARDING_BUSINESS_SETUP);

        // 6. Enviar correo de confirmación
        app(\App\Services\NotificationService::class)->trigger(
            type: 'auth_email_verified',
            data: [
                'name' => $user->first_name,
                'onboarding_url' => config('services.citara.front_url') . '/onboarding'
            ],
            organization: $organization,
            recipient: $user->email,
            recipientName: $user->first_name,
            notifiable: $user,
        );
    }

    // 7. Redirigir a Angular
    return redirect(config('services.citara.front_url') . '/onboarding');

})->name('verification.verify');

//})->middleware(['signed'])->name('verification.verify');
