<?php

namespace App\Services;

use App\Models\Branch;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;

class AppointmentTimezoneService
{
    /*
    |--------------------------------------------------------------------------
    | Convertir LOCAL branch -> UTC
    |--------------------------------------------------------------------------
    |
    | Ejemplo:
    | local: 2026-05-29 18:00:00
    | timezone: America/Mexico_City
    | resultado UTC: 2026-05-30 00:00:00
    |
    */
    public function localToUtc(
        string $localDatetime,
        string $timezone
    ): Carbon {

        return Carbon::parse(
            $localDatetime,
            $timezone
        )->utc();
    }

    /*
    |--------------------------------------------------------------------------
    | Convertir UTC -> LOCAL branch
    |--------------------------------------------------------------------------
    */
    public function utcToLocal(
        CarbonInterface|string $utcDatetime,
        string $timezone
    ): Carbon {

        return Carbon::parse($utcDatetime)
            ->setTimezone($timezone);
    }

    /*
    |--------------------------------------------------------------------------
    | Obtener timezone real de branch
    |--------------------------------------------------------------------------
    */
    public function resolveBranchTimezone(
        Branch $branch
    ): string {

        return $branch->timezone
            ?: config('app.timezone', 'UTC');
    }

    /*
    |--------------------------------------------------------------------------
    | Parsear datetime local desde date + time
    |--------------------------------------------------------------------------
    |
    | Recibe:
    | date = 2026-05-29
    | time = 18:00
    |
    | Devuelve:
    | 2026-05-29 18:00:00
    |
    */
    public function buildLocalDatetime(
        string $date,
        string $time
    ): string {

        return "{$date} {$time}:00";
    }

    /*
    |--------------------------------------------------------------------------
    | Crear rango UTC usando duración
    |--------------------------------------------------------------------------
    */
    public function buildUtcRange(
        string $date,
        string $time,
        int $durationMinutes,
        string $timezone
    ): array {

        $local = $this->buildLocalDatetime(
            $date,
            $time
        );

        $startUtc = $this->localToUtc(
            $local,
            $timezone
        );

        $endUtc = $startUtc
            ->copy()
            ->addMinutes($durationMinutes);

        return [
            'start_utc' => $startUtc,
            'end_utc' => $endUtc,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validar timezone
    |--------------------------------------------------------------------------
    */
    public function validateTimezone(
        string $timezone
    ): bool {

        return in_array(
            $timezone,
            timezone_identifiers_list()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validar y lanzar excepción
    |--------------------------------------------------------------------------
    */
    public function validateTimezoneOrFail(
        ?string $timezone
    ): void {

        if (
            !$timezone ||
            !$this->validateTimezone($timezone)
        ) {
            throw new Exception('invalid_timezone');
        }
    }
}
