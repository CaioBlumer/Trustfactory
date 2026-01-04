<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Product::insert([
            [
                'name' => 'Canvas Backpack',
                'price' => 54.99,
                'stock_quantity' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Desk Lamp',
                'price' => 29.50,
                'stock_quantity' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Wireless Earbuds',
                'price' => 79.00,
                'stock_quantity' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ceramic Mug Set',
                'price' => 24.00,
                'stock_quantity' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
