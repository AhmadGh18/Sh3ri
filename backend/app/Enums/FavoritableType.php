<?php

declare(strict_types=1);

namespace App\Enums;

enum FavoritableType: string
{
    case Poem = 'poem';
    case Verse = 'verse';
}
