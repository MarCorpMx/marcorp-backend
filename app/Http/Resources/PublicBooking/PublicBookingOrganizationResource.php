<?php

namespace App\Http\Resources\PublicBooking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicBookingOrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'slogan' => $this->slogan,

            'business_niche' => $this->business_niche,

            'status' => $this->status,
            'online_booking_enabled' => $this->online_booking_enabled,
            'online_booking_disabled_message' => $this->online_booking_disabled_message,

            'onboarding_completed_at' => $this->onboarding_completed_at,

            'logo_url' => $this->logo_url,

            'theme_key' => $this->theme_key,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'white_label' => $this->white_label,

            'rating' => data_get($this->metadata, 'rating'),
            'reviews_count' => data_get($this->metadata, 'reviews_count'),

            'timezone' => $this->timezone,

        ];
    }
}
