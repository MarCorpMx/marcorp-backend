<?php

namespace App\Services;

use App\Models\Subsystem;
use Illuminate\Support\Facades\Cache;

class SubsystemResolver
{
    /**
     * Cache en memoria (por request)
     */
    protected array $localCache = [];

    /**
     * Resolver por key (web, citas, etc)
     */
    public function resolve(?string $key): ?int
    {
        if (!$key) {
            return null;
        }

        // cache en memoria (rápido)
        if (isset($this->localCache[$key])) {
            return $this->localCache[$key];
        }

        // cache persistente (opcional pero pro)
        $id = Cache::remember("subsystem:{$key}", 3600, function () use ($key) {
            return Subsystem::where('key', $key)
                ->where('is_active', true)
                ->value('id');
        });

        // guardar en memoria
        $this->localCache[$key] = $id;

        return $id;
    }
}
