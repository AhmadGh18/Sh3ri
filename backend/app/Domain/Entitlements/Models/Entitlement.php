<?php

declare(strict_types=1);

namespace App\Domain\Entitlements\Models;

use App\Enums\EntitlementSource;
use App\Enums\EntitlementStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entitlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'product_code', 'source', 'status',
        'starts_at', 'ends_at', 'original_transaction_id', 'raw_receipt',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'raw_receipt' => 'array',
        'source' => EntitlementSource::class,
        'status' => EntitlementStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== EntitlementStatus::Active) {
            return false;
        }

        return $this->ends_at === null || $this->ends_at->isFuture();
    }
}
