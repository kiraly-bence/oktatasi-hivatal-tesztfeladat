<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\BonusPointCategory;
use App\Enums\Language;
use App\Enums\LanguageExamType;

class BonusPoint
{
    public function __construct(
        public readonly BonusPointCategory $category,
        public readonly LanguageExamType $type,
        public readonly Language $language,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            category: BonusPointCategory::from($data['kategoria']),
            type: LanguageExamType::from($data['tipus']),
            language: Language::from($data['nyelv']),
        );
    }
}