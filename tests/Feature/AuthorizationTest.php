<?php

use App\Models\Invoice;
use App\Models\User;

dataset('restricted routes', [
    'trips for keuangan' => ['keuangan', 'get', '/trips', 403],
    'daily costs for keuangan' => ['keuangan', 'get', '/daily-costs', 403],
    'invoices for operator' => ['operator', 'get', '/invoices', 403],
    'reports for operator' => ['operator', 'get', '/reports/vehicles', 403],
    'users for operator' => ['operator', 'get', '/admin/users', 403],
    'users for keuangan' => ['keuangan', 'get', '/admin/users', 403],
    'trips for operator' => ['operator', 'get', '/trips', 200],
    'invoices for keuangan' => ['keuangan', 'get', '/invoices', 200],
    'reports for keuangan' => ['keuangan', 'get', '/reports/vehicles', 200],
    'users for admin' => ['admin', 'get', '/admin/users', 200],
    'clients for keuangan' => ['keuangan', 'get', '/clients', 200],
    'vehicles for operator' => ['operator', 'get', '/vehicles', 200],
    'dashboard for keuangan' => ['keuangan', 'get', '/dashboard', 200],
]);

test('role gates pages', function (string $role, string $method, string $uri, int $status) {
    $this->actingAs(User::factory()->{$role}()->create());

    $this->{$method}($uri)->assertStatus($status);
})->with('restricted routes');

test('keuangan cannot modify master data and operator cannot create invoices', function () {
    $this->actingAs(User::factory()->keuangan()->create());
    $this->post('/clients', ['nama' => 'X', 'alamat' => 'Y'])->assertForbidden();

    $this->actingAs(User::factory()->operator()->create());
    $this->post('/invoices', [])->assertForbidden();
    $this->get(route('invoices.pdf', Invoice::factory()->create()))->assertForbidden();
});

test('guests are redirected to login', function () {
    $this->get('/trips')->assertRedirect(route('login'));
});

test('public registration is disabled', function () {
    $this->get('/register')->assertNotFound();
});
