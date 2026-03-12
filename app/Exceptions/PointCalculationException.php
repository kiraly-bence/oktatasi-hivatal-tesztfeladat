<?php

declare(strict_types=1);

namespace App\Exceptions;

class PointCalculationException extends \RuntimeException
{
    public function __construct(public readonly array $messages)
    {
        parent::__construct();
    }
}