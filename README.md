# AJUR Media Steamboat Weather toolkit

## Методы

#### Weather::init()

```
/**
 * @param null $api_key -- API-ключ для доступа к OpenWeather
 * @param array $options -- 2 опции: units:metric|imperial, lang:ru|en|..
 * @param null $logger -- null или Logger
 * @throws Exception
 */
public static function init($api_key = null, $options = [], $logger = null)
```

Для загрузки погоды с серверов OpenWeatherMap требуется указать API-key. Для загрузки погоды из файла достаточно (но не необходимо) указать Logger.

#### Weather::loadLocalWeather()

```
/**
 * @param int $district_id
 * @param null $source_file
 * @return array
 */
public static function loadLocalWeather($district_id = 0, $source_file = null)
``` 

Загружает данные о погоде для определенного региона (0 для всех) из JSON-файла. 
Структура файла: 
```
{
    "update_ts": 1592480232,
    "update_time": "2020-06-18 14-37-12",
    "data": {}
}
```
`data` - объект, в котором ключ - идентификатор региона в кодировке OpenWeatherMap, данные по ключу - информация о погоде в регионе.


#### Weather::fetchWeatherGroup() 
```
/**
 * Загружает из OpenWeather погоду для переданного массива регионов
 *
 * @param array $regions_list - либо список регионов (многомерный массив), либо список ключей регионов (array_keys)
 * @return array
 * @throws OpenWeatherMap\Exception
 */
public static function fetchWeatherGroup(array $regions_list):array
```
Аргументом `$regions_list` может служить как список регионов (многомерный массив типа `WeatherConstants::outer_regions`), так и список идентификаторов
регионов в формате OpenWeatherMap (в общем случае `array_keys(WeatherConstants::outer_regions)`)

#### Weather::fetchWeatherGroupDebug()
```
/**
 * @param array $regions_list
 * @return array
 * @throws OpenWeatherMap\Exception
 */
public static function fetchWeatherGroupDebug(array $regions_list): array
```
Отладочный метод. Аргументом может служить только многомерный массив-список регионов. 

## Константы (WeatherConstants)

Идентификаторы регионов представлены в двух пространствах значений. 
- Внутренние коды для сайта 47news (<100)
- Коды для OpenWeatherMap

Константы: 
- `lo_adjacency_lists` - Список смежности регионов ЛенОбласти. Значения - внутренние идентификаторы регионов на сайте 47news
- `map_intid_to_owmid` - таблица маппинга регионов ЛенОбласти на таблицу регионов OWMID
- `REGION_SPB` - код региона "Санкт-Петербург" в пространстве кодов OWMID
- `REGION_LO` - код региона "Ленинградкая Область" в пространстве кодов OWMID
- `outer_regions` - список всех регионов СПб и Ленобласти в пространстве кодов OWMID
- `icons_conversion` - таблица маппинга - код погоды в имя файла иконки 

# Как использовать?

Получаем погоду с сервера OWM:
```
require_once 'vendor/autoload.php';

use AJUR\Toolkit\Weather;

Weather::init('<api key>');

$data = Weather::fetchWeatherGroup( array_keys( \AJUR\Toolkit\WeatherConstants::outer_regions ) );

$data = [
    'update_ts'     =>  time(),
    'update_time'   =>  (new \DateTime())->format('Y-m-d H-i-s'),
    'data'          =>  $data
];

file_put_contents('weather.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE  ), LOCK_EX);
```

Загружаем погоду на сайте:
```
use AJUR\Toolkit\Weather;

Weather::init(null, [], AppLogger::scope('log.weather'));
$weather_source = getenv('STORAGE.WEATHER');
$weather = Weather::loadLocalWeather($current_district_id, $weather_source);

```
