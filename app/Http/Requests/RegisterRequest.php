<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'   => 'required|string|min:3|max:100',
            //'last_name'    => 'required|string|min:2|max:100',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|min:8',
            'subsystem'   => 'required|string|min:2|max:20',
            //'phone'        => 'required',
            //'subsystem_id' => 'required|exists:subsystems,id'
        ];
    }
}
