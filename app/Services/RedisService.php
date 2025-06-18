<?php namespace App\Services;

use Predis\Client;

class RedisService
{
    private $redis;

    public function __construct()
    {
        $this->prefix = getenv('REDIS_PREFIX') ?: '';

        $this->redis = new Client([
            'scheme' => 'tcp',
            'host'   => env('redis.host', 'redis'),
            'port'   => env('redis.port', 6379),
            'database' => env('redis.database', 0),
        ]);
    }

    private function prefixedKey(string $key): string
    {
        return $this->prefix . $key;
    }

    public function set(string $key, $value): void
    {
        $this->redis->set($this->prefixedKey($key), json_encode($value));
    }

    public function get(string $key)
    {
        $value = $this->redis->get($this->prefixedKey($key));
        return $value ? json_decode($value, true) : null;
    }

    public function delete(string $key): void
    {
        $this->redis->del([$this->prefixedKey($key)]);
    }

    public function keys(string $pattern): array
    {
        return $this->redis->keys($this->prefixedKey($pattern));
    }
}
