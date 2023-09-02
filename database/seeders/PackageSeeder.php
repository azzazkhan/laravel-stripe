<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PackageSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = collect([
            ['name' => '250 Coins', 'coins' => 250, 'additional' => 0, 'price' => 4.99],
            ['name' => '500 Coins', 'coins' => 500, 'additional' => 50, 'price' => 9.99],
            ['name' => '1000 Coins', 'coins' => 1000, 'additional' => 200, 'price' => 19.99],
            ['name' => '2500 Coins', 'coins' => 2500, 'additional' => 500, 'price' => 49.99],
            ['name' => '5000 Coins', 'coins' => 5000, 'additional' => 1800, 'price' => 99.99],
            ['name' => '7000 Coins', 'coins' => 7000, 'additional' => 2250, 'price' => 149.99],
            ['name' => '15000 Coins', 'coins' => 15000, 'additional' => 4500, 'price' => 299.99],
        ])
            ->map(fn (array $row) => array_merge(['id' => (string) Str::ulid()], $row))
            ->toArray();

        DB::table('packages')->insert($packages);
    }
}
