<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\DTOs\PointCalculatorData;
use App\Exceptions\PointCalculationException;
use App\Services\PointCalculatorService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PointCalculatorController
{
    public function calculate(Request $request, array $data): JsonResponse
    {
        $pointCalculatorService = new PointCalculatorService();
        $pointCalculatorData = PointCalculatorData::fromArray($data);
        
        try {
            $points = $pointCalculatorService->calculate($pointCalculatorData);
        } catch (PointCalculationException $e) {
            return new JsonResponse(['error' => 'Point Calculation Error', 'message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(['points' => $points]);
    }
}