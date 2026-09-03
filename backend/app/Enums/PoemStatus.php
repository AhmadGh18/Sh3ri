<?php

declare(strict_types=1);

namespace App\Enums;

enum PoemStatus: string
{
    case Published = 'published';
    case Hidden = 'hidden';
    case Quarantined = 'quarantined';
}
