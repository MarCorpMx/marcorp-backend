<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreContactMessageRequest extends FormRequest
{

    protected function prepareForValidation(): void
    {
        \Illuminate\Support\Facades\App::setLocale('es');
    }
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_slug' => 'required|exists:organizations,slug',

            'first_name'  => 'required|string|max:250',
            'last_name'   => 'nullable|string|max:100',
            'email'       => 'required|email:rfc,dns|max:150',

            'subject'     => 'nullable|string|max:150',

            'phone'       => 'nullable|array',
            'services'    => 'nullable|array',
            'services.*'  => 'string|max:100',

            'message'     => 'required|string|min:10',
        ];
    }

    /**
     * Fuerza respuesta JSON y evita redirects a /
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'ok'     => false,
                'message' => 'Hay errores de validación.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
