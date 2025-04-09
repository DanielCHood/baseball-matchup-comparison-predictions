<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Criteria;

use DanielCHood\BaseballMatchupComparisonPredictions\Analyzers\AnalyzerInterface;

class MinimumHitScoreMinusBattingAverage implements CriteriaInterface {
    public function __construct(
        public readonly string $field,
        public readonly mixed $value,
    ) {

    }

    public function isValid(AnalyzerInterface $analysis): bool {
        return $this->getFieldValue($analysis) > $this->value;
    }

    private function getFieldValue(AnalyzerInterface $analysis): mixed {
        return abs($analysis->getHitScore() - ($analysis->getBattingAverage() * 10));
    }
}