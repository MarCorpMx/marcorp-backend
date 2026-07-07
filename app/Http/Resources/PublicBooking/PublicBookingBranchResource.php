<?php

namespace App\Http\Resources\PublicBooking;

use App\Services\LocationService;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicBookingBranchResource extends JsonResource
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

            'tagline' => $this->tagline,

            'description' => $this->description,

            'is_primary' => (bool) $this->is_primary,


            /*
            |--------------------------------------------------------------------------
            | Location
            |--------------------------------------------------------------------------
            */

            'location' => [

                'country' => $this->country,

                'state' => $this->state,

                'city' => $this->city,

                'address' => $this->show_address
                    ? $this->address
                    : null,

                'maps_url' => $this->show_address
                    ? app(LocationService::class)
                    ->buildGoogleMapsUrl($this->resource)
                    : null,
            ],


            /*
            |--------------------------------------------------------------------------
            | Contact
            |--------------------------------------------------------------------------
            */

            'contact' => [

                'phone' => $this->show_phone
                    ? $this->phone
                    : null,

                'whatsapp' => $this->show_whatsapp
                    ? $this->whatsapp_phone
                    : null,

                'email' => $this->show_email
                    ? $this->email
                    : null,

                'website' => $this->show_website
                    ? $this->website
                    : null,

                'social_links' => $this->show_social_links
                    ? $this->social_links
                    : null,
            ],


            /*
            |--------------------------------------------------------------------------
            | Branding
            |--------------------------------------------------------------------------
            */

            'branding' => [

                'logo_url' => $this->logo_url,

                'primary_color' => $this->primary_color,

                'secondary_color' => $this->secondary_color,
            ],

            /*
            |--------------------------------------------------------------------------
            | Metadata
            |--------------------------------------------------------------------------
            */

            "stats" => [

                'rating' => data_get($this->metadata, 'rating'),
                
                'reviews_count' => data_get($this->metadata, 'reviews_count'),
            ],

            'metadata' => $this->metadata,

        ];
    }
}
