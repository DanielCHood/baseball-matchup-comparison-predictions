<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Analyzers;

use Closure;
use DanielCHood\BaseballMatchupComparison\Matchup;

readonly class AnalyzerFactory {
    public function __construct(
        public string $analyzer,
    ) {

    }

    public function build(
        Matchup $matchup,
    ): AnalyzerInterface {
        $analysis = $this->analyzer;
        return new $analysis($matchup);
    }
}