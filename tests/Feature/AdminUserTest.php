<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('admin creates a user with a role', function () {
    $this->post('/admin/users', [
        'name' => 'Budi', 'email' => 'budi@example.com', 'role' => 'keuangan', 'password' => 'Password123',
    ])->assertRedirect();

    expect(User::where('email', 'budi@example.com')->first()->hasRole('keuangan'))->toBeTrue();
});

test('invalid role is rejected', function () {
    $this->post('/admin/users', [
        'name' => 'Budi', 'email' => 'budi@example.com', 'role' => 'root', 'password' => 'Password123',
    ])->assertSessionHasErrors('role');
});

test('password is optional when updating and admin cannot delete self', function () {
    $user = User::factory()->operator()->create();
    $hash = $user->password;

    $this->put("/admin/users/{$user->id}", [
        'name' => 'Baru', 'email' => $user->email, 'role' => 'admin', 'password' => '',
    ])->assertRedirect();

    expect($user->fresh()->hasRole('admin'))->toBeTrue()->and($user->fresh()->password)->toBe($hash);

    $this->delete("/admin/users/{$this->admin->id}")->assertRedirect();
    $this->assertModelExists($this->admin);
});
