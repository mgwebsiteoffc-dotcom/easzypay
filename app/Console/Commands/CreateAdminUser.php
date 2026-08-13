<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature   = 'admin:create {--name=} {--email=} {--password=}';
    protected $description = 'Create an admin user';

    public function handle(): void
    {
        $name     = $this->option('name')     ?? $this->ask('Name');
        $email    = $this->option('email')    ?? $this->ask('Email');
        $password = $this->option('password') ?? $this->secret('Password');

        if (AdminUser::where('email', $email)->exists()) {
            $this->error("Admin with email {$email} already exists");
            return;
        }

        $user = AdminUser::create([
            'name'      => $name,
            'email'     => $email,
            'password'  => Hash::make($password),
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->info("✅ Admin created: {$user->name} ({$user->email})");
        $this->info("   Login at: " . config('app.url') . "/admin/login");
    }
}