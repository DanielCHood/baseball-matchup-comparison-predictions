<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Prediction;

use Closure;
use DanielCHood\BaseballMatchupComparison\Matchup;

interface PredictionInterface {
    public function __construct(
        string $name,
        Matchup $matchup,
        array $criteria,
        Closure $win,
    );

    public function isValid(): bool;

    public function getLabel(): string;

    public function win(): bool;

    public function toArray(): array;
}