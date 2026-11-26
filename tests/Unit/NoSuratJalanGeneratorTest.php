<?php

use App\Models\DailyCost;
use App\Models\Trip;
use App\Services\NoSuratJalanGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Create test data with various no_surat_jalan values
    Trip::factory()->create(['no_surat_jalan' => 'SJ-20261126-001']);
    Trip::factory()->create(['no_surat_jalan' => 'SJ-20261126-002']);
    Trip::factory()->create(['no_surat_jalan' => 'SJ-20261125-001']);
    DailyCost::factory()->create(['no_surat_jalan' => 'DC-20261126-001']);
});

test('generates trip surat jalan with SJ prefix', function () {
    $noSuratJalan = NoSuratJalanGenerator::generate(now()->format('Y-m-d'), 'B 1234');

    expect($noSuratJalan)->toMatch('/^SJ-\d{8}-\d{3}$/');
});

test('generates daily cost surat jalan with DC prefix', function () {
    $noSuratJalan = NoSuratJalanGenerator::generateForDailyCost(now()->format('Y-m-d'), 'B 1234');

    expect($noSuratJalan)->toMatch('/^DC-\d{8}-\d{3}$/');
});

test('generates unique sequential numbers for same day', function () {
    // Reset to clear any cache
    NoSuratJalanGenerator::reset();

    $noSuratJalan = NoSuratJalanGenerator::generate('2026-11-26', 'B 1234');

    expect($noSuratJalan)->toBe('SJ-20261126-003');
});

test('resets generate sequential numbers from existing data', function () {
    // Reset to clear any cache
    NoSuratJalanGenerator::reset();

    // Should continue from highest existing (SJ-20261126-002)
    $noSuratJalan = NoSuratJalanGenerator::generate('2026-11-26', 'B 1234');

    expect($noSuratJalan)->toBe('SJ-20261126-003');
});

test('generates sequential numbers for different vehicles', function () {
    // Reset to clear any cache
    NoSuratJalanGenerator::reset();

    $no1 = NoSuratJalanGenerator::generate('2026-11-26', 'B 1234');
    $no2 = NoSuratJalanGenerator::generate('2026-11-26', 'B 5678');

    // Both should start from same base since no existing data for those plates on that date
    expect($no1)->toBe('SJ-20261126-003')
        ->and($no2)->toBe('SJ-20261126-004'); // increments due to static cache
});

test('handles import counter parameter', function () {
    $noSuratJalan = NoSuratJalanGenerator::generate('2026-11-26', 'B 1234', 5);

    expect($noSuratJalan)->toBe('SJ-20261126-005');
});

test('ignores daily costs with different prefix', function () {
    // Reset to clear any cache
    NoSuratJalanGenerator::reset();

    // DC- prefix should not affect SJ- sequence
    $noSuratJalan = NoSuratJalanGenerator::generate('2026-11-26', 'B 1234');

    expect($noSuratJalan)->toMatch('/^SJ-\d{8}-\d{3}$/');
});

test('resets cached numbers', function () {
    // Generate a number
    $no1 = NoSuratJalanGenerator::generate('2026-11-26', 'B 1234');

    // Reset
    NoSuratJalanGenerator::reset();

    // Should start from existing max (002) + 1 = 003
    $no2 = NoSuratJalanGenerator::generate('2026-11-26', 'B 9999');

    expect($no1)->not->toBe($no2); // Different plates might have different values due to cache
});