<?php

require_once('vendor/autoload.php');

use DanielCHood\BaseballMatchupComparisonPredictions\Container;
use Kodus\Cache\FileCache;

const CACHE_BACKDATE_PREDICTION_COLLECTION = 'backdate.prediction-collection';

$container = Container::getInstance();

/** @var FileCache $cache */
$cache = $container->get("cache");

$predictionCollection = $cache->get(CACHE_BACKDATE_PREDICTION_COLLECTION, []);

$grouping = ['favorite', 'battingAverage'];

$results = [];

$predictionCollection = array_filter($predictionCollection, function ($row) {
    return ($row['label'] === 'HomeRunStartingPitcher' && $row['battingAverage'] >= 0.200 && (
        ((floor($row['hitScore'])) > 1 && round($row['hrScore'] * 10) >= (floor($row['hitScore'])))
        || (floor($row['hitScore'])) >= 4)
    );
});

usort($predictionCollection, function($a, $b) use ($grouping) {
    if (count($grouping) > 1) {
        return $a[$grouping[0]] <=> $b[$grouping[0]] ?: $a[$grouping[1]] <=> $b[$grouping[1]];
    }

    return $a[$grouping[0]] <=> $b[$grouping[0]];
});

foreach ($predictionCollection as $prediction) {
    $label = '';
    foreach ($grouping as $group) {
        if (in_array($group, ['battingAverage', 'hrScore'])) {
            $prediction[$group] = floor($prediction[$group] * 10);
        } else if (in_array($group, ['hitScore'])) {
            $prediction[$group] = floor($prediction[$group]);
        }

        $label .= $group . '=' . $prediction[$group] . '; ';
    }

    if (!isset($results[$label])) {
        $results[$label] = [
            'wins' => 0,
            'losses' => 0,
        ];
    }

    if ($prediction['won']) {
        $results[$label]['wins'] += 1;
    } else {
        $results[$label]['losses'] += 1;
    }

    $results[$label]['rate'] = $results[$label]['wins'] / ($results[$label]['wins'] + $results[$label]['losses']) * 100;
}

var_dump($results);

$totalWins = array_sum(array_column($results, 'wins'));
$totalLosses = array_sum(array_column($results, 'losses'));

echo "Total: " . $totalWins . "-" . $totalLosses . " (" . number_format(100 * ($totalWins / ($totalWins+$totalLosses)), 2) . "%)" . PHP_EOL;