<?php

require_once('vendor/autoload.php');

use DanielCHood\BaseballMatchupComparison\Repository\Event;
use DanielCHood\BaseballMatchupComparisonPredictions\Container;
use DanielCHood\BaseballMatchupComparisonPredictions\Prediction\PredictionFactory;
use DanielCHood\BaseballMatchupComparisonPredictions\Prediction\PredictionInterface;
use DanielCHood\BaseballMatchupComparisonPredictions\Result\HomeRun;
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

unset($dateRanges[2025]);
#unset($dateRanges[2022], $dateRanges[2023], $dateRanges[2025]);

$dates = [];
foreach ($dateRanges as $dateRange) {
    $period = new DatePeriod($dateRange[0], new DateInterval('P1D'), $dateRange[1]);
    foreach ($period as $date) {
        $dates[] = $date;
    }
}

$predicters = [];

$configPredictors = $container->getParameter("predictors");
foreach ($configPredictors as $predictorDefinition) {
    /** @var PredictionFactory $predicter */
    $predicter = $container->get($predictorDefinition);
    $predicters[] = [
        'name' => 'config-' . $predicter->name,
        'analyzer' => $predicter->analyzer,
        'criteria' => $predicter->criteria,
        'win' => $predicter->win,
    ];
}

foreach ($predicters as $predicter) {
    $predicters[] = [
        'name' => $predicter['name'] . '-AnyPitcher',
        'analyzer' => $predicter['analyzer'],
        'criteria' => $predicter['criteria'],
        'win' => new HomeRun(false),
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
                $predict = (new PredictionFactory($predicter['name'], $predicter['analyzer'], $predicter['criteria'], $predicter['win']))->build(
                    $matchup,
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