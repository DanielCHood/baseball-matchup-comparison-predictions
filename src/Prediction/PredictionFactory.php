<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Prediction;

use Closure;
use DanielCHood\BaseballMatchupComparison\Matchup;

class PredictionFactory {
    public function build(
        string $class,
        string $name,
        Matchup $matchup,
        array $criteria,
        Closure $win,
    ): PredictionInterface {
        return new $class($name, $matchup, $criteria, $win);
    }
}