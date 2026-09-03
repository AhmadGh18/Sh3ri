<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Poetry\Models\Meter;
use Illuminate\Database\Seeder;

class MeterSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => 'tawil',      'name_ar' => 'الطويل',   'name_en' => 'At-Tawil',    'pattern' => 'فَعُولُنْ مَفَاعِيلُنْ فَعُولُنْ مَفَاعِلُنْ', 'family' => 'khalilian'],
            ['slug' => 'basit',      'name_ar' => 'البسيط',   'name_en' => 'Al-Basit',    'pattern' => 'مُسْتَفْعِلُنْ فَاعِلُنْ مُسْتَفْعِلُنْ فَعِلُنْ', 'family' => 'khalilian'],
            ['slug' => 'wafir',      'name_ar' => 'الوافر',   'name_en' => 'Al-Wafir',    'pattern' => 'مُفَاعَلَتُنْ مُفَاعَلَتُنْ فَعُولُنْ', 'family' => 'khalilian'],
            ['slug' => 'kamil',      'name_ar' => 'الكامل',   'name_en' => 'Al-Kamil',    'pattern' => 'مُتَفَاعِلُنْ مُتَفَاعِلُنْ مُتَفَاعِلُنْ', 'family' => 'khalilian'],
            ['slug' => 'hazaj',      'name_ar' => 'الهزج',    'name_en' => 'Al-Hazaj',    'pattern' => 'مَفَاعِيلُنْ مَفَاعِيلُنْ', 'family' => 'khalilian'],
            ['slug' => 'rajaz',      'name_ar' => 'الرجز',    'name_en' => 'Ar-Rajaz',    'pattern' => 'مُسْتَفْعِلُنْ مُسْتَفْعِلُنْ مُسْتَفْعِلُنْ', 'family' => 'khalilian'],
            ['slug' => 'ramal',      'name_ar' => 'الرمل',    'name_en' => 'Ar-Ramal',    'pattern' => 'فَاعِلَاتُنْ فَاعِلَاتُنْ فَاعِلَاتُنْ', 'family' => 'khalilian'],
            ['slug' => 'sari',       'name_ar' => 'السريع',   'name_en' => 'As-Sari',     'pattern' => 'مُسْتَفْعِلُنْ مُسْتَفْعِلُنْ فَاعِلُنْ', 'family' => 'khalilian'],
            ['slug' => 'munsarih',   'name_ar' => 'المنسرح',  'name_en' => 'Al-Munsarih', 'pattern' => 'مُسْتَفْعِلُنْ مَفْعُولَاتُ مُسْتَفْعِلُنْ', 'family' => 'khalilian'],
            ['slug' => 'khafif',     'name_ar' => 'الخفيف',   'name_en' => 'Al-Khafif',   'pattern' => 'فَاعِلَاتُنْ مُسْتَفْعِ لُنْ فَاعِلَاتُنْ', 'family' => 'khalilian'],
            ['slug' => 'mudari',     'name_ar' => 'المضارع',  'name_en' => 'Al-Mudari',   'pattern' => 'مَفَاعِيلُ فَاعِلَاتُنْ', 'family' => 'khalilian'],
            ['slug' => 'muqtadab',   'name_ar' => 'المقتضب',  'name_en' => 'Al-Muqtadab', 'pattern' => 'مَفْعُولَاتُ مُسْتَفْعِلُنْ', 'family' => 'khalilian'],
            ['slug' => 'mujtath',    'name_ar' => 'المجتث',   'name_en' => 'Al-Mujtath',  'pattern' => 'مُسْتَفْعِ لُنْ فَاعِلَاتُنْ', 'family' => 'khalilian'],
            ['slug' => 'mutaqarib',  'name_ar' => 'المتقارب', 'name_en' => 'Al-Mutaqarib', 'pattern' => 'فَعُولُنْ فَعُولُنْ فَعُولُنْ فَعُولُنْ', 'family' => 'khalilian'],
            ['slug' => 'mutadarik',  'name_ar' => 'المتدارك', 'name_en' => 'Al-Mutadarik', 'pattern' => 'فَاعِلُنْ فَاعِلُنْ فَاعِلُنْ فَاعِلُنْ', 'family' => 'khalilian'],
            ['slug' => 'madid',      'name_ar' => 'المديد',   'name_en' => 'Al-Madid',    'pattern' => 'فَاعِلَاتُنْ فَاعِلُنْ فَاعِلَاتُنْ', 'family' => 'khalilian'],
        ];

        foreach ($rows as $r) {
            Meter::updateOrCreate(['slug' => $r['slug']], $r);
        }
    }
}
