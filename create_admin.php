<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Check if admin user exists
$admin = User::where('email', 'admin@merasstore.com')->first();

if (!$admin) {
    User::create([
        'name' => 'Administrator',
        'email' => 'admin@merasstore.com',
        'password' => Hash::make('admin123'),
        'role' => 'admin',
    ]);
    echo "✓ Admin user created successfully.\n";
    echo "  Email: admin@merasstore.com\n";
    echo "  Password: admin123\n";
} else {
    echo "✓ Admin user already exists.\n";
}

