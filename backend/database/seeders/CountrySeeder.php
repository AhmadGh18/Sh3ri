<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Poetry\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['iso_code' => 'SA', 'slug' => 'saudi-arabia', 'name_ar' => 'السعودية', 'name_en' => 'Saudi Arabia'],
            ['iso_code' => 'EG', 'slug' => 'egypt',        'name_ar' => 'مصر',       'name_en' => 'Egypt'],
            ['iso_code' => 'IQ', 'slug' => 'iraq',         'name_ar' => 'العراق',    'name_en' => 'Iraq'],
            ['iso_code' => 'SY', 'slug' => 'syria',        'name_ar' => 'سوريا',     'name_en' => 'Syria'],
            ['iso_code' => 'LB', 'slug' => 'lebanon',      'name_ar' => 'لبنان',     'name_en' => 'Lebanon'],
            ['iso_code' => 'JO', 'slug' => 'jordan',       'name_ar' => 'الأردن',    'name_en' => 'Jordan'],
            ['iso_code' => 'PS', 'slug' => 'palestine',    'name_ar' => 'فلسطين',    'name_en' => 'Palestine'],
            ['iso_code' => 'YE', 'slug' => 'yemen',        'name_ar' => 'اليمن',     'name_en' => 'Yemen'],
            ['iso_code' => 'OM', 'slug' => 'oman',         'name_ar' => 'عمان',      'name_en' => 'Oman'],
            ['iso_code' => 'BH', 'slug' => 'bahrain',      'name_ar' => 'البحرين',   'name_en' => 'Bahrain'],
            ['iso_code' => 'KW', 'slug' => 'kuwait',       'name_ar' => 'الكويت',    'name_en' => 'Kuwait'],
            ['iso_code' => 'QA', 'slug' => 'qatar',        'name_ar' => 'قطر',       'name_en' => 'Qatar'],
            ['iso_code' => 'AE', 'slug' => 'uae',          'name_ar' => 'الإمارات',  'name_en' => 'UAE'],
            ['iso_code' => 'LY', 'slug' => 'libya',        'name_ar' => 'ليبيا',     'name_en' => 'Libya'],
            ['iso_code' => 'TN', 'slug' => 'tunisia',      'name_ar' => 'تونس',      'name_en' => 'Tunisia'],
            ['iso_code' => 'DZ', 'slug' => 'algeria',      'name_ar' => 'الجزائر',   'name_en' => 'Algeria'],
            ['iso_code' => 'MA', 'slug' => 'morocco',      'name_ar' => 'المغرب',    'name_en' => 'Morocco'],
            ['iso_code' => 'SD', 'slug' => 'sudan',        'name_ar' => 'السودان',   'name_en' => 'Sudan'],
            ['iso_code' => 'MR', 'slug' => 'mauritania',   'name_ar' => 'موريتانيا', 'name_en' => 'Mauritania'],
            ['iso_code' => 'SO', 'slug' => 'somalia',      'name_ar' => 'الصومال',   'name_en' => 'Somalia'],
            ['iso_code' => 'DJ', 'slug' => 'djibouti',     'name_ar' => 'جيبوتي',    'name_en' => 'Djibouti'],
            ['iso_code' => 'KM', 'slug' => 'comoros',      'name_ar' => 'جزر القمر', 'name_en' => 'Comoros'],
            // Historical / non-ISO regions kept as null iso_code
            ['iso_code' => null, 'slug' => 'andalusia',    'name_ar' => 'الأندلس',   'name_en' => 'Andalusia'],
            ['iso_code' => null, 'slug' => 'arabia-historical', 'name_ar' => 'شبه الجزيرة العربية', 'name_en' => 'Historical Arabia'],
        ];

        foreach ($rows as $r) {
            Country::updateOrCreate(['slug' => $r['slug']], $r);
        }
    }
}
