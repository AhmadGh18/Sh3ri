<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Poetry\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => 'ghazal',     'name_ar' => 'غزل',    'name_en' => 'Love',        'display_order' => 1],
            ['slug' => 'madh',       'name_ar' => 'مدح',    'name_en' => 'Praise',      'display_order' => 2],
            ['slug' => 'hija',       'name_ar' => 'هجاء',   'name_en' => 'Satire',      'display_order' => 3],
            ['slug' => 'ritha',      'name_ar' => 'رثاء',   'name_en' => 'Elegy',       'display_order' => 4],
            ['slug' => 'hikma',      'name_ar' => 'حكمة',   'name_en' => 'Wisdom',      'display_order' => 5],
            ['slug' => 'wasf',       'name_ar' => 'وصف',    'name_en' => 'Description', 'display_order' => 6],
            ['slug' => 'diniyya',    'name_ar' => 'دينية',  'name_en' => 'Religious',   'display_order' => 7],
            ['slug' => 'siyasiyya',  'name_ar' => 'سياسية', 'name_en' => 'Political',   'display_order' => 8],
            ['slug' => 'hamasa',     'name_ar' => 'حماسة',  'name_en' => 'Heroic',      'display_order' => 9],
            ['slug' => 'itab',       'name_ar' => 'عتاب',   'name_en' => 'Reproach',    'display_order' => 10],
            ['slug' => 'itithar',    'name_ar' => 'اعتذار', 'name_en' => 'Apology',     'display_order' => 11],
            ['slug' => 'zuhd',       'name_ar' => 'زهد',    'name_en' => 'Asceticism',  'display_order' => 12],
            ['slug' => 'shakwa',     'name_ar' => 'شكوى',   'name_en' => 'Complaint',   'display_order' => 13],
            ['slug' => 'firaq',      'name_ar' => 'فراق',   'name_en' => 'Parting',     'display_order' => 14],
            ['slug' => 'other',      'name_ar' => 'متفرقات', 'name_en' => 'Other',      'display_order' => 99],
        ];

        foreach ($rows as $r) {
            Category::updateOrCreate(['slug' => $r['slug']], $r);
        }
    }
}
