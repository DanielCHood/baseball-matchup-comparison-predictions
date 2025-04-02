<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Criteria;

use Closure;
use DanielCHood\BaseballMatchupComparisonPredictions\Analysis;

interface CriteriaInterface {
    public function __construct(
        string $field,
        mixed $value,
    );

    public function isValid(Analysis $analysis): bool;
}