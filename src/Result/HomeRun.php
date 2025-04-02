<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Result;

use DanielCHood\BaseballMatchupComparison\Matchup;

readonly class HomeRun implements ResultInterface {
    public function __construct(
        private bool $starterOnly
    ) {

    }

    public function won(Matchup $matchup): bool {
        return $matchup->didHomer($this->starterOnly);
    }
}