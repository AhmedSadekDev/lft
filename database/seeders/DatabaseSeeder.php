<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            StaticPagesSeeder::class,
            VaultSeeder::class
        ]);

        Setting::create([
            'phone' => '000000000',
            'email' => 'mail@mail.com',
            'whatsapp' => '000000000000',
            'facebook' => '000000000000',
            'twitter' => '000000000000',
            'linkedin' => '000000000000',
            'instagram' => '000000000000',
        ]);


        \App\Models\User::factory(10)->create();

        \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
