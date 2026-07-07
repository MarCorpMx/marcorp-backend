<?php

namespace App\Http\Resources\PublicBooking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PublicBookingBranchCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Identity
            |--------------------------------------------------------------------------
            */

            'id' => $this->id,

            'name' => $this->name,

            'slug' => $this->slug,


            /*
            |--------------------------------------------------------------------------
            | Presentation
            |--------------------------------------------------------------------------
            */

            'tagline' => $this->tagline,

            'description_excerpt' => $this->description
                ? Str::limit($this->description, 90)
                : null,


            /*
            |--------------------------------------------------------------------------
            | Location
            |--------------------------------------------------------------------------
            */

            'location_label' => $this->show_address
                ? collect([
                    $this->city,
                    $this->state
                ])
                ->filter()
                ->implode(', ')
                : null,


            /*
            |--------------------------------------------------------------------------
            | Flags
            |--------------------------------------------------------------------------
            */

            'is_primary' => (bool) $this->is_primary,


            /*
            |--------------------------------------------------------------------------
            | Media
            |--------------------------------------------------------------------------
            */

            'logo_url' => $this->logo_url
        ];
    }
}
