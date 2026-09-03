<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Poetry\Models\Poem;
use App\Domain\Poetry\Models\Verse;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'google_id',
        'avatar_url',
    ];

    /** Spatie/permission guard — API is Sanctum-only. */
    protected string $guard_name = 'sanctum';

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'deleted_at' => 'datetime',
        ];
    }

    public function userPoems(): HasMany
    {
        return $this->hasMany(\App\Domain\Poetry\Models\UserPoem::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(\App\Domain\Submissions\Models\Submission::class);
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(\App\Domain\Entitlements\Models\Entitlement::class);
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
