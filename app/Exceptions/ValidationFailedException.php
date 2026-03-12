<?php

declare(strict_types=1);

namespace App\Exceptions;

class ValidationFailedException extends \RuntimeException
{
    private array $errors;

    public function __construct(array $errors)
    {
        parent::__construct('Validation Failed');
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}