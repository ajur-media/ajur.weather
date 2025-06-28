<?php

namespace AJUR\Toolkit;

use AJUR\OpenWeatherMap;
use AJUR\OpenWeatherMap\CurrentWeather;
use Exception;
use IteratorIterator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;
use RuntimeException;
use function array_key_exists;
use function file_get_contents;
use function json_decode;
use function shuffle;

class Weather implements WeatherInterface, WeatherConstants
{
    /**
     * @var string
     */
    private static string $API_KEY;

    /**
     * @var OpenWeatherMap
     */
    public static OpenWeatherMap $owm;

    /**
     * @var LoggerInterface|null
     */
    private static LoggerInterface $logger;

    /**
     * @var string[]
     */
    private static array $options = [
        'units'     =>  'metric',
        'lang'      =>  'ru'
    ];

    /**
     * Инициализация требуется для загрузки погоды
     *
     * @param string $api_key -- API-ключ для доступа к OpenWeather
     * @param string $units
     * @param string $lang
     * @param LoggerInterface|null $logger -- null или Logger
     * @throws Exception
     */
    public static function init(string $api_key = '', string $units = 'metric', string $lang = 'ru', ?LoggerInterface $logger = null):void
    {
        if (empty($api_key)) {
            throw new RuntimeException("OpenWeatherMap Exception: empty API key not allowed");
        }

        self::$logger
            = $logger instanceof LoggerInterface
            ? $logger
            : new NullLogger();

        self::$API_KEY = $api_key;
        self::$owm = new OpenWeatherMap(self::$API_KEY);
        self::$owm->setLogger(self::$logger);

        self::$options['units'] = $units;
        self::$options['lang'] = $lang;
    }

    /**
     * Загружает данные о погоде из JSON-файла.
     * Структура файла: update_ts:<int>, update_time:<string>, data:<массив по регионам>
     *
     * @param int $district_id
     * @param null $source_file
     * @return array
     */
    public static function loadLocalWeather(int $district_id = 0, $source_file = null): array
    {
        $current_weather = [];

        try {
            if (is_null($source_file)) {
                self::$logger->error("Weather file not defined");
                throw new RuntimeException("Weather file not defined", self::ERROR_SOURCE_FILE_NOT_DEFINED);
            }

            if (!is_readable($source_file)) {
                self::$logger->error("Weather file `{$source_file}` not found");
                throw new RuntimeException("Weather file `{$source_file}` not found", self::ERROR_SOURCE_FILE_NOT_READABLE);
            }

            $file_content = file_get_contents($source_file);
            if ($file_content === false) {
                self::$logger->error("Error reading weather file `{$source_file}`");
                throw new RuntimeException("Error reading weather file `{$source_file}`", self::ERROR_SOURCE_FILE_READING_ERROR);
            }

            $file_content_json = json_decode($file_content, true);

            if (!is_array($file_content_json)) {
                self::$logger->error("Weather data can't be parsed", [ $file_content_json ]);
                throw new RuntimeException("Weather data can't be parsed", self::ERROR_SOURCE_FILE_PARSING_ERROR);
            }

            if (!array_key_exists('data', $file_content_json)) {
                self::$logger->error("Weather file does not contain DATA section");
                throw new RuntimeException("Weather file does not contain DATA section", self::ERROR_SOURCE_FILE_HAVE_NO_DATA);
            }

            $current_weather = $file_content_json['data'];

            // Район - 0 (все) ?
            if ($district_id === 0) {
                shuffle($current_weather);
                return $current_weather; // возвращаем перемешанный массив с погодой
            }

            // Район не равен нулю, нужно построить массив с погодой для указанного района и ближайших:

            // Проверим, есть ли такой идентификатор района вообще в массиве кодов районов.
            // Если нет - кидаем исключение (записываем ошибку), но возвращаем массив со случайной погодой
            if (!array_key_exists($district_id, self::map_intid_to_owmid[ self::REGION_LO ])) {
                self::$logger->error("Given district id ({$district_id}) does not exist in MAP_INTID_TO_OWMID set", [ self::map_intid_to_owmid ]);
                throw new RuntimeException("Given district id ({$district_id}) does not exist in MAP_INTID_TO_OWMID set", self::ERROR_NO_SUCH_DISTRICT_ID);
            }

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

        } catch (RuntimeException $e) {
            self::$logger->error('[ERROR] Load Weather ',
                [
                    array_search($e->getCode(), (new ReflectionClass(self::class))->getConstants(), true),
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

        if ($weather->humidity) {
            $dataset['humidity'] = $weather->humidity->getFormatted();
        }

        if ($weather->pressure) {
            $dataset['pressure_hpa'] = $weather->pressure->getValue();
            $dataset['pressure_mm'] = round($dataset['pressure_hpa'] * 0.75006375541921, 0);
        }

        if ($weather->wind->speed) {
            $dataset['wind_speed'] = $weather->wind->speed->getValue();
        }

        if ($weather->wind->direction) {
            $dataset['wind_speed'] = $weather->wind->speed->getValue();
            $dataset['wind_dir_raw'] = $weather->wind->direction->getValue();
            $dataset['wind_dir']    = $weather->wind->direction->getUnit();
        }

        if ($weather->clouds) {
            $dataset['clouds_value'] = $weather->clouds->getValue();
            $dataset['clouds_text'] = $weather->clouds->getDescription();
        }

        if ($weather->precipitation) {
            $dataset['precipitation'] = $weather->precipitation->getValue();
        }

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
     *
     * @param array $regions_list
     * @param bool $print_debug
     * @return array
     * @throws OpenWeatherMap\Exception
     */
    public static function fetchWeatherGroupDebug(array $regions_list, bool $print_debug = true): array
    {
        $final_weather = [];
        if (self::arrayDepth($regions_list) == 1) {
            self::$logger->error("Can't iterate flat regions list.");
            if ($print_debug) {
                echo "Can't iterate flat regions list.";
            }
            return [];
        }

        foreach ($regions_list as $id => $region_info) {

            if ($print_debug) {
                echo "Retrieving data for region {$region_info['geoname_ru']} ... ";
            }
            self::$logger->debug("Retrieving data for region {$region_info['geoname_ru']} ... ");
            self::$logger->notice("[GET] data for region {$region_info['geoname_ru']} ... ");

            $region_weather = self::$owm->getWeather( $region_info['owm_id'], self::$options['units'], self::$options['lang']);
            $id = $region_weather->city->id;

            $final_weather[ $id ] = self::makeWeatherInfo($id, $region_weather);
            if ($print_debug) {
                echo "Ok.", PHP_EOL;
            }
        }
        return $final_weather;
    }

    /**
     * array_search_callback() аналогичен array_search() , только помогает искать по неодномерному массиву.
     * Копия из Arris.Helpers
     *
     * @param array $a
     * @param callable $callback
     * @return mixed|null
     */
    private static function array_search_callback(array $a, callable $callback): mixed
    {
        foreach ($a as $item) {
            $v = \call_user_func($callback, $item);
            if ( $v === true ) {
                return $item;
            }
        }
        return null;
    }

    /**
     * копия из Arris.Helpers
     *
     * @param $array
     * @param int $level
     * @return int
     */
    private static function arrayDepth($array, int $level = 0): int
    {
        if (!$array) {
            return 0;
        }

        $current = current($array);
        $level++;

        return is_array($current) ? self::arrayDepth($current, $level) : $level;
    }

}

# -eof- #
