<?php

return [
    'api_key' => getenv('REBRICKABLE_API_KEY'),
    'base_url' => 'https://rebrickable.com/api/v3',
    'endpoints' => [
        'themes' => '/lego/themes/',
        'sets' => '/lego/sets/',
        'colors' => '/lego/colors/',
        'parts' => '/lego/parts/',
        'minifigs' => '/lego/minifigs/'
    ]
]; 