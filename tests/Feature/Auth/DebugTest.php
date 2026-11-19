<?php

use App\Models\User;

test('debug login', function () {
    $user = User::factory()->create();
    
    echo "\n=== Debug ===\n";
    echo "User name: " . $user->name . "\n";
    echo "User email: " . $user->email . "\n";
    echo "Config username: " . config('fortify.username') . "\n";
    echo "Password hash: " . substr($user->password, 0, 30) . "...\n";
    
    // Direct auth
    $directResult = auth()->attempt(['name' => $user->name, 'password' => 'password']);
    echo "Direct auth: " . ($directResult ? 'SUCCESS' : 'FAILED') . "\n";
    auth()->logout();
    
    // HTTP test
    $response = $this->post(route('login.store'), [
        'name' => $user->name,
        'password' => 'password',
    ]);
    
    echo "HTTP Status: " . $response->status() . "\n";
    echo "Auth after HTTP: " . (auth()->check() ? 'YES' : 'NO') . "\n";
    
    if ($response->status() === 302) {
        echo "Redirect: " . $response->headers->get('Location') . "\n";
    } else {
        echo "Content: " . substr($response->content(), 0, 300) . "\n";
    }
    
    // Check session errors
    $errors = session()->get('errors');
    if ($errors) {
        echo "Session errors: " . print_r($errors, true) . "\n";
    }
});
