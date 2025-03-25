<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Prediction;

use Closure;
use DanielCHood\BaseballMatchupComparison\Matchup;
use InvalidArgumentException;

class HomeRunStartingPitcher implements PredictionInterface {
    public function __construct(
        public readonly string $name,
        protected readonly Matchup $matchup,
        protected readonly array $criteria,
        private readonly Closure $win,
    ) {
    }
    
    public function toArray(): array {
        return [
            'label' => $this->getLabel(),
            'home' => $this->matchup->homeTeamId === $this->matchup->getBatterStats()->getTeamId() ? 'true' : 'false',
            'favorite' => $this->matchup->getBatterMoneyline() > 0 ? 'true' : 'false',
            'ml' => $this->matchup->getBatterMoneyline(),
            'hitScore' => $this->getHitScore(),
            'hrScore' => $this->getHomeRunScore(),
            'pitcherPitchCount' => $this->getPitcherPitchCount(),
            'batterPitchCount' => $this->getBatterPitchCount(),
            'velocity' => $this->getVelocityScore(),
            'batterHrPercent' => $this->getBatterHomeRunPercentage(),
            'pitcherHrPercent' => $this->getPitcherHomeRunPercentage(),
            'battingAverage' => $this->getBattingAverage(),
            'won' => $this->win()
        ];
    }

    public function isValid(): bool {
        foreach ($this->criteria as $closure) {
            if (!$closure($this)) {
                return false;
            }
        }

        return true;
    }

    public function getLabel(): string {
        return $this->name;
    }

    public function win(): bool {
        return ($this->win)($this);
    }

    public function getHitScore(): float {
        $score = 0;

        foreach ($this->matchup->getPitcherStats()->getTagged() as $sectionName => $section) {
            $batterSection = $this->matchup->getBatterStats()->getTagged()[$sectionName] ?? null;
            // batter hasn't seen a pitch with this tag yet
            if (!$batterSection) {
                continue;
            }

            $score += ($section['hitPercentWeighted'] * $batterSection['hitPercentWeighted']);
        }

        return $score;
    }

    public function getBatterHomeRunPercentage(): float {
        $tagged = $this->matchup->getBatterStats()->getTagged();
        $homeRunCount = array_sum(array_column($tagged, 'homeRuns'));
        $pitchCount = array_sum(array_column($tagged, 'pitchCount'));

        return $homeRunCount / $pitchCount * 100;
    }

    public function getBattingAverage(): float {
        return $this->matchup->getBatterStats()->battingAverage;
    }

    public function getPitcherHomeRunPercentage(): float {
        $tagged = $this->matchup->getPitcherStats()->getTagged();
        $homeRunCount = array_sum(array_column($tagged, 'homeRuns'));
        $pitchCount = array_sum(array_column($tagged, 'pitchCount'));

        return $homeRunCount / $pitchCount * 100;
    }

    public function getHomeRunScore(): float {
        $score = 0;

        foreach ($this->matchup->getPitcherStats()->getTagged() as $sectionName => $section) {
            $batterSection = $this->matchup->getBatterStats()->getTagged()[$sectionName] ?? null;
            // batter hasn't seen a pitch with this tag yet
            if (!$batterSection) {
                continue;
            }

            $score += ($section['homeRunPercentWeighted'] * $batterSection['homeRunPercentWeighted']);
        }

        return $score;
    }


    public function getVelocityScore(): float {
        $score = 0;

        foreach ($this->matchup->getPitcherStats()->getTagged() as $sectionName => $section) {
            $batterSection = $this->matchup->getBatterStats()->getTagged()[$sectionName] ?? null;
            // batter hasn't seen a pitch with this tag yet
            if (!$batterSection) {
                continue;
            }

            $velocity = $section['velocity'];
            $frequency = $section['pitchCount'] / $this->getPitcherPitchCount();

            $batterAverageSeenVelocity = $batterSection['velocity'] ?? 0;
            if ($batterAverageSeenVelocity === 0) {
                continue;
            }

            $score += ($batterAverageSeenVelocity - $velocity) * $frequency;
        }

        return $score;
    }

    public function getBatterPitchCount(): float {
        return array_sum(array_column($this->matchup->getBatterStats()->getTagged(), 'pitchCount'));
    }

    public function getPitcherPitchCount(): float {
        return array_sum(array_column($this->matchup->getPitcherStats()->getTagged(), 'pitchCount'));
    }

    public function matchup(): Matchup {
        return $this->matchup;
    }
}