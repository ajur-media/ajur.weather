<?php

namespace AJUR\Toolkit;

use Exception;
use IteratorIterator;
use ReflectionClass;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use AJUR\OpenWeatherMap;
use AJUR\OpenWeatherMap\CurrentWeather;

class Weather implements WeatherInterface, WeatherConstants
{
    /**
     * @var string
     */
    private static $API_KEY;

    /**
     * @var OpenWeatherMap
     */
    private static $owm;

    /**
     * @var LoggerInterface|null
     */
    private static $logger;

    /**
     * @var string[]
     */
    private static $options = [
        'units'     =>  'metric',
        'lang'      =>  'ru'
    ];
    
    /**
     * Инициализация требуется для загрузки погоды
     *
     * @param string $api_key -- API-ключ для доступа к OpenWeather
     * @param array $options -- 2 опции: units:metric|imperial, lang:ru|en|..
     * @param LoggerInterface $logger -- null или Logger
     * @throws Exception
     */
    public static function init(string $api_key = null, array $options = [], LoggerInterface $logger = null)
    {
        if (!is_null($api_key)) {
            self::$API_KEY = $api_key;
            self::$owm = new OpenWeatherMap($api_key);
        }

        self::$options['units'] = $options['units'] ?? 'metric';
        self::$options['lang'] = $options['lang'] ?? 'ru';
    
        self::$logger
            = $logger instanceof LoggerInterface
            ? $logger
            : new NullLogger();
    }

    /**
     * Загружает данные о погоде из JSON-файла.
     * Структура файла: update_ts:<int>, update_time:<string>, data:<массив по регионам>
     *
     * @param int $district_id
     * @param null $source_file
     * @return array
     */
    public static function loadLocalWeather($district_id = 0, $source_file = null)
    {
        $current_weather = [];

        try {
            if (is_null($source_file))
                throw new Exception("Weather file not defined", self::ERROR_SOURCE_FILE_NOT_DEFINED);

            if (!is_readable($source_file))
                throw new Exception("Weather file `{$source_file}` not found", self::ERROR_SOURCE_FILE_NOT_READABLE);

            $file_content = \file_get_contents($source_file);
            if ($file_content === FALSE)
                throw new Exception("Error reading weather file `{$source_file}`", self::ERROR_SOURCE_FILE_READING_ERROR);

            $file_content = \json_decode($file_content, true);

            if (($file_content === NULL) || !\is_array($file_content))
                throw new Exception("Weather data can't be parsed", self::ERROR_SOURCE_FILE_PARSING_ERROR);

            if (!\array_key_exists('data', $file_content))
                throw new Exception("Weather file does not contain DATA section", self::ERROR_SOURCE_FILE_HAVE_NO_DATA);

            $current_weather = $file_content['data'];

            // Район - 0 (все) ?
            if ($district_id === 0) {
                \shuffle($current_weather);
                return $current_weather; // возвращаем перемешанный массив с погодой
            }

            // Район не равен нулю, нужно построить массив с погодой для указанного района и ближайших:

            // проверим, есть ли такой идентификатор района вообще в массиве кодов районов.
            // Если нет - кидаем исключение (записываем ошибку), но возвращаем массив со случайной погодой
            if (!array_key_exists($district_id, self::map_intid_to_owmid[ self::REGION_LO ]))
                throw new Exception("Given district id ({$district_id}) does not exist in MAP_INTID_TO_OWMID set", self::ERROR_NO_SUCH_DISTRICT_ID);

            /**
             * array_search_callback() аналогичен array_search() , только помогает искать по неодномерному массиву.
             */
            $local_weather = [];

            // первый элемент - погода текущего региона
            $district_owmid = self::map_intid_to_owmid[ self::REGION_LO ][ $district_id ];

            $local_weather[] = self::array_search_callback($current_weather, function ($item) use ($district_owmid) {
                return ($item['id'] == $district_owmid);
            });

            // ближайшие регионы
            foreach (self::lo_adjacency_lists[ $district_id ] as $adjacency_district_id ) {

                $adjacency_district_owmid = self::map_intid_to_owmid[ self::REGION_LO ][ $adjacency_district_id ];

                $local_weather[] = self::array_search_callback($current_weather, function ($item) use ($adjacency_district_owmid){
                    return ($item['id'] == $adjacency_district_owmid);
                });
            }

            return $local_weather;

        } catch (Exception $e) {
                self::$logger->error('[ERROR] Load Weather ',
                    [
                        array_search($e->getCode(), (new ReflectionClass(__CLASS__))->getConstants()),
                        $e->getMessage()
                    ]);
        }

        return $current_weather;
    }

