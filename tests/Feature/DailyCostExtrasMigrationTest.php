<?php

use App\Models\DailyCost;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('daily cost extras migration creates a missing table', function () {
    Schema::dropIfExists('daily_cost_extras');

    $migration = require database_path('migrations/2026_11_13_000000_create_daily_cost_extras_table.php');
    $migration->up();

    expect(Schema::hasColumns('daily_cost_extras', ['id', 'daily_cost_id', 'nama_biaya', 'nominal', 'created_at', 'updated_at']))->toBeTrue();
});

test('daily cost extras migration preserves an existing table and its data', function () {
    $cost = DailyCost::factory()->create();
    DB::table('daily_cost_extras')->insert([
        'daily_cost_id' => $cost->id,
        'nama_biaya' => 'Toll',
        'nominal' => 100,
    ]);

    $migration = require database_path('migrations/2026_11_13_000000_create_daily_cost_extras_table.php');
    $migration->up();

    $this->assertDatabaseHas('daily_cost_extras', [
        'daily_cost_id' => $cost->id,
        'nama_biaya' => 'Toll',
        'nominal' => 100,
    ]);
});

test('daily cost extras migration tolerates a mysql table already exists error', function () {
    $cause = new PDOException;
    $cause->errorInfo = ['42S01', 1050, 'Table already exists'];
    $exception = new QueryException('mysql', 'create table daily_cost_extras', [], $cause);
    Schema::shouldReceive('hasTable')->once()->with('daily_cost_extras')->andReturn(false);
    Schema::shouldReceive('create')->once()->andThrow($exception);

    $migration = require database_path('migrations/2026_11_13_000000_create_daily_cost_extras_table.php');
    $migration->up();
});

test('daily cost extras migration propagates other database errors', function () {
    $cause = new PDOException;
    $cause->errorInfo = ['42000', 1142, 'Permission denied'];
    $exception = new QueryException('mysql', 'create table daily_cost_extras', [], $cause);
    Schema::shouldReceive('hasTable')->once()->with('daily_cost_extras')->andReturn(false);
    Schema::shouldReceive('create')->once()->andThrow($exception);

    $migration = require database_path('migrations/2026_11_13_000000_create_daily_cost_extras_table.php');
    $migration->up();
})->throws(QueryException::class);
