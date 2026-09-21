<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $adminPassword = env('ADMIN_DEFAULT_PASSWORD');
            if (!$adminPassword) {
                $this->command?->warn('ADMIN_DEFAULT_PASSWORD is not set. Default admin user cannot be created in production without an explicit password.');
                return;
            }
        } else {
            $adminPassword = env('ADMIN_DEFAULT_PASSWORD', 'ShreejiAdmin@2024');
        }

        User::create([
            'name' => 'Shreeji Admin',
            'email' => 'admin@shreejiinfo.in',
            'phone' => '9377704344',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'password' => Hash::make($adminPassword),
            'account_type' => 'personal',
            'is_active' => true,
            'is_admin' => true,
        ]);

        // Demo business customer
        $businessUser = User::create([
            'name' => 'Demo Business User',
            'email' => 'business@demo.com',
            'phone' => '9876543210',
            'email_verified_at' => now(),
            'password' => Hash::make('DemoBusiness@2024'),
            'account_type' => 'business',
            'is_active' => true,
            'is_admin' => false,
        ]);

        $businessUser->businessProfile()->create([
            'company_name' => 'Demo Industries Pvt Ltd',
            'gstin' => '24AABCU9603R1ZM',
            'pan' => 'AABCU9603R',
            'price_tier' => 'business',
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        // Demo retail customer
        User::create([
            'name' => 'Demo Retail User',
            'email' => 'retail@demo.com',
            'phone' => '9876543211',
            'email_verified_at' => now(),
            'password' => Hash::make('DemoRetail@2024'),
            'account_type' => 'personal',
            'is_active' => true,
            'is_admin' => false,
        ]);
    }
}
