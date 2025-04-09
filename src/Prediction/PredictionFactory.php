<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Prediction;

use Closure;
use DanielCHood\BaseballMatchupComparison\Matchup;
use DanielCHood\BaseballMatchupComparisonPredictions\Analyzers\AnalyzerFactory;
use DanielCHood\BaseballMatchupComparisonPredictions\Result\ResultInterface;

readonly class PredictionFactory {
    public function __construct(
        public string $name,
        public AnalyzerFactory $analyzer,
        public array  $criteria,
        public ResultInterface $win,
    ) {

    }

    public function build(
        Matchup $matchup
    ): PredictionInterface {
        $analysis = $this->analyzer->build($matchup);

        return new Prediction($this->name, $analysis, $this->criteria, $this->win);
    }
}