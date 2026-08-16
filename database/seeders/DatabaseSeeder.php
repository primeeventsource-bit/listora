<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            // First: everything downstream authorises against these, and while
            // the tables are empty Role::configured() is false and every
            // granular permission check falls back to a binary "is admin".
            // Seeding does not create any account — use `listora:make-admin`.
            RbacSeeder::class,
            ListoraSeeder::class,
            // The support assistant answers policy questions only from these
            // articles, so an unseeded help centre leaves it with nothing to
            // quote — not just an empty page.
            HelpArticleSeeder::class,
        ]);
    }
}
