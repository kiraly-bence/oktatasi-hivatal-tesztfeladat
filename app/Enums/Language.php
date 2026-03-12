<?php

declare(strict_types=1);

namespace App\Enums;

enum Language: string
{
    case English = 'angol';
    case German = 'német';
    case French = 'francia';
    case Italian = 'olasz';
    case Russian = 'orosz';
    case Spanish = 'spanyol';
}