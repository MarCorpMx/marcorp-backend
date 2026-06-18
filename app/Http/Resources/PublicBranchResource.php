<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicBranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'name' => $this->name,

            'tagline' => $this->tagline,

            'description' => $this->description,

            /*'logo_url' => $this->logo_url
                ? url(Storage::url($this->logo_url))
                : null,*/

            'phone' => $this->show_phone
                ? $this->phone
                : null,

            'whatsapp_phone' => $this->show_whatsapp
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
                : [],

        ];
    }
}
