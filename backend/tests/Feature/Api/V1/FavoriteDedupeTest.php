<?php

declare(strict_types=1);

use App\Domain\Poetry\Models\Favorite;
use App\Domain\Poetry\Models\Poem;
use App\Domain\Poetry\Models\Poet;
use App\Enums\PoemStatus;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('is idempotent: favoriting the same poem twice does not double-insert', function () {
    $user = User::factory()->create()->assignRole('user');
    $poet = Poet::create(['slug' => 'p', 'name_ar' => 'ش']);
    $poem = Poem::create([
        'slug' => 'poem-fav',
        'poet_id' => $poet->id,
        'title_ar' => 'قصيدة',
        'status' => PoemStatus::Published,
        'published_at' => now(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/poems/{$poem->slug}/favorite")->assertStatus(204);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/poems/{$poem->slug}/favorite")->assertStatus(204);

    expect(Favorite::where('user_id', $user->id)->count())->toBe(1);
});
