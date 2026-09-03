<?php

declare(strict_types=1);

use App\Domain\Poetry\Models\UserPoem;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('prevents user A from reading, updating, or deleting user B private poem', function () {
    $alice = User::factory()->create()->assignRole('user');
    $bob   = User::factory()->create()->assignRole('user');

    // Bob creates a private draft — user_id / status are set explicitly
    // because they're intentionally excluded from $fillable (mass assignment
    // guard against ownership-transfer / self-publish exploits).
    $poem = new UserPoem([
        'title_ar' => 'بيت خاص',
        'raw_text' => 'نص خاص جدا',
        'visibility' => 'private',
    ]);
    $poem->user_id = $bob->id;
    $poem->status = 'draft';
    $poem->save();
    $poem->refresh();

    // Alice tries to read → 403
    $this->actingAs($alice, 'sanctum')
        ->getJson("/api/v1/user-poems/{$poem->uuid}")
        ->assertStatus(403);

    // Alice tries to update → 403
    $this->actingAs($alice, 'sanctum')
        ->patchJson("/api/v1/user-poems/{$poem->uuid}", ['title_ar' => 'اختراق'])
        ->assertStatus(403);

    // Alice tries to delete → 403
    $this->actingAs($alice, 'sanctum')
        ->deleteJson("/api/v1/user-poems/{$poem->uuid}")
        ->assertStatus(403);

    // Bob (the owner) can read/update/delete
    $this->actingAs($bob, 'sanctum')
        ->getJson("/api/v1/user-poems/{$poem->uuid}")
        ->assertStatus(200);

    $this->actingAs($bob, 'sanctum')
        ->deleteJson("/api/v1/user-poems/{$poem->uuid}")
        ->assertStatus(204);
});
