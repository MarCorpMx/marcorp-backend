<?php

use Illuminate\Support\Facades\Route;

use App\Models\Organization;
use App\Services\OrganizationMailService;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-mail/{slug}', function ($slug, OrganizationMailService $mailService) {

    return "todo ok";
    $organization = Organization::where('slug', $slug)->firstOrFail();

    $mailService->sendTemplate(
        $organization,
        'contact_auto_reply',
        'omar.marcorp@gmail.com', // <-- CAMBIA ESTO
        //'omar.lawliet90@gmail.com',
        [
            'name' => 'Omar Test',
        ]
    );

    return "Correo enviado desde {$organization->name}";
});
