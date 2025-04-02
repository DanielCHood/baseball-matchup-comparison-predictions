<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Result;

use Closure;
use DanielCHood\BaseballMatchupComparison\Matchup;
use DanielCHood\BaseballMatchupComparisonPredictions\Analysis;

interface ResultInterface {
    public function won(Matchup $matchup): bool;
}