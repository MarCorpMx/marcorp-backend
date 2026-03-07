<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'start' => $this->start_datetime->toIso8601String(),
            'end'   => $this->end_datetime->toIso8601String(),

            'date' => $this->start_datetime->format('Y-m-d'),
            'time' => $this->start_datetime->format('H:i'),

            'client' => [
                'id' => $this->client?->id,
                'name' => $this->client?->full_name ?? $this->client?->name,
            ],

            'service' => [
                'id' => $this->serviceVariant?->id,
                'main' => [
                    'id' => $this->serviceVariant?->service?->id,
                    'name' => $this->serviceVariant?->service?->name,
                    'color' => $this->serviceVariant?->service?->color,
                ],
                'variant' => [
                    'name' => $this->serviceVariant?->name,
                    'duration' => $this->serviceVariant?->duration_minutes,
                    'price' => $this->serviceVariant?->price,
                ],
            ],

            'staff' => [
                'id' => $this->staff?->id,
                'name' => $this->staff?->name,
            ],

            'status' => $this->status,
        ];
    }
}
