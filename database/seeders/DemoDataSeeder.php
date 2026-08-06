<?php

namespace Database\Seeders;

use App\Services\DemoData\DemoDataService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        app(DemoDataService::class)->seed();
    }
}
