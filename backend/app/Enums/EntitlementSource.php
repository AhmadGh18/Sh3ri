<?php

declare(strict_types=1);

namespace App\Enums;

enum EntitlementSource: string
{
    case Apple = 'apple';
    case Google = 'google';
    case Stripe = 'stripe';
    case Manual = 'manual';
    case Promo = 'promo';
    case Open = 'open';
}
