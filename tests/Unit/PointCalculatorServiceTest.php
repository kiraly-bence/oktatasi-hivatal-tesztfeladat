<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\BonusPoint;
use App\DTOs\ExamResult;
use App\DTOs\PointCalculatorData;
use App\DTOs\SelectedCourse;
use App\Enums\BonusPointCategory;
use App\Enums\ExamType;
use App\Enums\Language;
use App\Enums\LanguageExamType;
use App\Enums\Subject;
use App\Exceptions\PointCalculationException;
use App\Services\PointCalculatorService;
use PHPUnit\Framework\TestCase;

class PointCalculatorServiceTest extends TestCase
{
    private PointCalculatorService $service;

    protected function setUp(): void
    {
        $this->service = new PointCalculatorService();
    }

    private function makeElteIkCourse(): SelectedCourse
    {
        return new SelectedCourse('ELTE', 'IK', 'Programtervező informatikus');
    }

    private function makePpkeBtkCourse(): SelectedCourse
    {
        return new SelectedCourse('PPKE', 'BTK', 'Anglisztika');
    }

    private function makeRequiredSubjects(): array
    {
        return [
            new ExamResult(Subject::Hungarian, ExamType::Intermediate, 70),
            new ExamResult(Subject::History, ExamType::Intermediate, 80),
            new ExamResult(Subject::Mathematics, ExamType::Intermediate, 90),
        ];
    }

    public function test_calculates_base_points_correctly_for_elte_ik(): void
    {
        $data = new PointCalculatorData(
            selectedCourse: $this->makeElteIkCourse(),
            examResults: [
                ...$this->makeRequiredSubjects(),
                new ExamResult(Subject::InformationTechnology, ExamType::Intermediate, 95),
            ],
            bonusPoints: [],
        );

        $this->assertSame(370, $this->service->calculate($data));
    }

    public function test_picks_best_required_elective_subject(): void
    {
        $data = new PointCalculatorData(
            selectedCourse: $this->makeElteIkCourse(),
            examResults: [
                ...$this->makeRequiredSubjects(),
                new ExamResult(Subject::InformationTechnology, ExamType::Intermediate, 60),
                new ExamResult(Subject::Physics, ExamType::Intermediate, 80),
            ],
            bonusPoints: [],
        );

        $this->assertSame(340, $this->service->calculate($data));
    }

    public function test_base_points_capped_at_400(): void
    {
        $data = new PointCalculatorData(
            selectedCourse: $this->makeElteIkCourse(),
            examResults: [
                ...$this->makeRequiredSubjects(),
                new ExamResult(Subject::InformationTechnology, ExamType::Intermediate, 100),
            ],
            bonusPoints: [],
        );

        $this->assertSame(380, $this->service->calculate($data));
    }

    public function test_base_points_not_exceeding_400(): void
    {
        $data = new PointCalculatorData(
            selectedCourse: $this->makeElteIkCourse(),
            examResults: [
                new ExamResult(Subject::Hungarian, ExamType::Intermediate, 100),
                new ExamResult(Subject::History, ExamType::Intermediate, 100),
                new ExamResult(Subject::Mathematics, ExamType::Intermediate, 100),
                new ExamResult(Subject::InformationTechnology, ExamType::Intermediate, 100),
            ],
            bonusPoints: [],
        );

        $this->assertSame(400, $this->service->calculate($data));
    }

    public function test_calculates_b2_language_exam_bonus(): void
    {
        $data = new PointCalculatorData(
            selectedCourse: $this->makeElteIkCourse(),
            examResults: [
                ...$this->makeRequiredSubjects(),
                new ExamResult(Subject::InformationTechnology, ExamType::Intermediate, 95),
            ],
            bonusPoints: [
                new BonusPoint(BonusPointCategory::LanguageExam, LanguageExamType::B2, Language::English),
            ],
        );

        $this->assertSame(398, $this->service->calculate($data));
    }

