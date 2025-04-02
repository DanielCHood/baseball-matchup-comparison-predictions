<?php

require_once('vendor/autoload.php');

use DanielCHood\BaseballMatchupComparison\Repository\Event;
use DanielCHood\BaseballMatchupComparisonPredictions\Container;
use DanielCHood\BaseballMatchupComparisonPredictions\Analysis;
use DanielCHood\BaseballMatchupComparisonPredictions\Prediction\PredictionFactory;
use DanielCHood\BaseballMatchupComparisonPredictions\Prediction\PredictionInterface;
use Kodus\Cache\FileCache;

const CACHE_LAST_DATE_PROCESSED_KEY = 'backdate.last-date-processed';
const CACHE_BACKDATE_PREDICTION_COLLECTION = 'backdate.prediction-collection';

$container = Container::getInstance(
    __DIR__ . '/.env',
    __DIR__ . '/config'
);

/** @var Event $repo */
$repo = $container->get("machup_comparison.event_repository");

/** @var FileCache $cache */
$cache = $container->get("cache");

$iterations = 0;

$groups = [];

$dateRanges = [
    2022 => [
        new DateTime('2022-04-07'),
        new DateTime('2022-10-05'),
    ],
    2023 => [
        new DateTime('2023-04-04'),
        new DateTime('2023-10-01'),
    ],
    2024 => [
        new DateTime('2024-04-01'),
        new DateTime('2024-09-29'),
    ],
    2025 => [
        new DateTime('2025-03-27'),
        new DateTime('yesterday'),
    ],
];

unset($dateRanges[2022], $dateRanges[2023]);

$dates = [];
foreach ($dateRanges as $dateRange) {
    $period = new DatePeriod($dateRange[0], new DateInterval('P1D'), $dateRange[1]);
    foreach ($period as $date) {
        $dates[] = $date;
    }
}

$predicters = [
    [
        'name' => 'underdog;hrScore>0.15;pitchesSeen>400;pitchesThrown>400;velocityScore>2;hrPercentage>1.5;battingAverage>0.2',
        'criteria' => [
            fn (Analysis $predictor) => $predictor->getHomeRunScore() > .15,
            fn (Analysis $predictor) => $predictor->getBatterPitchCount() >= 400,
            fn (Analysis $predictor) => $predictor->getPitcherPitchCount() > 400,
            fn (Analysis $predictor) => $predictor->getHitScore() > 0.0000,
            fn (Analysis $predictor) => $predictor->matchup()->getBatterMoneyline() < 0,
            fn (Analysis $predictor) => $predictor->getVelocityScore() > 2,
            fn (Analysis $predictor) => $predictor->getBatterHomeRunPercentage() > 1.5,
            fn (Analysis $predictor) => $predictor->getBattingAverage() >= 0.2000,
            /**
            function (Analysis $predictor) {
                return (
                        floor($predictor->getHitScore()) > 1
                        && round($predictor->getHomeRunScore() * 10) >= floor($predictor->getHitScore())
                    )
                    || floor($predictor->getHitScore()) >= 4;
            },
            */
        ],
        'win' => fn (Analysis $predictor) => $predictor->matchup()->didHomer(true),
    ],
    [
        'name' => 'favorite;ml>110;pitchesSeen>400;pitchesThrown>400;velocity>0;hrPercentage>1.8;pitcherHrPercentage>1.1;hitScore>0',
        'criteria' => [
            fn (Analysis $predictor) => $predictor->matchup()->getBatterMoneyline() > 110,
            fn (Analysis $predictor) => $predictor->getBatterPitchCount() > 400,
            fn (Analysis $predictor) => $predictor->getPitcherPitchCount() > 400,
            fn (Analysis $predictor) => abs($predictor->getHitScore() - ($predictor->getBattingAverage() * 10)) > 1,
            fn (Analysis $predictor) => $predictor->getVelocityScore() > 0.000,
            fn (Analysis $predictor) => $predictor->getBatterHomeRunPercentage() > 1.800,
            fn (Analysis $predictor) => $predictor->getPitcherHomeRunPercentage() > 1.100,
            fn (Analysis $predictor) => $predictor->getHitScore() > 0.0000,
        ],
        'win' => fn (Analysis $predictor) => $predictor->matchup()->didHomer(true),
    ],
];

$configPredictors = $container->getParameter("predictors");
foreach ($configPredictors as $predictorDefinition) {
    /** @var PredictionFactory $predicter */
    $predicter = $container->get($predictorDefinition);
    $predicters[] = [
        'name' => 'config-' . $predicter->name,
        'criteria' => $predicter->criteria,
        'win' => fn (Analysis $predictor) => $predictor->matchup()->didHomer(true),
    ];
}

foreach ($predicters as $predicter) {
    $predicters[] = [
        'name' => $predicter['name'] . '-AnyPitcher',
        'criteria' => $predicter['criteria'],
        'win' => fn (Analysis $predictor) => $predictor->matchup()->didHomer(false),
    ];
}

$cache->deleteMultiple([CACHE_LAST_DATE_PROCESSED_KEY, CACHE_BACKDATE_PREDICTION_COLLECTION]);

$lastOutput = 0;

$lastDate = $cache->get(CACHE_LAST_DATE_PROCESSED_KEY, null);
$predictionCollection = $cache->get(CACHE_BACKDATE_PREDICTION_COLLECTION, []);

if ($lastDate !== null) {
    $dates = array_filter($dates, function ($date) use ($lastDate) {
        return $date->getTimestamp() > $lastDate->getTimestamp();
    });

    echo "Starting at " . $lastDate->format('Y-m-d') . PHP_EOL;
    echo "Predictions found so far: " . count($predictionCollection) . PHP_EOL;
}

foreach ($dates as $date) {
    $eventIds = $repo->getEventIdsOnDate($date);

    $iterations++;

    if (time() - 15 > $lastOutput) {
        $lastOutput = time();
        echo date('Y-m-d H:i:s') . ": working on " . $date->format('Y-m-d') . " (iteration: " . $iterations . "/" . (count($dates)) . ")\n";
    }

    foreach ($eventIds as $eventId) {
        try {
            $matchups = $repo->getAllMatchups($eventId, ['zone;type']);
        } catch (\TypeError $e) {
            echo "Error processing event id: " . $eventId . PHP_EOL;
            continue;
        }

        foreach ($matchups as $matchup) {
            foreach ($predicters as $predicter) {
                /** @var PredictionInterface $predict */
                $predict = (new PredictionFactory($predicter['name'], $predicter['criteria'], $predicter['win']))->build(
                    new Analysis($matchup),
                    $predicter['win'],
                );
                if ($predict->isValid()) {
                    $predictionCollection[] = $predict->toArray();
                }
            }
        }
    }

    $cache->set(CACHE_LAST_DATE_PROCESSED_KEY, $date);
    $cache->set(CACHE_BACKDATE_PREDICTION_COLLECTION, $predictionCollection);
}

echo "Completed" . PHP_EOL;