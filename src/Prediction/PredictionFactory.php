<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Prediction;

use Closure;
use DanielCHood\BaseballMatchupComparisonPredictions\Analysis;
use DanielCHood\BaseballMatchupComparisonPredictions\Result\ResultInterface;

readonly class PredictionFactory {
    public function __construct(
        public string $name,
        public array  $criteria,
        public ResultInterface $win,
    ) {

    }

    public function build(
        Analysis $analysis,
    ): PredictionInterface {
        return new Prediction($this->name, $analysis, $this->criteria, $this->win);
    }
}