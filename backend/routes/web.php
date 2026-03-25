<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Temporary Route to create the first admin user
Route::get('/setup-admin', function () {
    $email = 'admin@josef.com';
    $pass = 'password123';
    
    // Explicitly delete old user to ensure a fresh start
    \App\Models\User::where('email', $email)->delete();
    
    $user = \App\Models\User::create([
        'name' => 'Josef Admin',
        'email' => $email,
        'password' => $pass, // Hashed automatically by model cast
    ]);

    return [
        'status' => 'success',
        'message' => 'Fresh admin user created!',
        'user' => [
            'id' => $user->id,
            'email' => $user->email,
            'password_hash_preview' => substr($user->password, 0, 15) . '...',
        ],
        'total_db_users' => \App\Models\User::count(),
    ];
});

// Diagnostic route with password test
Route::get('/db-check', function() {
    try {
        \DB::connection()->getPdo();
        $user = \App\Models\User::where('email', 'admin@josef.com')->first();
        
        $hash_test = false;
        if ($user) {
            $hash_test = \Illuminate\Support\Facades\Hash::check('password123', $user->password);
        }

        return [
            'database' => 'connected',
            'user_exists' => !!$user,
            'password_matches_expected' => $hash_test,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
});
