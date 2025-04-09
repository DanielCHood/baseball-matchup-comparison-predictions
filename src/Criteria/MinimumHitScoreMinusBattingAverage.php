<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Criteria;

use DanielCHood\BaseballMatchupComparisonPredictions\Analysis;

class MinimumHitScoreMinusBattingAverage implements CriteriaInterface {
    public function __construct(
        public readonly string $field,
        public readonly mixed $value,
    ) {

    }

    public function isValid(Analysis $analysis): bool {
        return $this->getFieldValue($analysis) > $this->value;
    }

    private function getFieldValue(Analysis $analysis): mixed {
        return abs($analysis->getHitScore() - ($analysis->getBattingAverage() * 10));
    }
}