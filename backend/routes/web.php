<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Temporary Route to create the first admin user
// Visit: /setup-admin
Route::get('/setup-admin', function () {
    $user = \App\Models\User::updateOrCreate(
        ['email' => 'admin@josef.com'],
        [
            'name' => 'Josef Admin',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]
    );
    return 'Admin user created successfully! You can now log in with admin@josef.com / password123. (Please delete this route from web.php after use).';
});
