<?php

namespace App\Http\Controllers\Api;

use App\Services\GeminiService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MindfulnessController extends Controller
{
    public function __construct(
        protected GeminiService $gemini
    ) {}

    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'mood' => 'required|string|max:500',
        ]);

        return response()->json([
            'response' => $this->gemini
                ->getMindfulnessResponse($data['mood']),
        ]);
    }
}
