<?php

namespace Database\Seeders;

use App\Models\StaticPage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StaticPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('static_pages')->truncate();

        $data = [
            [
                'key'   => 'about us',
                'title' => [
                    'en' => 'About Us',
                    'ar' => 'من نحن',
                ],
                'description'   => [
                    'en' => 'About Us Description',
                    'ar' => 'وصف من نحن',
                ],
                'image' => 'Image',
            ],[
                'key'   => 'our services',
                'title' => [
                    'en' => 'OUR SERVICES',
                    'ar' => 'الخدمة',
                ],
                'description' => [
                    'en' => 'OUR SERVICES Description',
                    'ar' => 'وصف الخدمة',
                ],
                'image' => 'Image',
            ],[
                'key'   => 'choose us',
                'title' => [
                    'en' => 'Choose Us',
                    'ar' => 'لماذا تختارنا',
                ],
                'description' => [
                    'en' => 'Choose Us Description',
                    'ar' => 'وصف لماذا تختارنا',
                ],
                'image' => 'Image',
            ],[
                'key'   => 'header',
                'title' => [
                    'en' => 'header',
                    'ar' => 'الرئيسية',
                ],
                'description' => [
                    'en' => 'Header Description',
                    'ar' => 'وصف الرئيسية',
                ],
                'image' => 'Image',
            ]
        ];

        foreach($data as $row){
            StaticPage::create($row);
        }

    }
}
