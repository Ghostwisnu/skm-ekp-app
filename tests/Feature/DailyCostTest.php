<?php

use App\Models\DailyCost;
use App\Models\Trip;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->operator()->create());
});

test('daily cost total is the plain sum of the seven components', function () {
    $cost = DailyCost::factory()->make();

    expect($cost->total)->toBe(1150000);
});

test('daily costs are stored once per trip', function () {
    $trip = Trip::factory()->create();
    $payload = ['trip_id' => $trip->id, ...array_fill_keys(DailyCost::COMPONENTS, 100)];

    $this->post('/daily-costs', $payload)->assertRedirect();
    $this->post('/daily-costs', [...$payload, 'uang_jalan_1' => 900])->assertRedirect();

    expect(DailyCost::count())->toBe(1)
        ->and(DailyCost::first()->uang_jalan_1)->toBe(900);
});

test('daily cost components must be non-negative integers', function () {
    $trip = Trip::factory()->create();

    $this->post('/daily-costs', ['trip_id' => $trip->id, ...array_fill_keys(DailyCost::COMPONENTS, -1)])
        ->assertSessionHasErrors(DailyCost::COMPONENTS);
});

test('a daily cost can be updated', function () {
    $cost = DailyCost::factory()->create();

    $this->put("/daily-costs/{$cost->id}", ['trip_id' => $cost->trip_id, ...array_fill_keys(DailyCost::COMPONENTS, 10)])
        ->assertRedirect();

    expect($cost->fresh()->total)->toBe(70);
});
