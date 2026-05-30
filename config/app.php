<?php

return [
    'name'       => 'Visi',
    'url'        => getenv('APP_URL') ?: 'http://localhost:8080',
    'debug'      => true,
    'timezone'   => 'Asia/Jakarta',
    'seat_hold_ttl' => 300, // 5 menit
    'currency'   => 'IDR',
    'jwt_secret' => getenv('JWT_SECRET') ?: 'visi-dev-secret-key-change-in-production',
];
