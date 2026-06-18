<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\Subsystem;
use App\Models\Plan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

class PublicPlansController extends Controller
{

    public function getPlans(Request $request)
    {
        // Para consultar los planes de un subsistema
        if ($request->filled('subsystem')) {
            $request->validate(
                [
                    'subsystem' => 'required|string',
                ]
            );

            $subsystem = Subsystem::where('key', $request->subsystem)->firstOrFail();

            $plans = Plan::query()

                ->where('subsystem_id', $subsystem->id)
                ->where('is_active', true)
                ->where('is_visible', true)

                ->with([
                    'features' => function ($q) use ($subsystem) {
                        $q->where('subsystem_id', $subsystem->id)
                            ->where('is_enabled', true)

                            ->whereHas('feature', function ($feature) {
                                $feature->where('show_in_plans', true);
                            })

                            ->with('feature');
                        //->orderBy('sort_order');
                    }

                ])

                ->orderBy('sort_order')

                ->get();

            $plans->each(function ($plan) {

                $plan->setRelation(
                    'features',
                    $plan->features
                        ->sortBy(fn($f) => $f->feature->sort_order)
                        ->values()
                );
            });

            return response()->json(
                $plans->map(function ($plan) {
                    return [
                        'key' => $plan->key,
                        
                        'name' => $plan->name,
                        'description' => $plan->description,
                        
                        'original_price' => $plan->original_price,
                        'price' => $plan->price,
                        'billing_period' => $plan->billing_period,
                        
                        'is_limited' => $plan->is_limited,
                        'max_sales' => $plan->max_sales,
                        'sales_count' => $plan->sales_count,
                        
                        'trial_days' => $plan->trial_days,
                        
                        'support_level' => $plan->support_level,
                        'plan_type' => $plan->plan_type,
                        
                        'starts_at' => $plan->starts_at,
                        'ends_at' => $plan->ends_at,
                        
                        'metadata' => $plan->metadata,

                        'features' => $plan->features->map(function ($f) {
                            return [
                                'key' => $f->feature->key,
                                'name' => $f->feature->name,
                                'enabled' => (bool) $f->is_enabled,
                                'limit_type' => $f->limit_type,
                                'limit' => $f->limit_value,
                            ];
                        })->values()
                    ];
                })
            );
        } else {
            // Para consultar todos los planes
            return response()->json([
                'message' => 'Se necesita implementación para regresar todos los planes'
            ], 400);
        }
    }
}
