<?php

namespace AJUR\Toolkit;

use Cmfcmf\OpenWeatherMap\CurrentWeather;

interface WeatherInterface
{
    public static function init($options = [], $logger = null);

    public static function loadLocalWeather($district_id = 0, $source_file = null);

    public static function makeWeatherInfo($id, CurrentWeather $weather):array;

    public static function fetchWeatherGroup(array $region_ids_list):array;

    public static function fetchWeatherGroupDebug(array $region_ids_list):array;
}