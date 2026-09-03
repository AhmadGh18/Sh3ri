<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Poetry\Support\ArabicNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SmokeNormalizerCommand extends Command
{
    protected $signature = 'sh3ri:smoke-normalizer';
    protected $description = 'Verify PHP ArabicNormalizer and PG normalize_arabic() agree.';

    public function handle(ArabicNormalizer $normalizer): int
    {
        $cases = [
            'إذَا الشَّعْبُ يَوْمَاً أَرادَ الحَيَاةَ',
            'أحبّـــك يا حياتي',
            'ٱلْحَمْدُ لِلَّهِ',
            'المتنبي أبو الطيب',
            'قال أبو العلاء المعرّي',
            'الشعر ١٢٣٤٥',
            'شيء / شئ / شيئ',
            'ة و ه',
            'رأى / رأي / رئي',
        ];

        $ok = 0;
        $fail = 0;
        foreach ($cases as $c) {
            $php = $normalizer->normalize($c);
            $sql = DB::selectOne('select normalize_arabic(?) as v', [$c])->v ?? '';
            $match = $php === $sql;
            $match ? $ok++ : $fail++;
            $this->line(($match ? '<info>OK</info> ' : '<error>DIFF</error> ') . 'input: ' . $c);
            $this->line('       php:  ' . $php);
            $this->line('       sql:  ' . $sql);
            $this->newLine();
        }

        $this->line("passed: {$ok}   failed: {$fail}");

        return $fail === 0 ? self::SUCCESS : self::FAILURE;
    }
}
