<?php

namespace App\Services\Hrm;

use App\Models\Hrm\PerformanceReview;
use App\Models\Hrm\PerformanceScore;
use Illuminate\Support\Collection;

class PerformanceScoreCalculator
{
    public function calculateOverall(PerformanceReview $review): float
    {
        $scores = $review->relationLoaded('scores')
            ? $review->scores
            : $review->scores()->get();

        return $this->calculateFromScores($scores);
    }

    /** @param Collection<int, PerformanceScore> $scores */
    public function calculateFromScores(Collection $scores): float
    {
        $totalWeight = $scores->sum(fn (PerformanceScore $s) => (float) $s->weight);

        if ($totalWeight <= 0) {
            return 0.0;
        }

        $weighted = $scores->sum(function (PerformanceScore $score) {
            if ($score->score === null) {
                return 0.0;
            }

            return ((float) $score->score * (float) $score->weight) / 100;
        });

        return round($weighted, 2);
    }
}
