<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Prediction;

use Closure;
use DanielCHood\BaseballMatchupComparisonPredictions\Analysis;
use DanielCHood\BaseballMatchupComparisonPredictions\Criteria\CriteriaInterface;
use DanielCHood\BaseballMatchupComparisonPredictions\Result\ResultInterface;

readonly class Prediction implements PredictionInterface {
    public function __construct(
        public string $name,
        protected Analysis $analysis,
        protected array $criteria,
        private ResultInterface $win,
    ) {
    }

    public function toArray(): array {
        return array_merge(
            $this->analysis->toArray(),
            [
                'label' => $this->getLabel(),
                'won' => $this->win(),
            ]
        );
    }

    public function isValid(): bool {
        foreach ($this->criteria as $closure) {
            if ($closure instanceof CriteriaInterface) {
                if (!$closure->isValid($this->analysis)) {
                    return false;
                }
            }
            else {
                if (!$closure($this->analysis)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getLabel(): string {
        return $this->name;
    }

    public function win(): bool {
        return $this->win->won($this->analysis->matchup());
    }

    public function analysis(): Analysis {
        return $this->analysis;
    }
}