    public function test_calculates_c1_language_exam_bonus(): void
    {
        $data = new PointCalculatorData(
            selectedCourse: $this->makeElteIkCourse(),
            examResults: [
                ...$this->makeRequiredSubjects(),
                new ExamResult(Subject::InformationTechnology, ExamType::Intermediate, 95),
            ],
            bonusPoints: [
                new BonusPoint(BonusPointCategory::LanguageExam, LanguageExamType::C1, Language::English),
            ],
        );

        $this->assertSame(410, $this->service->calculate($data));
    }

    public function test_calculates_language_exams_for_different_languages(): void
    {
        $data = new PointCalculatorData(
            selectedCourse: $this->makeElteIkCourse(),
            examResults: [
                ...$this->makeRequiredSubjects(),
                new ExamResult(Subject::InformationTechnology, ExamType::Intermediate, 95),
            ],
            bonusPoints: [
                new BonusPoint(BonusPointCategory::LanguageExam, LanguageExamType::B2, Language::English),
                new BonusPoint(BonusPointCategory::LanguageExam, LanguageExamType::C1, Language::German),
            ],
        );

        $this->assertSame(438, $this->service->calculate($data));
    }

    public function test_calculates_advanced_exam_bonus(): void
    {
        $data = new PointCalculatorData(
            selectedCourse: $this->makeElteIkCourse(),
            examResults: [
                ...$this->makeRequiredSubjects(),
                new ExamResult(Subject::InformationTechnology, ExamType::Advanced, 95),
            ],
            bonusPoints: [],
        );

        $this->assertSame(420, $this->service->calculate($data));
    }

    public function test_bonus_points_capped_at_100(): void
    {
        $data = new PointCalculatorData(
            selectedCourse: $this->makeElteIkCourse(),
            examResults: [
                new ExamResult(Subject::Hungarian, ExamType::Advanced, 70),
                new ExamResult(Subject::History, ExamType::Advanced, 80),
                new ExamResult(Subject::Mathematics, ExamType::Advanced, 90),
                new ExamResult(Subject::InformationTechnology, ExamType::Advanced, 95),
            ],
            bonusPoints: [
                new BonusPoint(BonusPointCategory::LanguageExam, LanguageExamType::C1, Language::English),
                new BonusPoint(BonusPointCategory::LanguageExam, LanguageExamType::C1, Language::German),
            ],
        );

        $this->assertSame(470, $this->service->calculate($data));
    }

    public function test_calculates_points_for_ppke_btk_anglisztika(): void
    {
        $data = new PointCalculatorData(
            selectedCourse: $this->makePpkeBtkCourse(),
            examResults: [
                ...$this->makeRequiredSubjects(),
                new ExamResult(Subject::English, ExamType::Advanced, 90),
            ],
            bonusPoints: [],
        );

        $this->assertSame(390, $this->service->calculate($data));
    }

    public function test_score_exactly_at_passing_threshold_is_valid(): void
    {
        $data = new PointCalculatorData(
            selectedCourse: $this->makeElteIkCourse(),
            examResults: [
                new ExamResult(Subject::Hungarian, ExamType::Intermediate, 20),
                new ExamResult(Subject::History, ExamType::Intermediate, 80),
                new ExamResult(Subject::Mathematics, ExamType::Intermediate, 90),
                new ExamResult(Subject::InformationTechnology, ExamType::Intermediate, 95),
            ],
            bonusPoints: [],
        );

        $this->assertSame(370, $this->service->calculate($data));
    }

    public function test_throws_when_required_advanced_subject_is_missing_for_ppke_btk(): void
    {
        $this->expectException(PointCalculationException::class);

        $data = new PointCalculatorData(
            selectedCourse: $this->makePpkeBtkCourse(),
            examResults: [
                new ExamResult(Subject::Hungarian, ExamType::Intermediate, 70),
                new ExamResult(Subject::Mathematics, ExamType::Intermediate, 80),
                new ExamResult(Subject::History, ExamType::Intermediate, 85),
            ],
            bonusPoints: [],
        );

        $this->service->calculate($data);
    }

    public function test_history_works_as_elective_for_ppke_btk(): void
    {
        $data = new PointCalculatorData(
            selectedCourse: $this->makePpkeBtkCourse(),
            examResults: [
                new ExamResult(Subject::Hungarian, ExamType::Intermediate, 70),
                new ExamResult(Subject::Mathematics, ExamType::Intermediate, 80),
                new ExamResult(Subject::English, ExamType::Advanced, 90),
                new ExamResult(Subject::History, ExamType::Intermediate, 85),
            ],
            bonusPoints: [],
        );

        $this->assertSame(400, $this->service->calculate($data));
    }

