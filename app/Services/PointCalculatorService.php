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
        $errors = [];

        $errors = array_merge($errors, $this->validateNoDuplicateSubjects($data->examResults));
        $errors = array_merge($errors, $this->validateNoDuplicateLanguages($data->bonusPoints));
        $errors = array_merge($errors, $this->validateRequiredSubjects($data->examResults));
        $errors = array_merge($errors, $this->validatePassingScores($data->examResults));

        try {
            $requirements = $this->getCourseRequirements(
                $data->selectedCourse->university,
                $data->selectedCourse->faculty,
                $data->selectedCourse->course,
            );
            $errors = array_merge($errors, $this->validateCourseRequirements($data->examResults, $requirements));
        } catch (PointCalculationException $e) {
            $errors = array_merge($errors, $e->messages);
        }

        if (!empty($errors)) {
            sort($errors);
            throw new PointCalculationException($errors);
        }

        $basePoints = $this->calculateBasePoints($data->examResults, $requirements);
        $bonusPoints = $this->calculateBonusPoints($data->examResults, $data->bonusPoints);

        return $basePoints + $bonusPoints;
    }

    private function validateNoDuplicateSubjects(array $examResults): array
    {
        $names = array_map(fn(ExamResult $e) => $e->name, $examResults);
        $duplicates = array_filter(array_count_values(array_column($names, 'value')), fn($count) => $count > 1);

        return array_map(
            fn($subject) => "Ugyanabból a tantárgyból nem lehet kétszer érettségizni: {$subject}",
            array_keys($duplicates)
        );
    }

    private function validateNoDuplicateLanguages(array $bonusPoints): array
    {
        $languages = array_map(fn(BonusPoint $b) => $b->language->value, $bonusPoints);
        $duplicates = array_filter(array_count_values($languages), fn($count) => $count > 1);

        return array_map(
            fn($language) => "Ugyanabból a nyelvből nem lehet kétszer nyelvvizsgát megadni: {$language}",
            array_keys($duplicates)
        );
    }

    private function validateRequiredSubjects(array $examResults): array
    {
        $subjects = array_map(fn(ExamResult $e) => $e->name, $examResults);
        $errors = [];

        foreach (self::REQUIRED_SUBJECTS as $required) {
            if (!in_array($required, $subjects)) {
                $errors[] = "Hiányzó kötelező érettségi tárgy: {$required->value}";
            }
        }

        return $errors;
    }

    private function validatePassingScores(array $examResults): array
    {
        $errors = [];

        foreach ($examResults as $examResult) {
            if ($examResult->score < self::MIN_PASSING_SCORE) {
                $errors[] = "Sikertelen érettségi: {$examResult->name->value} ({$examResult->score}%)";
            }
        }

        return $errors;
    }

    private function getCourseRequirements(string $university, string $faculty, string $course): array
    {
        if (!isset(self::COURSE_REQUIREMENTS[$university][$faculty][$course])) {
            throw new PointCalculationException(["Ismeretlen szak: {$university} {$faculty} - {$course}"]);
        }

        return self::COURSE_REQUIREMENTS[$university][$faculty][$course];
    }

    private function validateCourseRequirements(array $examResults, array $requirements): array
    {
        $errors = [];
        $examResultsByName = $this->indexByName($examResults);
        $requiredAdvanced = $requirements['required_advanced'] ?? [];
        $globalRequired = array_map(fn($s) => $s->value, self::REQUIRED_SUBJECTS);

        foreach ($requirements['required'] as $subject) {
            if (in_array($subject->value, $globalRequired)) {
                continue;
            }
            if (!isset($examResultsByName[$subject->value])) {
                $errors[] = "Hiányzó kötelező érettségi tárgy: {$subject->value}";
            } elseif (in_array($subject, $requiredAdvanced) && $examResultsByName[$subject->value]->type !== ExamType::Advanced) {
                $errors[] = "A(z) {$subject->value} tárgyat emelt szinten kell teljesíteni.";
            }
        }

        $electiveScores = array_filter(
            array_map(fn($subject) => $examResultsByName[$subject->value]->score ?? null, $requirements['elective']),
            fn($score) => $score !== null
        );

        if (empty($electiveScores)) {
            $errors[] = "Nincs teljesített kötelezően választható tárgy.";
        }

        return $errors;
    }

    private function calculateBasePoints(array $examResults, array $requirements): int
    {
        $examResultsByName = $this->indexByName($examResults);

        $requiredScore = array_sum(array_map(fn($subject) => $examResultsByName[$subject->value]->score, $requirements['required']));
        $electiveScores = array_filter(
            array_map(fn($subject) => $examResultsByName[$subject->value]->score ?? null, $requirements['elective']),
            fn($score) => $score !== null
        );
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