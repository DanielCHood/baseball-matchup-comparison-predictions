<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Criteria;

use DanielCHood\BaseballMatchupComparisonPredictions\Analyzers\AnalyzerInterface;

class Maximum implements CriteriaInterface {
    public function __construct(
        public readonly string $field,
        public readonly mixed $value,
    ) {

    }

    public function isValid(AnalyzerInterface $analysis): bool {
        return $this->getFieldValue($analysis) < $this->value;
    }

    private function getFieldValue(AnalyzerInterface $analysis): mixed {
        $field = explode('.', $this->field);
        $value = $analysis;

        foreach ($field as $part) {
            $value = $value->{$part}();
        }

        return $value;
    }
}