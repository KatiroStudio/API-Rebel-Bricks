<?php

return [
    'api_key' => getenv('BRICKSET_API_KEY'),
    'username' => getenv('BRICKSET_USERNAME'),
    'password' => getenv('BRICKSET_PASSWORD'),
    'base_url' => 'https://brickset.com/api/v3.asmx',
    'endpoints' => [
        'themes' => '/getThemes',
        'sets' => '/getSets',
        'years' => '/getYears',
        'subthemes' => '/getSubthemes'
    ]
]; 