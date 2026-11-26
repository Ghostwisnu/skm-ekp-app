<?php

namespace App\Services;

use App\Models\DailyCost;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

/**
 * Service to auto-generate unique nomor surat jalan (no_sj) 
 * when not provided during trip creation or import.
 */
class NoSuratJalanGenerator
{
    /**
     * Cache for tracking generated numbers during import.
     */
    protected static array $generatedNumbers = [];

    /**
     * Generate a unique no_surat_jalan for a given date and vehicle plate.
     * Format: SJ-YYYYMMDD-NXN (e.g., SJ-20261126-001)
     */
    public static function generate(string $tanggal, string $plat, ?int $importCounter = null): string
    {
        $date = is_string($tanggal) ? $tanggal : $tanggal->format('Y-m-d');
        $dateFormatted = \Carbon\Carbon::parse($date)->format('Ymd');
        
        $prefix = "SJ-{$dateFormatted}";
        
        // Use import counter if provided (for batch imports without explicit no_surat_jalan)
        if ($importCounter !== null) {
            return "{$prefix}-" . str_pad($importCounter, 3, '0', STR_PAD_LEFT);
        }
        
        // Find the highest sequence number for this date in trips and daily_costs
        $maxSequence = DB::table('trips')
            ->where('no_surat_jalan', 'like', $prefix . '-%')
            ->orderByRaw("SUBSTRING(no_surat_jalan, LENGTH(?) + 2) DESC")
            ->value('no_surat_jalan');
        
        // Also check daily_costs to avoid duplicates
        $maxSequenceDaily = DB::table('daily_costs')
            ->where('no_surat_jalan', 'like', $prefix . '-%')
            ->orderByRaw("SUBSTRING(no_surat_jalan, LENGTH(?) + 2) DESC")
            ->value('no_surat_jalan');
        
        $maxOverall = 0;
        if ($maxSequence) {
            $parts = explode('-', $maxSequence);
            $maxOverall = max($maxOverall, (int) end($parts));
        }
        if ($maxSequenceDaily) {
            $parts = explode('-', $maxSequenceDaily);
            $maxOverall = max($maxOverall, (int) end($parts));
        }
        
        // Track generated numbers for this import session
        $key = "{$prefix}-{$plat}";
        if (!isset(self::$generatedNumbers[$key])) {
            self::$generatedNumbers[$key] = $maxOverall + 1;
        }
        
        $sequence = self::$generatedNumbers[$key];
        self::$generatedNumbers[$key]++;
        
        return "{$prefix}-" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate a unique no_surat_jalan for a daily cost.
     * Format: DC-YYYYMMDD-NXN (e.g., DC-20261126-001)
     */
    public static function generateForDailyCost(string $tanggal, string $plat): string
    {
        $date = is_string($tanggal) ? $tanggal : $tanggal->format('Y-m-d');
        $dateFormatted = \Carbon\Carbon::parse($date)->format('Ymd');
        
        $prefix = "DC-{$dateFormatted}";
        
        // Find the highest sequence number for this date in trips
        $maxSequence = DB::table('trips')
            ->where('no_surat_jalan', 'like', $prefix . '-%')
            ->max(DB::raw("CAST(SUBSTRING(no_surat_jalan, LENGTH(?) + 2) AS UNSIGNED)", [$prefix]));
        
        // Also check daily_costs table
        $maxSequenceDaily = DB::table('daily_costs')
            ->where('no_surat_jalan', 'like', $prefix . '-%')
            ->max(DB::raw("CAST(SUBSTRING(no_surat_jalan, LENGTH(?) + 2) AS UNSIGNED)", [$prefix]));
        
        $maxOverall = max($maxSequence ?? 0, $maxSequenceDaily ?? 0);
        
        // Track generated numbers for this import session
        $key = "{$prefix}-{$plat}";
        if (!isset(self::$generatedNumbers[$key])) {
            self::$generatedNumbers[$key] = $maxOverall + 1;
        }
        
        $sequence = self::$generatedNumbers[$key];
        self::$generatedNumbers[$key]++;
        
        return "{$prefix}-" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Reset the generated numbers cache (useful for testing).
     */
    public static function reset(): void
    {
        self::$generatedNumbers = [];
    }
}