    /**
     * Формирует погоду для экспорта для указанного региона
     *
     * @param $id -- внутренний ID региона
     * @param CurrentWeather $weather
     * @return array
     */
    public static function makeWeatherInfo($id, CurrentWeather $weather):array
    {
        //default values
        $dataset = [
            'id'            =>  $id,
            'name'          =>  self::outer_regions[ $id ]['geoname_ru'],
            'temperature'   =>  0,
            'humidity'      =>  '0 %',  // форматированное, с %
            'pressure_hpa'  =>  0,      // в гектопаскалях, сырое значение
            'pressure_mm'   =>  0,
            'wind_speed'    =>  0,      // м/с, сырое
            'wind_dir_raw'  =>  0,
            'wind_dir'      =>  '',     // направление, аббревиатурой
            'clouds_value'  =>  0,      // облачность (% значение)
            'clouds_text'   =>  '',     // облачность, текстом
            'precipitation' =>  0,      // осадки, сырое значение
            'weather_icon'  =>  '',     // погодная иконка, название
            'weather_icon_url'  =>  '', // погода, текстом
            't'             =>  0,
        ];

        // parse Weather data
        if ($weather->temperature->now) {
            $dataset['temperature'] = $weather->temperature->now->getValue();
            $dataset['t'] = round($dataset['temperature'], 0);
        }

        if ($weather->humidity)
            $dataset['humidity'] = $weather->humidity->getFormatted();

        if ($weather->pressure) {
            $dataset['pressure_hpa'] = $weather->pressure->getValue();
            $dataset['pressure_mm'] = round($dataset['pressure_hpa'] * 0.75006375541921, 0);
        }
        if ($weather->wind->speed)
            $dataset['wind_speed'] = $weather->wind->speed->getValue();

        if ($weather->wind->direction) {
            $dataset['wind_speed'] = $weather->wind->speed->getValue();
            $dataset['wind_dir_raw'] = $weather->wind->direction->getValue();
            $dataset['wind_dir']    = $weather->wind->direction->getUnit();
        }

        if ($weather->clouds) {
            $dataset['clouds_value'] = $weather->clouds->getValue();
            $dataset['clouds_text'] = $weather->clouds->getDescription();
        }

        if ($weather->precipitation)
            $dataset['precipitation'] = $weather->precipitation->getValue();

        if ($weather->weather) {
            $dataset['weather_icon'] = $weather->weather->icon;
            $dataset['weather_icon_url'] = $weather->weather->getIconUrl();
        }

        $dataset['s'] = array_key_exists($weather->weather->icon, self::icons_conversion)
            ? self::icons_conversion[ $weather->weather->icon ]
            : '44d';

        return $dataset;
    }

    /**
     * Загружает из OpenWeather погоду для переданного массива регионов
     *
     * @param array $regions_list - либо список регионов (многомерный массив), либо список ключей регионов (array_keys)
     * @return array
     * @throws OpenWeatherMap\Exception
     */
    public static function fetchWeatherGroup(array $regions_list):array
    {
        $final_weather = [];
        // make IDS list: keys of region list
        $regions_ids_list = (self::arrayDepth($regions_list) > 1) ? array_keys($regions_list) : $regions_list;

        self::$logger->notice('[FETCH] weather for regions ids: ', $regions_list);

        // get weather for set of items
        $all_regions_weather = self::$owm->getWeatherGroup($regions_ids_list, self::$options['units'], self::$options['lang']);

        // create iterator from returned object
        $all_regions_weather = new IteratorIterator($all_regions_weather);

        // iterate all elements: make weather for each region
        /**
         * @var CurrentWeather $region_weather
         */
        foreach ($all_regions_weather as $region_weather) {
            $region_id = $region_weather->city->id;
            $region_info = self::outer_regions[ $region_id ];
            self::$logger->notice("[MAKE] weather data for region {$region_info['geoname_ru']}... ");

            $final_weather[ $region_id ] = self::makeWeatherInfo($region_id, $region_weather);
        }

        return $final_weather;
    }

    /**
     * DEBUG Method
     * @param array $regions_list
     * @return array
     * @throws OpenWeatherMap\Exception
     */
    public static function fetchWeatherGroupDebug(array $regions_list): array
    {
        $final_weather = [];
        if (self::arrayDepth($regions_list) == 1) {
            echo "Can't iterate flat regions list." . PHP_EOL;
            return [];
        }

        foreach ($regions_list as $id => $region_info) {

            echo "Retrieving data for region {$region_info['geoname_ru']} ... ";
            self::$logger->notice("[GET] data for region {$region_info['geoname_ru']} ... ");

            $region_weather = self::$owm->getWeather( $region_info['owm_id'], self::$options['units'], self::$options['lang']);
            $id = $region_weather->city->id;

            $final_weather[ $id ] = self::makeWeatherInfo($id, $region_weather);
            echo "Ok.", PHP_EOL;
        }
        return $final_weather;
    }



    /* =============================================================================================================== */

    /**
     * array_search_callback() аналогичен array_search() , только помогает искать по неодномерному массиву.
     *
     * @param array $a
     * @param callable $callback
     * @return mixed|null
     */
    private static function array_search_callback(array $a, callable $callback)
    {
        foreach ($a as $item) {
            $v = \call_user_func($callback, $item);
            if ( $v === true ) return $item;
        }
        return null;
    }

    private static function arrayDepth($array, $level = 0) {

        if (!$array) return 0;

        $current = current($array);
        $level++;

        if ( !is_array($current) ) return $level;

        return self::arrayDepth($current, $level);
    }

}

# -eof-