    public function test_throws_when_duplicate_subject(): void
    {
        $this->expectException(PointCalculationException::class);

        $data = new PointCalculatorData(
            selectedCourse: $this->makeElteIkCourse(),
            examResults: [
                new ExamResult(Subject::Hungarian, ExamType::Intermediate, 70),
                new ExamResult(Subject::Hungarian, ExamType::Intermediate, 80),
                new ExamResult(Subject::History, ExamType::Intermediate, 80),
                new ExamResult(Subject::Mathematics, ExamType::Intermediate, 90),
                new ExamResult(Subject::InformationTechnology, ExamType::Intermediate, 95),
            ],
            bonusPoints: [],
        );

        $this->service->calculate($data);
    }

    public function test_throws_when_duplicate_language(): void
    {
        $this->expectException(PointCalculationException::class);

        $data = new PointCalculatorData(
            selectedCourse: $this->makeElteIkCourse(),
            examResults: [
                ...$this->makeRequiredSubjects(),
                new ExamResult(Subject::InformationTechnology, ExamType::Intermediate, 95),
            ],
            bonusPoints: [
                new BonusPoint(BonusPointCategory::LanguageExam, LanguageExamType::B2, Language::English),
                new BonusPoint(BonusPointCategory::LanguageExam, LanguageExamType::C1, Language::English),
            ],
        );

        $this->service->calculate($data);
    }

    public function test_throws_when_english_is_not_advanced_for_ppke_btk(): void
    {
        $this->expectException(PointCalculationException::class);

        $data = new PointCalculatorData(
            selectedCourse: $this->makePpkeBtkCourse(),
            examResults: [
                ...$this->makeRequiredSubjects(),
                new ExamResult(Subject::English, ExamType::Intermediate, 90),
                new ExamResult(Subject::History, ExamType::Intermediate, 80),
            ],
            bonusPoints: [],
        );

        $this->service->calculate($data);
    }

    public function test_throws_when_required_subject_is_missing(): void
    {
        $this->expectException(PointCalculationException::class);

        $data = new PointCalculatorData(
            selectedCourse: $this->makeElteIkCourse(),
            examResults: [
                new ExamResult(Subject::History, ExamType::Intermediate, 80),
                new ExamResult(Subject::Mathematics, ExamType::Intermediate, 90),
                new ExamResult(Subject::InformationTechnology, ExamType::Intermediate, 95),
            ],
            bonusPoints: [],
        );

        $this->service->calculate($data);
    }

    public function test_throws_when_score_is_below_passing(): void
    {
        $this->expectException(PointCalculationException::class);

        $data = new PointCalculatorData(
            selectedCourse: $this->makeElteIkCourse(),
            examResults: [
                new ExamResult(Subject::Hungarian, ExamType::Intermediate, 15),
                new ExamResult(Subject::History, ExamType::Intermediate, 80),
                new ExamResult(Subject::Mathematics, ExamType::Intermediate, 90),
                new ExamResult(Subject::InformationTechnology, ExamType::Intermediate, 95),
            ],
            bonusPoints: [],
        );

        $this->service->calculate($data);
    }

    public function test_throws_when_no_required_elective_subject_present(): void
    {
        $this->expectException(PointCalculationException::class);

        $data = new PointCalculatorData(
            selectedCourse: $this->makeElteIkCourse(),
            examResults: $this->makeRequiredSubjects(),
            bonusPoints: [],
        );

        $this->service->calculate($data);
    }

    public function test_throws_when_unknown_course(): void
    {
        $this->expectException(PointCalculationException::class);

        $data = new PointCalculatorData(
            selectedCourse: new SelectedCourse('UNKNOWN', 'XX', 'Unknown course'),
            examResults: [
                ...$this->makeRequiredSubjects(),
                new ExamResult(Subject::InformationTechnology, ExamType::Intermediate, 95),
            ],
            bonusPoints: [],
        );

        $this->service->calculate($data);
    }
}