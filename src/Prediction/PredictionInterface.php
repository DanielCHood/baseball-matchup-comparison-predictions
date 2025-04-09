<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Prediction;

use Closure;
use DanielCHood\BaseballMatchupComparisonPredictions\Analyzers\AnalyzerInterface;
use DanielCHood\BaseballMatchupComparisonPredictions\Result\ResultInterface;

interface PredictionInterface {
    public function __construct(
        string $name,
        AnalyzerInterface $analysis,
        array $criteria,
        ResultInterface $win,
    );

    public function isValid(): bool;

    public function getLabel(): string;

    public function win(): bool;

    public function toArray(): array;
}