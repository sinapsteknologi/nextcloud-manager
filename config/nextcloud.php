<?php

return [
    'disk' => 'nextcloud',
    'api_base' => env('NEXTCLOUD_API_BASE',),
    'username' => env('NEXTCLOUD_USERNAME'),
    'password' => env('NEXTCLOUD_PASSWORD'),
    'url' => env('NEXTCLOUD_URL',),
    'path' => env('NEXTCLOUD_PATH'),
];
