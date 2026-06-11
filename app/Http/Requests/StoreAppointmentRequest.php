<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organization = $this->attributes->get('organization');

        return [

            'client_id' => [
                'required',
                'integer',
                'exists:clients,id'
            ],

            'pet_id' => [
                $organization?->business_niche === 'pet_grooming'
                    ? 'required'
                    : 'nullable',
                'integer'
            ],

            'staff_member_id' => [
                'required',
                'integer',
                'exists:staff_members,id'
            ],

            'branch_service_variant_id' => [
                'required',
                'integer',
                'exists:branch_service_variant,id'
            ],

            'start_datetime_local' => [
                'required',
                'date'
            ],

            'timezone' => [
                'required',
                'timezone'
            ],

            'mode' => [
                'required',
                'in:presential,online'
            ],

            'meeting_url' => [
                'nullable',
                'url'
            ],

            'meeting_provider' => [
                'nullable',
                'string',
                'max:50'
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000'
            ],

            'recurring' => [
                'nullable',
                'array'
            ],

            'recurring.frequency' => [
                'required_with:recurring',
                'in:daily,weekly,monthly'
            ],

            'recurring.interval' => [
                'required_with:recurring',
                'integer',
                'min:1',
                'max:52'
            ],

            'recurring.end_type' => [
                'required_with:recurring',
                'in:occurrences,date'
            ],

            'recurring.occurrences' => [
                'nullable',
                'integer',
                'min:1',
                'max:365'
            ],

            'recurring.end_date' => [
                'nullable',
                'date',
                'after_or_equal:today'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Debes seleccionar un cliente.',
            'client_id.exists' => 'El cliente seleccionado no existe.',

            'pet_id.required' => 'Debes seleccionar una mascota.',

            'staff_member_id.required' => 'Debes seleccionar un profesional.',
            'staff_member_id.exists' => 'El profesional seleccionado no existe.',

            'service_variant_id.required' => 'Debes seleccionar un servicio.',
            'service_variant_id.exists' => 'El servicio seleccionado no existe.',

            'start_datetime_local.required' => 'Debes seleccionar fecha y hora.',
            'start_datetime_local.date' => 'La fecha seleccionada no es válida.',

            'timezone.required' => 'La zona horaria es obligatoria.',
            'timezone.timezone' => 'La zona horaria no es válida.',

            'mode.required' => 'Debes seleccionar una modalidad.',
            'mode.in' => 'La modalidad seleccionada no es válida.',

            'meeting_url.url' => 'La URL de reunión no es válida.',

            'recurring.end_date.after_or_equal' =>
            'La fecha final de recurrencia no puede estar en el pasado.',
        ];
    }

    public function attributes(): array
    {
        return [
            'client_id' => 'cliente',
            'pet_id' => 'mascota',
            'staff_member_id' => 'profesional',
            'service_variant_id' => 'servicio',
            'start_datetime_local' => 'fecha y hora',
        ];
    }
}
