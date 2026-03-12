<?php

declare(strict_types=1);

namespace App\Requests;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

abstract class BaseRequest
{
    protected array $data;
    private array $errors = [];

    public function __construct(Request $request)
    {
        $this->data = json_decode($request->getContent(), true) ?? [];
        $this->validate();
    }

    abstract protected function constraints(): Assert\Collection;

    private function validate(): void
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($this->data, $this->constraints());

        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            $field = ltrim($field, '[');
            $field = str_replace('][', '.', $field);
            $field = rtrim($field, ']');

            $this->errors[$field] = $violation->getMessage();
        }
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->data;
    }
}