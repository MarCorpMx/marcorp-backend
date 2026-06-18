<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        //$socialLinks = $this->social_links ?? [];

        $socialLinks = collect($this->social_links ?? [])
            ->map(fn($url) => $this->normalizeUrl($url))
            ->toArray();

        $this->merge([
            'website' => $this->normalizeUrl($this->website),
            'social_links' => $socialLinks,
        ]);
    }

    private function normalizeUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (!preg_match('/^https?:\/\//i', $url)) {
            return 'https://' . $url;
        }

        return $url;
    }

    public function rules(): array
    {
        $branchId = $this->route('branch')?->id;
        $organizationId = $this->attributes
            ->get('organization')
            ->id;

        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:120',
            ],

            'slug' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',

                Rule::unique('branches')
                    ->where(
                        fn($q) =>
                        $q->where('organization_id', $organizationId)
                    )
                    ->ignore($branchId),
            ],

            'reference_prefix' => [
                'required',
                'string',
                'min:2',
                'max:5',
                'regex:/^(?=.*[A-Z])[A-Z0-9]{2,5}$/',
            ],

            'tagline' => [
                'nullable',
                'string',
                'max:120',
            ],

            'description' => [
                'nullable',
                'string',
                'min:20',
                'max:500',
            ],

            'country' => [
                'required',
                'string',
                'max:100',
            ],

            'state' => [
                'nullable',
                'string',
                'max:100',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'zip_code' => [
                'nullable',
                'string',
                'max:20',
            ],

            'address' => [
                'nullable',
                'string',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'array',
            ],

            'whatsapp_phone' => [
                'nullable',
                'array',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'website' => [
                'nullable',
                'url',
                'max:255',
            ],

            'social_links' => [
                'nullable',
                'array',
            ],

            'social_links.instagram' => [
                'nullable',
                'url',
                'max:255',
            ],

            'social_links.facebook' => [
                'nullable',
                'url',
                'max:255',
            ],

            'social_links.tiktok' => [
                'nullable',
                'url',
                'max:255',
            ],

            'social_links.youtube' => [
                'nullable',
                'url',
                'max:255',
            ],

            'social_links.x' => [
                'nullable',
                'url',
                'max:255',
            ],

            'show_phone' => [
                'boolean',
            ],

            'show_whatsapp' => [
                'boolean',
            ],

            'show_email' => [
                'boolean',
            ],

            'show_address' => [
                'boolean',
            ],

            'show_website' => [
                'boolean',
            ],

            'show_social_links' => [
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [

            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'Mínimo 3 caracteres.',
            'name.max' => 'Máximo 120 caracteres.',

            'slug.required' => 'El enlace es obligatorio.',
            'slug.min' => 'Mínimo 3 caracteres.',
            'slug.max' => 'Máximo 50 caracteres.',
            'slug.regex' => 'Solo minúsculas, números y guiones.',
            'slug.unique' => 'El enlace ya está siendo utilizado.',

            'reference_prefix.required' => 'El prefijo es obligatorio.',
            'reference_prefix.min' => 'Mínimo 2 caracteres.',
            'reference_prefix.max' => 'Máximo 5 caracteres.',
            'reference_prefix.regex' => 'Solo mayúsculas y números.',

            'description.min' => 'La descripción debe tener al menos 20 caracteres.',
            'description.max' => 'La descripción no puede superar 500 caracteres.',

            'email.email' => 'Correo inválido.',

            'website.url' => 'Ingresa una URL válida.',

            'social_links.instagram.url' => 'Ingresa una URL válida para Instagram.',
            'social_links.facebook.url' => 'Ingresa una URL válida para Facebook.',
            'social_links.tiktok.url' => 'Ingresa una URL válida para TikTok.',
            'social_links.youtube.url' => 'Ingresa una URL válida para YouTube.',
            'social_links.x.url' => 'Ingresa una URL válida para X.',
        ];
    }
}
