<?php

use App\Models\User;

test('debug login pipeline', function () {
    $user = User::factory()->create();
    
    echo "Direct auth with name: " . (auth()->attempt(['name' => $user->name, 'password' => 'password']) ? 'SUCCESS' : 'FAILED') . "\n";
    auth()->logout();
    
    // Test via HTTP
    $response = $this->post(route('login.store'), [
        'name' => $user->name,
        'password' => 'password',
    ]);
    
    echo "HTTP status: " . $response->status() . "\n";
    echo "Redirect: " . $response->headers->get('Location') . "\n";
    echo "Authenticated after: " . (auth()->check() ? 'YES' : 'NO') . "\n";
    
    // Check if it's a validation error
    if ($response->status() === 302 && $response->headers->get('Location') === 'http://localhost') {
        echo "\n=== BUG: Redirecting to home instead of dashboard ===\n";
        echo "This means login FAILED silently\n";
    }
});
