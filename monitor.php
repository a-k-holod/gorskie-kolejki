<?php
require __DIR__ . '/vendor/autoload.php';
error_reporting(E_ALL & ~E_DEPRECATED);

use React\EventLoop\Factory;
use Clue\React\Redis\Factory as RedisFactory;
use App\Services\CoasterAnalyzer;

$loop = Factory::create();
$redisFactory = new RedisFactory($loop);

$env = getenv('APP_ENV') ?: 'production';
$envPrefix = $env === 'development' ? 'dev:' : 'prod:';
echo "[Monitor] Środowisko: $env, prefiks: $envPrefix\n";

$redisFactory->createClient('redis://redis:6379')->then(function ($client) use ($loop, $envPrefix) {
    echo "[Monitor] Połączono z Redis\n";

    $loop->addPeriodicTimer(5.0, function () use ($client, $envPrefix) {
        $client->keys($envPrefix . 'coasters:*')->then(function ($keys) use ($client, $envPrefix) {
            echo "[" . date('H:i:s') . "] Znalezione klucze: " . implode(', ', $keys) . "\n";

            foreach ($keys as $key) {
                if (str_contains($key, ':wagons')) continue;

                $client->get($key)->then(function ($raw) use ($client, $envPrefix, $key) {
                    if ($raw === null) {
                        file_put_contents('monitor_error.log', "[" . date('Y-m-d H:i:s') . "] Pusty rekord: $key\n", FILE_APPEND);
                        return;
                    }

                    $clean = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $raw));
                    $data = json_decode($clean, true);
                    if (!is_array($data)) {
                        file_put_contents('monitor_error.log', "[".date('Y-m-d H:i:s')."] Błąd JSON ($key): ".json_last_error_msg().", dane: $clean\n", FILE_APPEND);
                        return;
                    }

                    $coasterId = substr($key, strlen($envPrefix . 'coasters:'));
                    $client->hgetall($envPrefix . "coasters:$coasterId:wagons")->then(function ($wagRaw) use ($data) {
                        $wagons = [];
                        if (is_array($wagRaw)) {
                            foreach ($wagRaw as $jsonW) {
                                $d = json_decode($jsonW, true);
                                if (is_array($d)) $wagons[] = $d;
                            }
                        }
                        $data['wagons'] = $wagons;
                        $an = CoasterAnalyzer::analyze($data);

                        echo "[".date('H:i:s')."] Kolejka {$data['id']}\n";
                        echo "  Godziny: {$data['godziny_od']}–{$data['godziny_do']}\n";
                        echo "  Wagonów: ".count($wagons)."/".($an['brak_wagonow'] ?? count($wagons))."\n";
                        echo "  Personel: {$data['liczba_personelu']}/".($an['personel_roznica'] ?? 0)." ({$an['personel_status']})\n";
                        echo "  Klienci: {$data['liczba_klientow']}, status: {$an['klienci_status']}\n";

                        if (in_array($an['personel_status'], ['brak','nadmiar']) || in_array($an['klienci_status'], ['brak','nadmiar'])) {
                            $msg = "[".date('Y-m-d H:i:s')."] PROBLEM: {$data['id']} – personel={$an['personel_status']},".
                                " klienci={$an['klienci_status']}\n";
                            file_put_contents('monitor.log', $msg, FILE_APPEND);
                        }
                    });
                });
            }
        });
    });
});

$loop->run();
