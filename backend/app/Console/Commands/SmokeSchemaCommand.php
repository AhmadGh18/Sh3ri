<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Poetry\Models\Era;
use App\Domain\Poetry\Models\Poem;
use App\Domain\Poetry\Models\Poet;
use App\Domain\Poetry\Models\Verse;
use App\Enums\PoemStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SmokeSchemaCommand extends Command
{
    protected $signature = 'sh3ri:smoke-schema';
    protected $description = 'Insert one poet + poem + verses and prove generated columns + FTS work.';

    public function handle(): int
    {
        DB::transaction(function () {
            $era = Era::firstOrCreate(
                ['slug' => 'abbasid'],
                ['name_ar' => 'العصر العباسي', 'name_en' => 'Abbasid', 'display_order' => 4]
            );

            $poet = Poet::create([
                'slug' => 'al-mutanabbi',
                'name_ar' => 'أبو الطيب المتنبي',
                'bio_ar' => 'شاعر عربي من العصر العباسي',
                'era_id' => $era->id,
                'birth_year' => 915,
                'death_year' => 965,
                'source' => 'smoke_test',
                'source_external_id' => '1',
                'import_meta' => ['sample' => true],
            ]);

            $poem = Poem::create([
                'slug' => 'ala-qadri-ahli-al-azmi',
                'poet_id' => $poet->id,
                'era_id' => $era->id,
                'title_ar' => 'عَلَى قَدْرِ أَهْلِ العَزْمِ',
                'language' => 'ar',
                'status' => PoemStatus::Published,
                'published_at' => now(),
                'source' => 'smoke_test',
                'source_external_id' => 'p1',
                'raw_source_text' => 'raw text',
            ]);

            Verse::create([
                'poem_id' => $poem->id,
                'position' => 1,
                'hemistich_a' => 'عَلَى قَدْرِ أَهْلِ العَزْمِ تَأْتِي العَزَائِمُ',
                'hemistich_b' => 'وَتَأْتِي عَلَى قَدْرِ الكِرَامِ المَكَارِمُ',
            ]);
            Verse::create([
                'poem_id' => $poem->id,
                'position' => 2,
                'hemistich_a' => 'وَتَعْظُمُ في عَينِ الصَّغيرِ صِغَارُها',
                'hemistich_b' => 'وَتَصْغُرُ في عَينِ العَظِيمِ العَظائِمُ',
            ]);
        });

        $row = DB::selectOne('select name_ar, name_normalized, search_tsv::text from poets where slug = ?', ['al-mutanabbi']);
        $this->line('poet.name_ar         : ' . $row->name_ar);
        $this->line('poet.name_normalized : ' . $row->name_normalized);
        $this->line('poet.search_tsv      : ' . $row->search_tsv);

        $poem = DB::selectOne('select title_ar, title_normalized, search_tsv::text from poems where slug = ?', ['ala-qadri-ahli-al-azmi']);
        $this->line('');
        $this->line('poem.title_ar        : ' . $poem->title_ar);
        $this->line('poem.title_normalized: ' . $poem->title_normalized);
        $this->line('poem.search_tsv      : ' . $poem->search_tsv);

        $verse = DB::selectOne('select full_text, full_text_normalized, search_tsv::text from verses where position = 1');
        $this->line('');
        $this->line('verse.full_text            : ' . $verse->full_text);
        $this->line('verse.full_text_normalized : ' . $verse->full_text_normalized);
        $this->line('verse.search_tsv           : ' . $verse->search_tsv);

        $this->line('');
        $this->line('=== FTS query test (normalized query: "قدر الاهل") ===');
        $rows = DB::select(<<<'SQL'
            SELECT v.position, v.full_text,
                   ts_rank_cd(v.search_tsv, plainto_tsquery('arabic_simple', normalize_arabic(?))) AS rank
            FROM verses v
            WHERE v.search_tsv @@ plainto_tsquery('arabic_simple', normalize_arabic(?))
            ORDER BY rank DESC
            LIMIT 5
        SQL, ['قدر', 'قدر']);

        foreach ($rows as $r) {
            $this->line("  #{$r->position} rank={$r->rank}  {$r->full_text}");
        }

        $this->line('');
        $this->line('=== trigram similarity test on poet name ===');
        $rows = DB::select(<<<'SQL'
            SELECT name_ar, similarity(name_normalized, normalize_arabic(?)) AS sim
            FROM poets
            WHERE name_normalized % normalize_arabic(?)
            ORDER BY sim DESC
        SQL, ['متنبي', 'متنبي']);
        foreach ($rows as $r) {
            $this->line("  {$r->name_ar}   sim={$r->sim}");
        }

        return self::SUCCESS;
    }
}
