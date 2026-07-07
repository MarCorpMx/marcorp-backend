<?php 

namespace App\Http\Resources\PublicBooking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicBookingStaffMemberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [

            'id'=>$this->id,

            'name'=>$this->name,

            'title'=>$this->title,
            
            'specialty'=>$this->specialty,
            
            'bio'=>$this->bio,

            'avatar'=>$this->avatar,

        ];
    }
}