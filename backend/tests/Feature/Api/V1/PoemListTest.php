<?php

declare(strict_types=1);

use App\Domain\Poetry\Models\Era;
use App\Domain\Poetry\Models\Poem;
use App\Domain\Poetry\Models\Poet;
use App\Enums\PoemStatus;
use Database\Seeders\EraSeeder;

beforeEach(function () {
    $this->seed(EraSeeder::class);
});

it('lists only published poems and cursor-paginates', function () {
    $era = Era::first();
    $poet = Poet::create(['slug' => 'p1', 'name_ar' => 'شاعر', 'era_id' => $era->id]);

    for ($i = 0; $i < 3; $i++) {
        Poem::create([
            'slug' => "p1-poem-$i",
            'poet_id' => $poet->id,
            'era_id' => $era->id,
            'title_ar' => "قصيدة $i",
            'status' => PoemStatus::Published,
            'published_at' => now(),
        ]);
    }
    Poem::create([
        'slug' => 'quarantined',
        'poet_id' => $poet->id,
        'title_ar' => 'مخفية',
        'status' => PoemStatus::Quarantined,
    ]);

    $r = $this->getJson('/api/v1/poems?per_page=2')->assertStatus(200);
    $r->assertJsonCount(2, 'data');
    expect($r->json('meta.next_cursor'))->toBeString();
    // Ensure quarantined never leaked
    foreach ($r->json('data') as $row) {
        expect($row['status'])->toBe('published');
    }
});

it('filters by era slug', function () {
    $abbasid = Era::where('slug', 'abbasid')->first();
    $jahili  = Era::where('slug', 'pre-islamic')->first();
    $poet = Poet::create(['slug' => 'x', 'name_ar' => 'x']);
    Poem::create(['slug'=>'a','poet_id'=>$poet->id,'era_id'=>$abbasid->id,'title_ar'=>'أ','status'=>PoemStatus::Published,'published_at'=>now()]);
    Poem::create(['slug'=>'b','poet_id'=>$poet->id,'era_id'=>$jahili->id,'title_ar'=>'ب','status'=>PoemStatus::Published,'published_at'=>now()]);

    $r = $this->getJson('/api/v1/poems?filter[era]=abbasid')->assertStatus(200);
    expect($r->json('data'))->toHaveCount(1);
    expect($r->json('data.0.slug'))->toBe('a');
});
