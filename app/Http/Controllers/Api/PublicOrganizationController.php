<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Http\Resources\PublicOrganizationResource;

class PublicOrganizationController extends Controller
{
    public function show(Organization $organization)
    {
        return new PublicOrganizationResource($organization);
    }
}
