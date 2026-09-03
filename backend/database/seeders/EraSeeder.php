<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Poetry\Models\Era;
use Illuminate\Database\Seeder;

class EraSeeder extends Seeder
{
    public function run(): void
    {
        // `name_ar` values match the exact strings used in the Kaggle
        // ahmedabelal/arabic-poetry `category` field so the taxonomy
        // resolver can bind by normalize_arabic() equality without aliases.
        $rows = [
            ['slug' => 'pre-islamic',              'name_ar' => 'العصر الجاهلي',   'name_en' => 'Pre-Islamic',   'start_year' => -500, 'end_year' => 622,  'display_order' => 1],
            ['slug' => 'mukhadramin',              'name_ar' => 'عصر المخضرمون',   'name_en' => 'Mukhadramin (Straddlers)', 'start_year' => 610, 'end_year' => 661, 'display_order' => 2],
            ['slug' => 'islamic',                  'name_ar' => 'العصر الإسلامي',  'name_en' => 'Islamic',       'start_year' => 622, 'end_year' => 661,  'display_order' => 3],
            ['slug' => 'umayyad',                  'name_ar' => 'العصر الاموي',    'name_en' => 'Umayyad',       'start_year' => 661, 'end_year' => 750,  'display_order' => 4],
            ['slug' => 'abbasid',                  'name_ar' => 'العصر العباسي',   'name_en' => 'Abbasid',       'start_year' => 750, 'end_year' => 1258, 'display_order' => 5],
            ['slug' => 'andalusian',               'name_ar' => 'العصر الأندلسي',  'name_en' => 'Andalusian',    'start_year' => 711, 'end_year' => 1492, 'display_order' => 6],
            ['slug' => 'ayyubid',                  'name_ar' => 'العصر الايوبي',   'name_en' => 'Ayyubid',       'start_year' => 1171, 'end_year' => 1250, 'display_order' => 7],
            ['slug' => 'mamluk',                   'name_ar' => 'العصر المملوكي',  'name_en' => 'Mamluk',        'start_year' => 1250, 'end_year' => 1517, 'display_order' => 8],
            ['slug' => 'ottoman',                  'name_ar' => 'العصر العثماني',  'name_en' => 'Ottoman',       'start_year' => 1517, 'end_year' => 1800, 'display_order' => 9],
            ['slug' => 'modern',                   'name_ar' => 'العصر الحديث',    'name_en' => 'Modern',        'start_year' => 1800, 'end_year' => null, 'display_order' => 10],
        ];

        foreach ($rows as $r) {
            Era::updateOrCreate(['slug' => $r['slug']], $r);
        }
    }
}
