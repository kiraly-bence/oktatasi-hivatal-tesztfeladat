<?php

declare(strict_types=1);

namespace App\Enums;

enum ExamType: string
{
    case Intermediate = 'közép';
    case Advanced = 'emelt';
}