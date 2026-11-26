<?php

namespace App\Services;

use App\Models\DailyCost;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

/**
 * Service for auto-generating unique no_surat_jalan (Surat Jalan) numbers.
 *
 * Formats:
 * - Trips: SJ-YYYYMMDD-NXN (e.g., SJ-20261126-001)
 * - Daily Costs: DC-YYYYMMDD-NXN (e.g., DC-20261126-001)
 */
class NoSuratJalanGenerator
{
    /**
     * Generate a unique no_surat_jalan for a Trip.
     */
    public function generateForTrip(?string $prefix = null): string
    {
        $date = now()->format('Ymd');
        $prefix = $prefix ?? 'SJ';

        return $this->generateUnique($prefix, $date, Trip::class);
    }

    /**
     * Generate a unique no_surat_jalan for a DailyCost.
     */
    public function generateForDailyCost(?string $prefix = null): string
    {
        $date = now()->format('Ymd');
        $prefix = $prefix ?? 'DC';

        return $this->generateUnique($prefix, $date, DailyCost::class);
    }

    /**
     * Generate a unique no_surat_jalan with the given prefix and date.
     *
     * @param string $prefix The prefix (e.g., 'SJ' for trips, 'DC' for daily costs)
     * @param string $date The date in YYYYMMDD format
     * @param class-string<Trip|DailyCost> $modelClass The model class to check for uniqueness
     */
    protected function generateUnique(string $prefix, string $date, string $modelClass): string
    {
        // Find the highest sequence number for today
        $table = $modelClass === Trip::class ? 'trips' : 'daily_costs';

        $maxSequence = DB::table($table)
            ->where('no_surat_jalan', 'like', "{$prefix}-{$date}-%")
            ->max(
                DB::raw("CAST(SUBSTRING(no_surat_jalan, LENGTH('{$prefix}-{$date}-') + 1) AS UNSIGNED)")
            );

        $sequence = ($maxSequence ?? 0) + 1;
        $sequencePadded = str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$sequencePadded}";
    }

    /**
     * Generate or return existing no_surat_jalan.
     *
     * @param string|null $noSuratJalan The provided no_surat_jalan (may be empty)
     * @param string $type The type: 'trip' or 'daily_cost'
     * @return string The no_surat_jalan (either existing or auto-generated)
     */
    public function resolve(?string $noSuratJalan, string $type): string
    {
        $noSuratJalan = strtoupper(trim((string) ($noSuratJalan ?? '')));

        if ($noSuratJalan !== '') {
            return $noSuratJalan;
        }

        return match ($type) {
            'trip' => $this->generateForTrip(),
            'daily_cost' => $this->generateForDailyCost(),
            default => $this->generateForTrip(),
        };
    }
}
