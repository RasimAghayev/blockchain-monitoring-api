<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // TaskSeeder::class used to be listed here but the class doesn't exist
        // anywhere in this codebase (no app/.../Task* model, controller, or
        // migration either) — a leftover reference from whatever starter
        // kit/tutorial this project was originally scaffolded from, pointing at a
        // domain ("tasks") this app has never had. `php artisan db:seed` has been
        // fatally erroring on it. Token/Transaction domain data has no dedicated
        // seeder of its own yet; add one here if that's ever needed.
        $this->call([
            UserSeeder::class,
        ]);
    }
}
