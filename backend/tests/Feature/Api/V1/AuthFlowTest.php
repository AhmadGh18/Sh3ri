<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('registers, logs in, exposes /me, and revokes on logout', function () {
    // register
    $reg = $this->postJson('/api/v1/auth/register', [
        'name' => 'Bilal',
        'email' => 'bilal@example.com',
        'password' => 'Zw!qF-vg8Jh#Test',
        'password_confirmation' => 'Zw!qF-vg8Jh#Test',
        'device_name' => 'pest-test',
    ]);
    $reg->assertStatus(201);
    $reg->assertJsonPath('data.user.email', 'bilal@example.com');
    $reg->assertJsonPath('data.user.roles.0', 'user');
    $token = $reg->json('data.token');
    expect($token)->toBeString();

    // /me works with token
    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertStatus(200)
        ->assertJsonPath('data.email', 'bilal@example.com');

    // logout-all revokes every token for this user.
    $this->withToken($token)->postJson('/api/v1/auth/logout-all')->assertStatus(204);

    // Assert the token row is gone. (Sanctum's guard memoizes the resolved
    // user within a single RefreshDatabase transaction, so we verify the
    // persistence effect directly instead of chaining another HTTP request.)
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('rejects invalid login with 422 and a uniform error', function () {
    User::factory()->create(['email' => 'x@x.com', 'password' => 'Zw!qF-vg8Jh#Test']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'x@x.com',
        'password' => 'wrong',
        'device_name' => 'pest',
    ])->assertStatus(422)->assertJsonStructure(['error' => ['type', 'message', 'trace_id']]);
});
