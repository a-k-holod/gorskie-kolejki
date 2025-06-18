<?php
namespace Config;

use CodeIgniter\Config\BaseConfig;

class Redis extends BaseConfig
{
    public string $host;
    public int $port;
    public int $database;
    public string $prefix;

    public function __construct()
    {
        $this->host = env('REDIS_HOST') ?: '127.0.0.1';
        $this->port = (int)(env('REDIS_PORT') ?: 6379);
        $this->database = (int)(env('REDIS_DATABASE') ?: 0);
        $this->prefix = env('REDIS_PREFIX') ?: '';
    }
}
