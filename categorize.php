<?php

require_once('vendor/autoload.php');

use DanielCHood\BaseballMatchupComparisonPredictions\Container;
use Illuminate\Support\Arr;
use Kodus\Cache\FileCache;

const CACHE_BACKDATE_PREDICTION_COLLECTION = 'backdate.prediction-collection';

$container = Container::getInstance();

/** @var FileCache $cache */
$cache = $container->get("cache");

$predictionCollection = $cache->get(CACHE_BACKDATE_PREDICTION_COLLECTION, []);

$grouping = ['label', 'favorite'];

$results = [];

$predictionCollection = array_filter($predictionCollection, function ($row) {
    return in_array($row['label'], [
        'underdog;hrScore>0.15;pitchesSeen>400;pitchesThrown>400;velocityScore>2;hrPercentage>1.5;battingAverage>0.2',
        'favorite;Experimental'
    ]);
});

$predictionCollection = Arr::map($predictionCollection, fn ($array) => Arr::dot($array));
$predictionCollection = Arr::map($predictionCollection, function ($array) {
    $array['battingAverage_hitScore_difference'] = $array['hitScore'] - ($array['battingAverage'] * 10);
    return $array;
});

usort($predictionCollection, function($a, $b) use ($grouping) {
    if (count($grouping) > 1) {
        return $a[$grouping[0]] <=> $b[$grouping[0]] ?: $a[$grouping[1]] <=> $b[$grouping[1]];
    }

    return $a[$grouping[0]] <=> $b[$grouping[0]];
});

$predictionCollection = array_filter($predictionCollection, function ($row) {
    if ($row['label'] !== 'favorite;Experimental') {
        return true;
    }

    return $row['batterHrPercent'] > 1.800 && $row['pitcherHrPercent'] > 1.100
        && abs($row['hitScore'] - ($row['battingAverage'] * 10)) > 1
        && (
            $row['hrScore'] > 0.0
            && $row['batterPitchCount'] > 400
            && $row['pitcherPitchCount'] > 400
            && $row['ml'] > 110
        );
});

foreach ($predictionCollection as $prediction) {
    $label = '';

    foreach ($grouping as $group) {
        if (in_array($group, ['battingAverage', 'hrScore', 'batterHrPercent', 'pitcherHrPercent'])) {
            $prediction[$group] = floor($prediction[$group] * 10);
        } else if (in_array($group, ['hitScore', 'velocity', 'batterHrPercent', 'pitcherHrPercent'])) {
            $prediction[$group] = floor($prediction[$group]);
        } else if (in_array($group, ['batterPitchCount', 'pitcherPitchCount'])) {
            $prediction[$group] = floor($prediction[$group] / 100);
        } else if (in_array($group, ['ml'])) {
            $prediction[$group] = floor($prediction[$group] / 10);
        } elseif (in_array($group, ['battingAverage_hitScore_difference'])) {
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