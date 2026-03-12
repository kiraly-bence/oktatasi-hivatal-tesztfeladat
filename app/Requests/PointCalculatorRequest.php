<?php

declare(strict_types=1);

namespace App\Requests;

use App\Enums\BonusPointCategory;
use App\Enums\ExamType;
use App\Enums\Language;
use App\Enums\LanguageExamType;
use App\Enums\Subject;
use Symfony\Component\Validator\Constraints as Assert;

class PointCalculatorRequest extends BaseRequest
{
    protected function constraints(): Assert\Collection
    {
        return new Assert\Collection([
            'valasztott-szak' => new Assert\Collection([
                'egyetem' => [new Assert\NotBlank(), new Assert\Type('string')],
                'kar' => [new Assert\NotBlank(), new Assert\Type('string')],
                'szak' => [new Assert\NotBlank(), new Assert\Type('string')],
            ]),
            'erettsegi-eredmenyek' => [
                new Assert\NotBlank(),
                new Assert\Type('array'),
                new Assert\Count(min: 1),
                new Assert\All([
                    new Assert\Collection([
                        'nev' => [new Assert\NotBlank(), new Assert\Choice(choices: array_column(Subject::cases(), 'value'))],
                        'tipus' => [new Assert\NotBlank(), new Assert\Choice(choices: array_column(ExamType::cases(), 'value'))],
                        'eredmeny' => [new Assert\NotBlank(), new Assert\Regex(
                            pattern: '/^\d+(\.\d+)?%$/',
                            message: 'Százalékos formátumban kell megadni az adatot (pl. 85%)',
                        )],
                    ]),
                ]),
            ],
            'tobbletpontok' => [
                new Assert\NotNull(),
                new Assert\Type('array'),
                new Assert\All([
                    new Assert\Collection([
                        'kategoria' => [new Assert\NotBlank(), new Assert\Choice(choices: array_column(BonusPointCategory::cases(), 'value'))],
                        'tipus' => [new Assert\NotBlank(), new Assert\Choice(choices: array_column(LanguageExamType::cases(), 'value'))],
                        'nyelv' => [new Assert\NotBlank(), new Assert\Choice(choices: array_column(Language::cases(), 'value'))],
                    ]),
                ]),
            ],
        ]);
    }
}