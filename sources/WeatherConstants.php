<?php

namespace AJUR\Toolkit;

interface WeatherConstants
{
    /**
     * Error consts
     */
    public const ERROR_SOURCE_FILE_NOT_DEFINED = 1;
    public const ERROR_SOURCE_FILE_NOT_READABLE = 2;
    public const ERROR_SOURCE_FILE_PARSING_ERROR = 3;
    public const ERROR_SOURCE_FILE_HAVE_NO_DATA = 4;
    public const ERROR_NO_SUCH_DISTRICT_ID = 5;
    public const ERROR_SOURCE_FILE_READING_ERROR = 6;

    /**
     * Константы регионов
     */
    public const REGION_SPB    = 812;
    public const REGION_LO     = 813;

    /**
     * список смежности регионов леобласти
     *
     * Ключ - внутренний идентификатор региона на сайте 47news.ru (0 - регион не выбран, СПб)
     * Значение - массив смежных регионов
     */
    public const lo_adjacency_lists = [
        1   =>  [ 13, 4, 15, 6 ],
        2   =>  [ 14, 16, 7, 8  ],
        3   =>  [ 11, 16, 7, 12 ],
        4   =>  [ 5, 13, 14, 15 ],
        5   =>  [ 14, 15, 17, 4],
        6   =>  [ 9, 13, 15, 1 ],
        7   =>  [ 12, 16, 3, 2 ],
        8   =>  [ 2, 11, 14, 16],
        9   =>  [ 1, 6, 13, 15],
        10   =>  [ 17, 18, 5, 7],
        11   =>  [ 3, 8, 16, 7 ],
        12   =>  [ 7, 3, 16, 18 ],
        13   =>  [ 1, 4, 6, 15 ],
        14   =>  [ 2, 5, 4, 8 ],
        15   =>  [ 4, 5, 13, 6 ],
        16   =>  [ 2, 3, 7, 8 ],
        17   =>  [ 5, 10, 18, 14 ],
        18   =>  [ 12, 7, 10, 17 ],
    ];

    /**
     * таблица маппинга регионов ленобласти на таблицу регионов OWMID
     *
     * Ключ: внутренний код на сайте
     * Значение: OWM ID
     */
    public const map_intid_to_owmid = [
        // субрайоны Санкт-Петербурга
        self::REGION_SPB =>  [
            0   => 536203,              // Условно Санкт-Петербург (на самом деле - центр)
        ],

        // регионы ленобласти (внутренний ID на сайте 47news.ru => код региона OWM)
        self::REGION_LO =>  [
            0   =>  536203,             // Санкт-Петербург (центр)
            1   =>  575410,             // 'Бокситогорский район'
            2   =>  561887,             // 'Гатчинский район'
            3   =>  548602,             // 'Кингисеппский район'
            4   =>  548442,             // 'Киришский район'
            5   =>  548392,             // 'Кировский район'
            6   =>  534560,             // 'Лодейнопольский район'
            7   =>  534341,             // 'Ломоносовский район'
            8   =>  533690,             // 'Лужский район'
            9   =>  508034,             // 'Подпорожский район'
            10  =>  505230,             // 'Приозерский район'
            11  =>  492162,             // 'Сланцевский район'
            12  =>  490172,             // 'Сосновоборский округ'
            13  =>  483019,             // 'Тихвинский район'
            14  =>  481964,             // 'Тосненский район'
            15  =>  472722,             // 'Волховский район'
            16  =>  472357,             // 'Волосовский район'
            17  =>  471101,             // 'Всеволожский район'
            18  =>  470546              // 'Выборгский район'
        ]
    ];

