<?php

return [
    'url' => getenv('SUPABASE_URL'),
    'key' => getenv('SUPABASE_KEY'),
    'options' => [
        'headers' => [
            'apikey' => getenv('SUPABASE_KEY'),
            'Authorization' => 'Bearer ' . getenv('SUPABASE_KEY')
        ]
    ]
]; 