<?php

declare(strict_types=1);

namespace App\Enums;

enum UserPoemVisibility: string
{
    case Private = 'private';
    case Public = 'public';
}
