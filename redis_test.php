<?php
require 'vendor/autoload.php';

use Predis\Client;

try {
    $redis = new Client([
        'scheme' => 'tcp',
        'host'   => '127.0.0.1',
        'port'   => 6379,
    ]);

    echo "Connected to Redis: " . $redis->ping();
} catch (Exception $e) {
    echo "Redis error: " . $e->getMessage();
}
