<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\ExamType;
use App\Enums\Subject;

class ExamResult
{
    public function __construct(
        public readonly Subject $name,
        public readonly ExamType $type,
        public readonly int $score,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: Subject::from($data['nev']),
            type: ExamType::from($data['tipus']),
            score: (int) rtrim($data['eredmeny'], '%'),
        );
    }
}