<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\CoasterAnalyzer;
use CodeIgniter\API\ResponseTrait;
use Predis\Client;

class Coasters extends BaseController
{
    use ResponseTrait;

    protected $redis;
    protected $envPrefix;

    public function __construct()
    {
        $env = getenv('APP_ENV') ?: 'production';
        error_log("Current APP_ENV: $env");
        $this->envPrefix = $env === 'development' ? 'dev:' : 'prod:';

        $this->redis = new Client([
            'scheme' => 'tcp',
            'host'   => 'redis',
            'port'   => 6379,
        ]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        if (!isset($data['liczba_personelu'], $data['liczba_klientow'], $data['dl_trasy'], $data['godziny_od'], $data['godziny_do'])) {
            return $this->failValidationErrors('Brak wymaganych danych');
        }

        $coasterId = uniqid('coaster_', true);
        $data['id'] = $coasterId;

        $this->redis->set("{$this->envPrefix}coasters:$coasterId", json_encode($data));

        return $this->respondCreated(['id' => $coasterId, 'message' => 'Kolejka dodana']);
    }

    public function index()
    {
        $keys = array_filter(
            $this->redis->keys("{$this->envPrefix}coasters:*"),
            fn($key) => !str_contains($key, ':wagons')
        );

        $coasters = [];

        foreach ($keys as $key) {
            $rawData = $this->redis->get($key);
            $data = json_decode($rawData, true);

            if (!is_array($data)) {
                continue;
            }

            preg_match("/{$this->envPrefix}coasters:(.+)/", $key, $matches);
            $coasterId = $matches[1] ?? null;

            if ($coasterId) {
                $wagonsRaw = $this->redis->hgetall("{$this->envPrefix}coasters:$coasterId:wagons");
                $wagons = [];

                foreach ($wagonsRaw as $jsonWagon) {
                    $decoded = json_decode($jsonWagon, true);
                    if (is_array($decoded)) {
                        $wagons[] = $decoded;
                    }
                }

                $data['wagons'] = $wagons;
            }

            $data['analysis'] = CoasterAnalyzer::analyze($data);
            $coasters[] = $data;
        }

        return $this->respond($coasters);
    }

    public function update(string $coasterId)
    {
        $json = $this->request->getJSON(true);
        if (!$json) {
            return $this->failValidationErrors('Brak danych lub niepoprawny JSON');
        }

        $coasterKey = "{$this->envPrefix}coasters:$coasterId";
        $coasterJson = $this->redis->get($coasterKey);

        if (!$coasterJson) {
            return $this->failNotFound('Kolejka nie istnieje');
        }

        $coasterData = json_decode($coasterJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->failServerError('Błąd dekodowania danych kolejki');
        }

        foreach (['liczba_personelu', 'liczba_klientow', 'godziny_od', 'godziny_do'] as $field) {
            if (isset($json[$field])) {
                $coasterData[$field] = $json[$field];
            }
        }

        $this->redis->set($coasterKey, json_encode($coasterData));

        return $this->respond([
            'message' => 'Kolejka zaktualizowana',
            'coaster' => $coasterData
        ]);
    }

    public function addWagon(string $coasterId)
    {
        $wagonData = $this->request->getJSON(true);

        if (!isset($wagonData['ilosc_miejsc'], $wagonData['predkosc_wagonu'])) {
            return $this->failValidationErrors('Brak wymaganych danych wagonu');
        }

        $coasterKey = "{$this->envPrefix}coasters:$coasterId";
        if (!$this->redis->get($coasterKey)) {
            return $this->failNotFound('Kolejka nie istnieje');
        }

        $wagonId = uniqid('wagon_', true);
        $wagonData['id'] = $wagonId;

        $this->redis->hset("{$coasterKey}:wagons", $wagonId, json_encode($wagonData));

        return $this->respondCreated(['wagonId' => $wagonId, 'message' => 'Wagon dodany']);
    }

    public function deleteWagon(string $coasterId, string $wagonId)
    {
        $coasterKey = "{$this->envPrefix}coasters:$coasterId";

        if (!$this->redis->get($coasterKey)) {
            return $this->failNotFound('Kolejka nie istnieje');
        }

        if (!$this->redis->hexists("{$coasterKey}:wagons", $wagonId)) {
            return $this->failNotFound('Wagon nie istnieje');
        }

        $this->redis->hdel("{$coasterKey}:wagons", [$wagonId]);

        return $this->respondDeleted(['message' => 'Wagon usunięty']);
    }

    public function status(string $coasterId)
    {
        $coasterKey = "{$this->envPrefix}coasters:$coasterId";
        $coasterJson = $this->redis->get($coasterKey);

        if (!$coasterJson) {
            return $this->failNotFound('Kolejka nie istnieje');
        }

        $coasterData = json_decode($coasterJson, true);
        $wagonsRaw = $this->redis->hgetall("{$coasterKey}:wagons");
        $wagons = [];

        foreach ($wagonsRaw as $jsonWagon) {
            $decoded = json_decode($jsonWagon, true);
            if (is_array($decoded)) {
                $wagons[] = $decoded;
            }
        }

        $coasterData['wagons'] = $wagons;
        $analysis = CoasterAnalyzer::analyze($coasterData);

        return $this->respond(['coaster' => $coasterData, 'analysis' => $analysis]);
    }
}
