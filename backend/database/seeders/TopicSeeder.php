<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Poetry\Models\Topic;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => 'love',       'name_ar' => 'حب',        'name_en' => 'Love'],
            ['slug' => 'longing',    'name_ar' => 'شوق',       'name_en' => 'Longing'],
            ['slug' => 'homeland',   'name_ar' => 'وطن',       'name_en' => 'Homeland'],
            ['slug' => 'freedom',    'name_ar' => 'حرية',      'name_en' => 'Freedom'],
            ['slug' => 'religion',   'name_ar' => 'دين',       'name_en' => 'Religion'],
            ['slug' => 'time',       'name_ar' => 'زمن',       'name_en' => 'Time'],
            ['slug' => 'death',      'name_ar' => 'الموت',     'name_en' => 'Death'],
            ['slug' => 'nature',     'name_ar' => 'الطبيعة',   'name_en' => 'Nature'],
            ['slug' => 'philosophy', 'name_ar' => 'فلسفة',     'name_en' => 'Philosophy'],
            ['slug' => 'youth',      'name_ar' => 'شباب',      'name_en' => 'Youth'],
        ];

        foreach ($rows as $r) {
            Topic::updateOrCreate(['slug' => $r['slug']], $r);
        }
    }
}
