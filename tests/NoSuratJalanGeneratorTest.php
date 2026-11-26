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
    $generator = new NoSuratJalanGenerator();
    $noSuratJalan = $generator->generateForTrip();

    expect($noSuratJalan)->toMatch('/^SJ-\d{8}-\d{3}$/');
});

test('generates daily cost surat jalan with DC prefix', function () {
    $generator = new NoSuratJalanGenerator();
    $noSuratJalan = $generator->generateForDailyCost();

    expect($noSuratJalan)->toMatch('/^DC-\d{8}-\d{3}$/');
});

test('generates unique sequential numbers for same day', function () {
    $generator = new NoSuratJalanGenerator();

    // Should generate SJ-YYYYMMDD-003 (next after existing test data)
    $noSuratJalan = $generator->generateForTrip();
    $today = now()->format('Ymd');

    expect($noSuratJalan)->toBe("SJ-{$today}-003");
});

test('returns existing no_surat_jalan if provided', function () {
    $generator = new NoSuratJalanGenerator();
    $existing = 'SJ-TEST-123';

    $result = $generator->resolve($existing, 'trip');

    expect($result)->toBe($existing);
});

test('generates new no_surat_jalan when empty string provided', function () {
    $generator = new NoSuratJalanGenerator();

    $result = $generator->resolve('', 'trip');

    expect($result)->toMatch('/^SJ-\d{8}-\d{3}$/');
});

test('generates new no_surat_jalan when null provided', function () {
    $generator = new NoSuratJalanGenerator();

    $result = $generator->resolve(null, 'trip');

    expect($result)->toMatch('/^SJ-\d{8}-\d{3}$/');
});

test('generates daily cost surat jalan for daily cost type', function () {
    $generator = new NoSuratJalanGenerator();

    $result = $generator->resolve(null, 'daily_cost');

    expect($result)->toMatch('/^DC-\d{8}-\d{3}$/');
});

test('custom prefix overrides default', function () {
    $generator = new NoSuratJalanGenerator();

    // Override default prefix
    $tripNo = $generator->generateForTrip('CUSTOM');
    $dailyCostNo = $generator->generateForDailyCost('BIAYA');

    expect($tripNo)->toMatch('/^CUSTOM-\d{8}-\d{3}$/');
    expect($dailyCostNo)->toMatch('/^BIAYA-\d{8}-\d{3}$/');
});
