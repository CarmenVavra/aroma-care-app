<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Local demo account (development only):
     *   E-Mail:   anna@example.com
     *   Passwort: aromacare-demo
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Anna',
            'email' => 'anna@example.com',
            'password' => 'aromacare-demo',
        ]);

        $this->callWith(ProductSeeder::class, ['user' => $user]);
        $this->call(RecipeSeeder::class);
    }
}
