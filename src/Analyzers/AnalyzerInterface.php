<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Analyzers;

use DanielCHood\BaseballMatchupComparison\Matchup;
use JsonSerializable;

interface AnalyzerInterface extends JsonSerializable {
    public function __construct(
        Matchup $matchup
    );


}