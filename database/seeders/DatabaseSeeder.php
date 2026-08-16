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
            ListoraSeeder::class,
            // The support assistant answers policy questions only from these
            // articles, so an unseeded help centre leaves it with nothing to
            // quote — not just an empty page.
            HelpArticleSeeder::class,
        ]);
    }
}
