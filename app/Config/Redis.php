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
        $this->host = getenv('redis.host') ?: '127.0.0.1';
        $this->port = (int)(getenv('redis.port') ?: 6379);
        $this->database = (int)(getenv('redis.database') ?: 0);
        $this->prefix = getenv('REDIS_PREFIX') ?: '';
    }
}
