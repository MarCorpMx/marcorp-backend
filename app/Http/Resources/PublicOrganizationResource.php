<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PublicOrganizationResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,

            'logo_url' => $this->logo_url
                ? url(Storage::url($this->logo_url))
                : null,

            'phone' => $this->phone,
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'zip_code' => $this->zip_code,
            'address' => $this->address,

            'theme_key' => $this->theme_key,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,

            'timezone' => $this->timezone,

            'rating' => data_get($this->metadata, 'rating'),
            'reviews_count' => data_get($this->metadata, 'reviews_count'),

            'plan' => 'pro',
        ];
    }
}
