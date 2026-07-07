<?php

namespace App\Http\Resources\PublicBooking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\PublicBooking\PublicBookingBranchServiceVariantResource;

class PublicBookingBranchServiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [

            'id'=>$this->id,

            'name'=>$this->name,

            'description'=>$this->description,

            'color'=>$this->color,

            'variants'=>
                PublicBookingBranchServiceVariantResource::collection(
                    $this->variants
                )

        ];
    }
}