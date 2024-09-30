<?php

namespace Database\Seeders;

use App\Models\Vault;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VaultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Vault::create([
            'amount' => 0
        ]);
    }
}