    /**
     * owm_id       -- код региона в таблицах OpenWeatherMap
     * geoname_en   -- гео название на англ. языке
     * geoname_ru   -- гео название на русском
     * lon          -- координаты центра региона
     * lat
     * group_code   -- какой группе принадлежит регион (используется телефонный код)
     */
    public const outer_regions = [
        536203 => [
            'owm_id' => 536203,
            'geoname_en' => 'Sankt-Peterburg',
            'geoname_ru' => 'Санкт-Петербург',
            'lon' => 30.25,
            'lat' => 59.916668,
            'group_code' => self::REGION_SPB,
        ],
        // Ленобласть
        575410 => [
            'owm_id' => 575410,
            'geoname_en' => 'Boksitogorsk',
            'geoname_ru' => 'Бокситогорский район',
            'lon' => 33.84853,
            'lat' => 59.474049,
            'group_code' => self::REGION_LO,
        ],
        561887 => [
            'owm_id' => 561887,
            'geoname_en' => 'Gatchina',
            'geoname_ru' => 'Гатчинский район',
            'lon' => 30.12833,
            'lat' => 59.576389,
            'group_code' => self::REGION_LO,
        ],
        548602 => [
            'owm_id' => 548602,
            'geoname_en' => 'Kingisepp',
            'geoname_ru' => 'Кингисеппский район',
            'lon' => 28.61343,
            'lat' => 59.37331,
            'group_code' => self::REGION_LO,
        ],
        548442 => [
            'owm_id' => 548442,
            'geoname_en' => 'Kirishi',
            'geoname_ru' => 'Киришский район',
            'lon' => 32.020489,
            'lat' => 59.447121,
            'group_code' => self::REGION_LO,
        ],
        548392 => [
            'owm_id' => 548392,
            'geoname_en' => 'Kirovsk',
            'geoname_ru' => 'Кировский район',
            'lon' => 30.99507,
            'lat' => 59.881008,
            'group_code' => self::REGION_LO,
        ],
        534560 => [
            'owm_id' => 534560,
            'geoname_en' => 'Lodeynoye Pole',
            'geoname_ru' => 'Лодейнопольский район',
            'lon' => 33.553059,
            'lat' => 60.726002,
            'group_code' => self::REGION_LO,
        ],
        534341 => [
            'owm_id' => 534341,
            'geoname_en' => 'Lomonosov',
            'geoname_ru' => 'Ломоносовский район',
            'lon' => 29.77253,
            'lat' => 59.90612,
            'group_code' => self::REGION_LO,
        ],
        533690 => [
            'owm_id' => 533690,
            'geoname_en' => 'Luga',
            'geoname_ru' => 'Лужский район',
            'lon' => 29.84528,
            'lat' => 58.737221,
            'group_code' => self::REGION_LO,
        ],
        508034 => [
            'owm_id' => 508034,
            'geoname_en' => 'Podporozhye',
            'geoname_ru' => 'Подпорожский район',
            'lon' => 34.170639,
            'lat' => 60.91124,
            'group_code' => self::REGION_LO,
        ],
        505230 => [
            'owm_id' => 505230,
            'geoname_en' => 'Priozersk',
            'geoname_ru' => 'Приозерский район',
            'lon' => 30.12907,
            'lat' => 61.03928,
            'group_code' => self::REGION_LO,
        ],
        492162 => [
            'owm_id' => 492162,
            'geoname_en' => 'Slantsy',
            'geoname_ru' => 'Сланцевский район',
            'lon' => 28.09137,
            'lat' => 59.118172,
            'group_code' => self::REGION_LO,
        ],
        490172 => [
            'owm_id' => 490172,
            'geoname_en' => 'Sosnovyy Bor',
            'geoname_ru' => 'Сосновоборский округ',
            'lon' => 29.116671,
            'lat' => 59.900002,
            'group_code' => self::REGION_LO,
        ],
        483019 => [
            'owm_id' => 483019,
            'geoname_en' => 'Tikhvin',
            'geoname_ru' => 'Тихвинский район',
            'lon' => 33.599369,
            'lat' => 59.645111,
            'group_code' => self::REGION_LO,
        ],
        481964 => [
            'owm_id' => 481964,
            'geoname_en' => 'Tosno',
            'geoname_ru' => 'Тосненский район',
            'lon' => 30.877501,
            'lat' => 59.540001,
            'group_code' => self::REGION_LO,
        ],
        472722 => [
            'owm_id' => 472722,
            'geoname_en' => 'Volhov',
            'geoname_ru' => 'Волховский район',
            'lon' => 32.338188,
            'lat' => 59.9258,
            'group_code' => self::REGION_LO,
        ],
        471101 => [
            'owm_id' => 471101,
            'geoname_en' => 'Vsevolozhsk',
            'geoname_ru' => 'Всеволожский район',
            'lon' => 30.637159,
            'lat' => 60.020432,
            'group_code' => self::REGION_LO,
        ],
        472357 => [
            'owm_id' => 472357,
            'geoname_en' => 'Volosovo',
            'geoname_ru' => 'Волосовский район',
            'lon' => 59.45,
            'lat' => 29.48,
            'group_code' => self::REGION_LO,
        ],
        470546 => [
            'owm_id' => 470546,
            'geoname' => 'Vyborg',
            'geoname_ru' => 'Выборгский район',
            'lon' => 28.752831,
            'lat' => 60.70763,
            'group_code' => self::REGION_LO,
        ],
    ];

    public const icons_conversion = [
        // clear sky - чистое небо
        '01d'   =>  '31d',
        '01n'   =>  '31n',

        // few clouds - малая облачность
        '02d'   =>  '30d',
        '02n'   =>  '30n',

        // scattered clouds - рассеянная облачность
        '03d'   =>  '26d',
        '03n'   =>  '26n',

        // broken clouds - облачно с прояснениями
        '04d'   =>  '27d',
        '04n'   =>  '27n',

        // shower rain  =- проливной дождь
        '09d'   =>  '10d',
        '09n'   =>  '10n',

        // rain - дождь
        '10d'   =>  '9d',
        '10n'   =>  '9n',

        // thunderstorm - гроза
        '11d'   =>  '0d',
        '11n'   =>  '0n',

        // snow - снег
        '13d'   =>  '6d',
        '13n'   =>  '6n',

        // mist - туман
        '50d'   =>  '22d',
        '50n'   =>  '22n',
    ];

}

# -eof- #
