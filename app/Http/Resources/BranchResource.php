<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            // General
            'name' => $this->name,
            'slug' => $this->slug,
            'reference_prefix' => $this->reference_prefix,
            'tagline' => $this->tagline,
            'description' => $this->description,

            // Location
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'zip_code' => $this->zip_code,
            'address' => $this->address,

            // Contact
            'phone' => $this->phone,
            'whatsapp_phone' => $this->whatsapp_phone,
            'email' => $this->email,
            'website' => $this->website,

            // Social
            'social_links' => $this->social_links,

            // Visibility
            'show_phone' => (bool) $this->show_phone,
            'show_whatsapp' => (bool) $this->show_whatsapp,
            'show_email' => (bool) $this->show_email,
            'show_address' => (bool) $this->show_address,
            'show_website' => (bool) $this->show_website,
            'show_social_links' => (bool) $this->show_social_links,

            // System
            'is_active' => (bool) $this->is_active,
            'is_primary' => (bool) $this->is_primary,
            'locked_by_plan' => (bool) $this->locked_by_plan,

            // Estado
            'is_blocked' => (bool) $this->is_blocked,
            'blocked_reason' => $this->blocked_reason,
            'blocked_at' => $this->blocked_at,

            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

            // branding
            'theme_key' => $this->theme_key,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'logo_url' => $this->logo_url,
            'white_label' => (bool) $this->white_label,

            // dominio
            'primary_domain' => $this->primary_domain,
            'domains' => $this->domains,
            'force_https' => (bool) $this->force_https,

            // configuración
            'timezone' => $this->timezone,
            'metadata' => $this->metadata,

            // auditoría
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
