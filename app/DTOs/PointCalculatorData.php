<?php

declare(strict_types=1);

namespace App\DTOs;

class PointCalculatorData
{
    /**
     * @param SelectedCourse $selectedCourse
     * @param ExamResult[] $examResults
     * @param BonusPoint[] $bonusPoints
     */
    public function __construct(
        public readonly SelectedCourse $selectedCourse,
        public readonly array $examResults,
        public readonly array $bonusPoints,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            selectedCourse: SelectedCourse::fromArray($data['valasztott-szak']),
            examResults: array_map(fn($e) => ExamResult::fromArray($e), $data['erettsegi-eredmenyek']),
            bonusPoints: array_map(fn($t) => BonusPoint::fromArray($t), $data['tobbletpontok'] ?? []),
        );
    }
}