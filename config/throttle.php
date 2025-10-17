<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Throttle Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the throttling settings for different routes.
    | The format is 'requests,minutes' where requests is the number of
    | requests allowed and minutes is the time window.
    |
    */

    'api' => '60,1', // 60 requests per minute (default)
    'news' => '300,1', // 300 requests per minute for news (more lenient)
    'auth' => '10,1', // 10 requests per minute for auth (more strict)
];
