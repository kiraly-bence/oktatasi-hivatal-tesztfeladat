<?php

declare(strict_types=1);

namespace App\DTOs;

class SelectedCourse
{
    public function __construct(
        public readonly string $university,
        public readonly string $faculty,
        public readonly string $course,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            university: $data['egyetem'],
            faculty: $data['kar'],
            course: $data['szak'],
        );
    }
}