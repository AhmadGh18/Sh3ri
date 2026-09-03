<?php

declare(strict_types=1);

namespace App\Enums;

enum SubmissionType: string
{
    case Poem = 'poem';
    case Poet = 'poet';
    case Correction = 'correction';
    case Metadata = 'metadata';
}
