<?php

declare(strict_types=1);

namespace App\Enums;

enum Subject: string
{
    case Hungarian = 'magyar nyelv és irodalom';
    case History = 'történelem';
    case Mathematics = 'matematika';
    case English = 'angol nyelv';
    case German = 'német nyelv';
    case French = 'francia nyelv';
    case Italian = 'olasz nyelv';
    case Russian = 'orosz nyelv';
    case Spanish = 'spanyol nyelv';
    case Biology = 'biológia';
    case Physics = 'fizika';
    case InformationTechnology = 'informatika';
    case Chemistry = 'kémia';
}