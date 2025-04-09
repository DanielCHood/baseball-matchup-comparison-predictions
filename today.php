<?php

require_once('vendor/autoload.php');

use DanielCHood\BaseballMatchupComparison\Matchup;
use DanielCHood\BaseballMatchupComparison\Repository\Event;
use DanielCHood\BaseballMatchupComparisonPredictions\Container;
use DanielCHood\BaseballMatchupComparisonPredictions\Analysis;
use DanielCHood\BaseballMatchupComparisonPredictions\Prediction\PredictionFactory;
use Kodus\Cache\FileCache;

$container = Container::getInstance(
    __DIR__ . '/.env',
    __DIR__ . '/config'
);

/** @var Event $repo */
$repo = $container->get("machup_comparison.event_repository");

/** @var FileCache $cache */
$cache = $container->get("cache");

$today = new DateTime;

$predictors = [];
foreach ($container->getParameter("predictors") as $predictorDefinition) {
    /** @var PredictionFactory $predicter */
    $predictor = $container->get($predictorDefinition);
    $predictors[] = [
        'name' => $predictor->name,
        'criteria' => $predictor->criteria,
        'win' => $predictor->win,
    ];
}

$eventIds = $repo->getEventIdsOnDate($today);

foreach ($eventIds as $eventId) {
    echo "Working on " . $eventId . PHP_EOL;

    try {
        $matchups = $repo->getAllMatchups($eventId, ['zone;type']);
    } catch (\TypeError $e) {
        echo "Error processing event id: " . $eventId . PHP_EOL;
        continue;
    }

    /** @var Matchup $matchup */
    foreach ($matchups as $matchup) {
        $analysis = new Analysis($matchup);

        echo " - Considering " . $matchup->getBatterStats()->getName() . " vs " . $matchup->getPitcherStats()->getName() . PHP_EOL;
        #echo " -- Hit score: " . $analysis->getHitScore() . ", velocity score: " . $analysis->getVelocityScore() . PHP_EOL;

        foreach ($predictors as $predictor) {
            $predict = (new PredictionFactory($predictor['name'], $predictor['criteria'], $predictor['win']))->build(
                $analysis,
            );

            if ($predict->isValid()) {
                var_dump($predict->toArray());
            }
            else {
                #var_dump($predict->getMismatchedCriteria());
            }
        }
    }

}


echo "Completed" . PHP_EOL;