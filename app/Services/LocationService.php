<?php

namespace App\Services;

use App\Models\Branch;

class LocationService
{
    public function buildBranchAddress(Branch $branch): ?string
    {
        return collect([
            $branch->address,
            $branch->city,
            $branch->state,
            $branch->country,
        ])
            ->filter()
            ->implode(', ');
    }

    public function buildGoogleMapsUrl(Branch $branch): ?string
    {
        $address = $this->buildBranchAddress($branch);

        if (!$address) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query=' .
            urlencode($address);
    }

    public function buildGoogleMapsDirectionsUrl(Branch $branch): ?string
    {
        $address = $this->buildBranchAddress($branch);

        if (!$address) {
            return null;
        }

        return 'https://www.google.com/maps/dir/?api=1&destination=' .
            urlencode($address);
    }

    // LocationService::resolveCoordinates($branch);
    //hasCoordinates();
    //buildCoordinatesUrl()
    

}
