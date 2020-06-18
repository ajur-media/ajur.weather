# AJUR Media Steamboat Weather toolkit

Get weather from OWM
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

Load weather data for site:
```


```
