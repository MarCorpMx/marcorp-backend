<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
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
            'first_name'   => 'required|string|min:3|max:100',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|min:8',
            'subsystem'   => 'required|string|min:2|max:20',
            'accept_terms' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'nombre',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'accept_terms' => 'condiciones de uso',

        ];
    }

    public function messages(): array
    {
        return [

            'required' => 'El campo :attribute es obligatorio.',
            //'email' => 'Ingresa un correo electrónico válido.',
            'exists' => 'La organización seleccionada no existe.',

            'min' => [
                'string' => 'El campo :attribute debe contener al menos :min caracteres.',
            ],

            'max' => [
                'string' => 'El campo :attribute no puede exceder :max caracteres.',
            ],

            'string' => 'El campo :attribute debe ser texto válido.',
            'array' => 'El campo :attribute tiene un formato inválido.',

            'accepted' => 'Debes aceptar los términos para continuar',
        ];
    }

    /**
     * Fuerza respuesta JSON y evita redirects a /
     */
    /*protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'ok'     => false,
                'message' => 'Por favor corrige los campos marcados e inténtalo nuevamente.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }*/
}
