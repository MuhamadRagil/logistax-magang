<?php

namespace App\Services;

class EvaluationScoringService
{
    private const WEIGHT_DISCIPLINE = 0.25;

    private const WEIGHT_PERFORMANCE = 0.35;

    private const WEIGHT_ATTITUDE = 0.25;

    private const WEIGHT_COMMUNICATION = 0.15;

    public function calculateTotalScore(
        float $disciplineScore,
        float $performanceScore,
        float $attitudeScore,
        float $communicationScore,
    ): float {
        return round(
            ($disciplineScore * self::WEIGHT_DISCIPLINE)
            + ($performanceScore * self::WEIGHT_PERFORMANCE)
            + ($attitudeScore * self::WEIGHT_ATTITUDE)
            + ($communicationScore * self::WEIGHT_COMMUNICATION),
            2
        );
    }

    public function calculateGrade(float $totalScore): string
    {
        return match (true) {
            $totalScore >= 85 => 'A',
            $totalScore >= 70 => 'B',
            $totalScore >= 55 => 'C',
            default => 'D',
        };
    }
}
