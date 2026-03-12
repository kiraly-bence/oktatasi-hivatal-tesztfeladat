<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\BonusPoint;
use App\DTOs\ExamResult;
use App\DTOs\PointCalculatorData;
use App\Enums\BonusPointCategory;
use App\Enums\ExamType;
use App\Enums\LanguageExamType;
use App\Enums\Subject;
use App\Exceptions\PointCalculationException;

class PointCalculatorService
{
    private const MIN_PASSING_SCORE = 20;
    private const MAX_BONUS_POINTS = 100;
    private const MAX_BASE_POINTS = 400;
    private const ADVANCED_EXAM_POINTS = 50;

    private const LANGUAGE_EXAM_POINTS = [
        LanguageExamType::B2->value => 28,
        LanguageExamType::C1->value => 40,
    ];

    private const REQUIRED_SUBJECTS = [
        Subject::Hungarian,
        Subject::History,
        Subject::Mathematics,
    ];

    private const COURSE_REQUIREMENTS = [
        'ELTE' => [
            'IK' => [
                'Programtervező informatikus' => [
                    'required' => [
                        Subject::Mathematics,
                    ],
                    'elective' => [
                        Subject::Biology,
                        Subject::Physics,
                        Subject::InformationTechnology,
                        Subject::Chemistry,
                    ],
                ],
            ],
        ],
        'PPKE' => [
            'BTK' => [
                'Anglisztika' => [
                    'required' => [
                        Subject::English,
                    ],
                    'elective' => [
                        Subject::French,
                        Subject::German,
                        Subject::Italian,
                        Subject::Russian,
                        Subject::Spanish,
                        Subject::History,
                    ],
                    'required_advanced' => [
                        Subject::English,
                    ],
                ],
            ],
        ],
    ];

    public function calculate(PointCalculatorData $data): int
    {
        $this->validateNoDuplicateSubjects($data->examResults);
        $this->validateNoDuplicateLanguages($data->bonusPoints);
        $this->validateRequiredSubjects($data->examResults);
        $this->validatePassingScores($data->examResults);

        $requirements = $this->getCourseRequirements(
            $data->selectedCourse->university,
            $data->selectedCourse->faculty,
            $data->selectedCourse->course,
        );

        $basePoints = $this->calculateBasePoints($data->examResults, $requirements);
        $bonusPoints = $this->calculateBonusPoints($data->examResults, $data->bonusPoints);

        return $basePoints + $bonusPoints;
    }

    private function validateNoDuplicateSubjects(array $examResults): void
{
    $names = array_map(fn(ExamResult $e) => $e->name, $examResults);
    $duplicates = array_filter(array_count_values(array_column($names, 'value')), fn($count) => $count > 1);

    if (!empty($duplicates)) {
        $subject = array_key_first($duplicates);
        throw new PointCalculationException("Ugyanabból a tantárgyból nem lehet kétszer érettségizni: {$subject}");
    }
}

private function validateNoDuplicateLanguages(array $bonusPoints): void
{
    $languages = array_map(fn(BonusPoint $b) => $b->language->value, $bonusPoints);
    $duplicates = array_filter(array_count_values($languages), fn($count) => $count > 1);

    if (!empty($duplicates)) {
        $language = array_key_first($duplicates);
        throw new PointCalculationException("Ugyanabból a nyelvből nem lehet kétszer nyelvvizsgát megadni: {$language}");
    }
}

    private function validateRequiredSubjects(array $examResults): void
    {
        $subjects = array_map(fn(ExamResult $e) => $e->name, $examResults);

        foreach (self::REQUIRED_SUBJECTS as $required) { 
            if (!in_array($required, $subjects)) {
                throw new PointCalculationException("Hiányzó kötelező érettségi tárgy: {$required->value}");
            }
        }
    }

    private function validatePassingScores(array $examResults): void
    {
        foreach ($examResults as $examResult) {
            if ($examResult->score < self::MIN_PASSING_SCORE) {
                throw new PointCalculationException("Sikertelen érettségi: {$examResult->name->value} ({$examResult->score}%)");
            }
        }
    }

    private function getCourseRequirements(string $university, string $faculty, string $course): array
    {
        if (!isset(self::COURSE_REQUIREMENTS[$university][$faculty][$course])) {
            throw new PointCalculationException("Ismeretlen szak: {$university} {$faculty} - {$course}");
        }

        return self::COURSE_REQUIREMENTS[$university][$faculty][$course];
    }

    private function calculateBasePoints(array $examResults, array $requirements): int
    {
        $required = $requirements['required'];
        $elective = $requirements['elective'];
        $requiredAdvanced = $requirements['required_advanced'] ?? [];

        $examResultsByName = $this->indexByName($examResults);

        foreach ($required as $subject) {
            if (!isset($examResultsByName[$subject->value])) {
                throw new PointCalculationException("Hiányzó kötelező tárgy: {$subject->value}");
            }
            if (in_array($subject, $requiredAdvanced) && $examResultsByName[$subject->value]->type !== ExamType::Advanced) {
                throw new PointCalculationException("A(z) {$subject->value} tárgyat emelt szinten kell teljesíteni");
            }
        }

        $electiveScores = [];
        foreach ($elective as $subject) {
            if (isset($examResultsByName[$subject->value])) {
                $electiveScores[] = $examResultsByName[$subject->value]->score;
            }
        }

        if (empty($electiveScores)) {
            throw new PointCalculationException("Nincs teljesített kötelezően választható tárgy");
        }

        $requiredScore = array_sum(array_map(fn($subject) => $examResultsByName[$subject->value]->score, $required));
        $bestElectiveScore = max($electiveScores);

        return min(($requiredScore + $bestElectiveScore) * 2, self::MAX_BASE_POINTS);
    }

    private function calculateBonusPoints(array $examResults, array $bonusPoints): int
    {
        $total = 0;
        $total += $this->calculateLanguageExamPoints($bonusPoints);
        $total += $this->calculateAdvancedExamPoints($examResults);

        return min($total, self::MAX_BONUS_POINTS);
    }

    private function calculateLanguageExamPoints(array $bonusPoints): int
    {
        $byLanguage = [];

        foreach ($bonusPoints as $bonusPoint) {
            if ($bonusPoint->category !== BonusPointCategory::LanguageExam) {
                continue;
            }

            $language = $bonusPoint->language->value;
            $points = self::LANGUAGE_EXAM_POINTS[$bonusPoint->type->value] ?? 0;

            if (!isset($byLanguage[$language]) || $points > $byLanguage[$language]) {
                $byLanguage[$language] = $points;
            }
        }

        return array_sum($byLanguage);
    }

    private function calculateAdvancedExamPoints(array $examResults): int
    {
        $points = 0;

        foreach ($examResults as $examResult) {
            if ($examResult->type === ExamType::Advanced) {
                $points += self::ADVANCED_EXAM_POINTS;
            }
        }

        return $points;
    }

    private function indexByName(array $examResults): array
    {
        $indexed = [];
        foreach ($examResults as $examResult) {
            $indexed[$examResult->name->value] = $examResult;
        }
        return $indexed;
    }
}