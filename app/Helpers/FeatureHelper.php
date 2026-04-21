<?php 

namespace App\Helpers;

use App\Models\Organization;
use App\Services\FeatureService;

class FeatureHelper
{
    public static function get(Organization $org, string $key): array
    {
        [$subsystem, $feature] = explode('.', $key);

        return app(FeatureService::class)
            ->get($org, $subsystem, $feature);
    }
}