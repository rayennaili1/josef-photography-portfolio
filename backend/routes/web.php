<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Temporary Route to create the first admin user
// Visit: /setup-admin
// Temporary Route to create the first admin user
// Visit: /setup-admin
Route::get('/setup-admin', function () {
    $user = \App\Models\User::updateOrCreate(
        ['email' => 'admin@josef.com'],
        [
            'name' => 'Josef Admin',
            'password' => 'password123', // 'hashed' cast in User model handles this automatically
        ]
    );

    $count = \App\Models\User::count();
    
    return [
        'message' => 'Admin user created/updated successfully!',
        'email' => 'admin@josef.com',
        'password' => 'password123',
        'total_users' => $count,
        'next_step' => 'Now try logging in at the frontend /admin/josef/login'
    ];
});

// Diagnostic route to check database connection and user existence
Route::get('/db-check', function() {
    try {
        \DB::connection()->getPdo();
        $user = \App\Models\User::where('email', 'admin@josef.com')->first();
        return [
            'database' => 'connected',
            'admin_user_exists' => !!$user,
            'admin_email' => $user ? $user->email : null,
            'laravel_version' => app()->version(),
        ];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
});
