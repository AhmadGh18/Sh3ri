<?php

declare(strict_types=1);

use App\Domain\Poetry\Support\ArabicNormalizer;
use Illuminate\Support\Facades\DB;

it('normalizes PHP-side and Postgres-side identically for tricky Arabic', function (string $input) {
    $php = app(ArabicNormalizer::class)->normalize($input);
    $sql = DB::selectOne('select normalize_arabic(?) as v', [$input])->v ?? '';

    expect($php)->toBe($sql);
})->with([
    'diacritics'      => 'إذَا الشَّعْبُ يَوْمَاً أَرادَ الحَيَاةَ',
    'tatweel'         => 'أحبّـــك يا حياتي',
    'quranic'         => 'ٱلْحَمْدُ لِلَّهِ',
    'alif maqsura'    => 'المتنبي أبو الطيب',
    'arabic digits'   => 'الشعر ١٢٣٤٥',
    'ta marbuta / ha' => 'ة و ه',
]);
