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
            //'organization_slug' => 'required|exists:organizations,slug',
            'organization_slug' => 'required|string|max:150',

            'first_name'  => 'required|string|max:250',
            'last_name'   => 'nullable|string|max:100',
            'email'       => 'required|email:rfc,dns|max:150',

            'business_name'  => 'nullable|string|max:150',

            'subject'     => 'nullable|string|max:150',

            'phone'       => 'nullable|array',

            'services'    => 'nullable|array',
            'services.*'  => 'string|max:100',

            'custom_fields'  => 'nullable|array',

            'source'         => 'nullable|string|max:50',

            'message'     => 'required|string|min:10',
        ];
    }

    public function attributes(): array
    {
        return [
            'organization_slug' => 'organización',
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'email' => 'correo electrónico',
            'business_name' => 'nombre del negocio',
            'subject' => 'asunto',
            'phone' => 'teléfono',
            'services' => 'servicios',
            'message' => 'mensaje',
            'source' => 'origen',
        ];
    }

    public function messages(): array
    {
        return [

            'required' => 'El campo :attribute es obligatorio.',
            'email' => 'Ingresa un correo electrónico válido.',
            'exists' => 'La organización seleccionada no existe.',

            'min' => [
                'string' => 'El campo :attribute debe contener al menos :min caracteres.',
            ],

            'max' => [
                'string' => 'El campo :attribute no puede exceder :max caracteres.',
            ],

            'string' => 'El campo :attribute debe ser texto válido.',
            'array' => 'El campo :attribute tiene un formato inválido.',
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
                'message' => 'Por favor corrige los campos marcados e inténtalo nuevamente.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
