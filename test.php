<?php

require_once __DIR__ . '/vendor/autoload.php';

date_default_timezone_set("Europe/Moscow");

use AJUR\Toolkit\Weather;
use AJUR\Toolkit\WeatherConstants;

if ($argc === 1) {
    echo "Use " . __FILE__ . " <api key> ", PHP_EOL;
    die(1);
}

$api_key = $argv[1];

Weather::init($api_key);

$data = Weather::fetchWeatherGroupDebug( WeatherConstants::outer_regions );

$data = [
    'update_ts'     =>  time(),
    'update_time'   =>  (new \DateTime())->format('Y-m-d H-i-s'),
    'data'          =>  $data
];

file_put_contents('weather.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION ), LOCK_EX);
