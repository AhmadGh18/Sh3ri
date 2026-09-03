<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('returns 403 to non-admin on /admin/submissions', function () {
    $user = User::factory()->create()->assignRole('user');
    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/admin/submissions')
        ->assertStatus(403);
});

it('returns 200 to admin on /admin/submissions', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/submissions')
        ->assertStatus(200);
});
