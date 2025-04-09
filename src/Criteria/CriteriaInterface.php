<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Criteria;

use Closure;
use DanielCHood\BaseballMatchupComparisonPredictions\Analyzers\AnalyzerInterface;

interface CriteriaInterface {
    public function __construct(
        string $field,
        mixed $value,
    );

    public function isValid(AnalyzerInterface $analysis): bool;
}