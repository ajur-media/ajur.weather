<?php

namespace AJUR\Toolkit;

use AJUR\OpenWeatherMap;
use AJUR\OpenWeatherMap\CurrentWeather;

use Psr\Log\LoggerInterface;

interface WeatherInterface
{
    public static function init(string $api_key = '', array $options = [], LoggerInterface $logger = null);

    public static function loadLocalWeather(int $district_id = 0, $source_file = null):array;

    public static function makeWeatherInfo($id, CurrentWeather $weather):array;

    public static function fetchWeatherGroup(array $regions_list):array;

    public static function fetchWeatherGroupDebug(array $regions_list):array;
}
# -eof-
