<?php

declare(strict_types=1);

namespace App\Enums;

enum EntitlementStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Refunded = 'refunded';
    case Paused = 'paused';
}
