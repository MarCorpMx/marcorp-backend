<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subsystem;

class SubsystemController extends Controller
{
    public function index()
    {
        return response()->json(
            //Subsystem::where('is_active', true)->get()
            Subsystem::where('is_active', true)
            ->where('is_selectable', true)
            ->get()
        );

        
    }
}
