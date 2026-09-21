<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Run order matters — categories and brands must exist before products.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            AttributeDefinitionSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
