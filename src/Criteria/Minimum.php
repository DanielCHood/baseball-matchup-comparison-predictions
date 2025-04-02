<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Criteria;

use DanielCHood\BaseballMatchupComparisonPredictions\Analysis;

class Minimum implements CriteriaInterface {
    public function __construct(
        private readonly string $field,
        private readonly mixed $value,
    ) {

    }

    public function isValid(Analysis $analysis): bool {
        return $this->getFieldValue($analysis) > $this->value;
    }

    private function getFieldValue(Analysis $analysis): mixed {
        $field = explode('.', $this->field);
        $value = $analysis;

        foreach ($field as $part) {
            $value = $value->{$part}();
        }

        return $value;
    }
}