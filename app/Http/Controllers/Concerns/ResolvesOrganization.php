<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ResolvesOrganization
{
    protected function getOrganization(Request $request)
    {
        $organization = $request->user()?->currentOrganization();

        abort_if(
            !$organization,
            403,
            'No organization context.'
        );

        return $organization;
    }
}