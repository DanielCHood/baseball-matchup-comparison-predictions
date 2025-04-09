<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions\Analyzers;

use DanielCHood\BaseballMatchupComparison\Matchup;
use JsonSerializable;

class V1 implements AnalyzerInterface {
    public function __construct(
        protected readonly Matchup $matchup,
    ) {
    }
    
    public function toArray(): array {
        return json_decode(json_encode($this), true);
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
        $pitchCount = max(1, array_sum(array_column($tagged, 'pitchCount')));

        return $homeRunCount / $pitchCount * 100;
    }

    public function getBattingAverage(): float {
        return $this->matchup->getBatterStats()->battingAverage;
    }

    public function getPitcherHomeRunPercentage(): float {
        $tagged = $this->matchup->getPitcherStats()->getTagged();
        $homeRunCount = array_sum(array_column($tagged, 'homeRuns'));
        $pitchCount = max(1, array_sum(array_column($tagged, 'pitchCount')));

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

    public function getHomeRunVelocityScore(): float {
        $score = 0;

        foreach ($this->matchup->getPitcherStats()->getTagged() as $sectionName => $section) {
            $batterSection = $this->matchup->getBatterStats()->getTagged()[$sectionName] ?? null;
            // batter hasn't seen a pitch with this tag yet
            if (!$batterSection) {
                continue;
            }

            $velocity = $section['velocity'];
            $frequency = $section['pitchCount'] / $this->getPitcherPitchCount();

            $batterAverageSeenVelocity = $batterSection['velocityHomeRuns'] ?? 0;
            if ($batterAverageSeenVelocity === 0) {
                continue;
            }

            $score += ($batterAverageSeenVelocity - $velocity) * $frequency;
        }

        return $score;
    }

    public function getHitVelocityScore(): float {
        $score = 0;

        foreach ($this->matchup->getPitcherStats()->getTagged() as $sectionName => $section) {
            $batterSection = $this->matchup->getBatterStats()->getTagged()[$sectionName] ?? null;
            // batter hasn't seen a pitch with this tag yet
            if (!$batterSection) {
                continue;
            }

            $velocity = $section['velocity'];
            $frequency = $section['pitchCount'] / $this->getPitcherPitchCount();

            $batterAverageSeenVelocity = $batterSection['velocityHits'] ?? 0;
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

    public function jsonSerialize(): array {
        return [
            'home' => $this->matchup->homeTeamId === $this->matchup->getBatterStats()->getTeamId() ? 'true' : 'false',
            'favorite' => $this->matchup->getBatterMoneyline() > 0 ? 'true' : 'false',
            'ml' => $this->matchup->getBatterMoneyline(),
            'hitScore' => $this->getHitScore(),
            'hrScore' => $this->getHomeRunScore(),
            'pitcherPitchCount' => $this->getPitcherPitchCount(),
            'batterPitchCount' => $this->getBatterPitchCount(),
            'velocity' => $this->getVelocityScore(),
            'hitVelocity' => $this->getHitVelocityScore(),
            'homeRunVelocity' => $this->getHomeRunVelocityScore(),
            'batterHrPercent' => $this->getBatterHomeRunPercentage(),
            'pitcherHrPercent' => $this->getPitcherHomeRunPercentage(),
            'battingAverage' => $this->getBattingAverage(),
            'pitcher' => $this->matchup->getPitcherStats()->toArray()['athlete'],
            'batter' => $this->matchup->getBatterStats()->toArray()['athlete'],
        ];
    }
}