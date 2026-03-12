<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Requests\PointCalculatorRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class PointCalculatorRequestTest extends TestCase
{
    private function makeRequest(array $data): PointCalculatorRequest
    {
        $request = Request::create('/pontszamitas', 'POST', content: json_encode($data));
        $request->headers->set('Content-Type', 'application/json');
        return new PointCalculatorRequest($request);
    }

    private function validPayload(): array
    {
        return [
            'valasztott-szak' => [
                'egyetem' => 'ELTE',
                'kar' => 'IK',
                'szak' => 'Programtervező informatikus',
            ],
            'erettsegi-eredmenyek' => [
                [
                    'nev' => 'magyar nyelv és irodalom',
                    'tipus' => 'közép',
                    'eredmeny' => '70%',
                ],
                [
                    'nev' => 'matematika',
                    'tipus' => 'közép',
                    'eredmeny' => '90%',
                ],
            ],
            'tobbletpontok' => [
                [
                    'kategoria' => 'Nyelvvizsga',
                    'tipus' => 'B2',
                    'nyelv' => 'angol',
                ],
            ],
        ];
    }

    public function test_valid_payload_passes_validation(): void
    {
        $request = $this->makeRequest($this->validPayload());
        $this->assertTrue($request->isValid());
        $this->assertEmpty($request->errors());
    }

    public function test_valid_payload_with_empty_bonus_points_passes_validation(): void
    {
        $payload = $this->validPayload();
        $payload['tobbletpontok'] = [];

        $request = $this->makeRequest($payload);
        $this->assertTrue($request->isValid());
    }

    public function test_fails_when_selected_course_is_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['valasztott-szak']);

        $request = $this->makeRequest($payload);
        $this->assertFalse($request->isValid());
        $this->assertArrayHasKey('valasztott-szak', $request->errors());
    }

    public function test_fails_when_university_is_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['valasztott-szak']['egyetem']);

        $request = $this->makeRequest($payload);
        $this->assertFalse($request->isValid());
    }

    public function test_fails_when_exam_results_are_empty(): void
    {
        $payload = $this->validPayload();
        $payload['erettsegi-eredmenyek'] = [];

        $request = $this->makeRequest($payload);
        $this->assertFalse($request->isValid());
    }

    public function test_fails_when_exam_type_is_invalid(): void
    {
        $payload = $this->validPayload();
        $payload['erettsegi-eredmenyek'][0]['tipus'] = 'invalid';

        $request = $this->makeRequest($payload);
        $this->assertFalse($request->isValid());
    }

    public function test_fails_when_exam_score_format_is_invalid(): void
    {
        $payload = $this->validPayload();
        $payload['erettsegi-eredmenyek'][0]['eredmeny'] = '70';

        $request = $this->makeRequest($payload);
        $this->assertFalse($request->isValid());
    }

    public function test_fails_when_exam_name_is_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['erettsegi-eredmenyek'][0]['nev']);

        $request = $this->makeRequest($payload);
        $this->assertFalse($request->isValid());
    }

    public function test_fails_when_bonus_point_type_is_invalid(): void
    {
        $payload = $this->validPayload();
        $payload['tobbletpontok'][0]['tipus'] = 'A1';

        $request = $this->makeRequest($payload);
        $this->assertFalse($request->isValid());
    }

    public function test_fails_when_bonus_point_category_is_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['tobbletpontok'][0]['kategoria']);

        $request = $this->makeRequest($payload);
        $this->assertFalse($request->isValid());
    }

    public function test_fails_when_body_is_empty(): void
    {
        $request = $this->makeRequest([]);
        $this->assertFalse($request->isValid());
        $this->assertNotEmpty($request->errors());
    }
}