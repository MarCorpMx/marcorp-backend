<?php

namespace App\Services;

use App\Models\Branch;

class BranchProfileCompletionService
{
    public function calculate(Branch $branch): array
    {
        $completed = 0;

        $missing = [];

        $fields = [

            'name' => filled($branch->name),
            'slug' => filled($branch->slug),
            'reference_prefix' => filled($branch->reference_prefix),
            'tagline' => filled($branch->tagline),
            'description' => filled($branch->description),

            //'phone' => !empty($branch->phone),
            //'whatsapp_phone' => !empty($branch->whatsapp_phone),
            'phone' => filled($branch->phone),
            'whatsapp_phone' => filled($branch->whatsapp_phone),

            'email' => filled($branch->email),
            'website' => filled($branch->website),
            'country' => filled($branch->country),
            'city' => filled($branch->city),
            'address' => filled($branch->address),
            'social_links' => $this->hasSocialLinks($branch),
            'logo_url' => filled($branch->logo_url),

        ];

        foreach ($fields as $field => $isCompleted) {

            if ($isCompleted) {
                $completed++;
            } else {
                $missing[] = [
                    'field' => $field,
                    'label' => $this->labels[$field],
                ];
            }
        }

        $total = count($fields);

        $percentage = (int) round(
            ($completed / $total) * 100
        );

        return [
            'percentage' => $percentage,
            'completed' => $completed,
            'total' => $total,
            'level' => $this->getLevelData($percentage),
            'missing' => $missing,
        ];
    }

    private function hasSocialLinks(Branch $branch): bool
    {
        if (empty($branch->social_links)) {
            return false;
        }

        return collect($branch->social_links)
            ->filter(fn($value) => filled($value))
            ->isNotEmpty();
    }

    private array $labels = [
        'name' => 'Nombre',
        'slug' => 'Enlace público',
        'reference_prefix' => 'Prefijo',
        'tagline' => 'Frase principal',
        'description' => 'Descripción',
        'phone' => 'Teléfono',
        'whatsapp_phone' => 'WhatsApp',
        'email' => 'Correo',
        'website' => 'Sitio web',
        'country' => 'País',
        'city' => 'Ciudad',
        'address' => 'Dirección',
        'social_links' => 'Redes sociales',
        'logo_url' => 'Logo de la organización',
    ];

    private function getLevelData(int $percentage): array
    {
        return match (true) {

            $percentage <= 30 => [
                'key' => 'starter',
                'label' => 'Perfil inicial',
            ],

            $percentage <= 60 => [
                'key' => 'growing',
                'label' => 'Perfil en crecimiento',
            ],

            $percentage <= 90 => [
                'key' => 'professional',
                'label' => 'Perfil profesional',
            ],

            default => [
                'key' => 'optimized',
                'label' => 'Perfil optimizado',
            ],
        };
    }
}
