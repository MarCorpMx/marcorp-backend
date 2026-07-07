<?php 

namespace App\Http\Resources\PublicBooking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\PublicBooking\PublicBookingStaffMemberResource;


class PublicBookingBranchServiceVariantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [

            'id'=>$this->id,

            'name'=>$this->name,

            'description'=>$this->description,

            'duration_minutes'=>$this->duration_minutes,

            'price'=>$this->price,

            'mode'=>$this->mode,

            'image_url'=>$this->image_url,

            'staff_members_count'=>
                $this->staffMembers->count(),

            'requires_staff_selection'=>
                $this->staffMembers->count() > 1,

            'default_staff_member'=>

                $this->staffMembers->count() === 1

                    ? new PublicBookingStaffMemberResource(
                        $this->staffMembers->first()
                    )

                    : null,

            'staff_members'=>
                PublicBookingStaffMemberResource::collection(
                    $this->staffMembers
                )

        ];
    }
}