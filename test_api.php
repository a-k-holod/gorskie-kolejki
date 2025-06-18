<?php

function request(string $method, string $url, ?array $data = null)
{
    $curl = curl_init();

    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ];

    if ($data !== null) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($data);
    }

    curl_setopt_array($curl, $opts);
    $response = curl_exec($curl);
    $info = curl_getinfo($curl);
    $err = curl_error($curl);
    curl_close($curl);

    echo "### $method $url\n";
    if ($data !== null) {
        echo "Payload: " . json_encode($data) . "\n";
    }

    echo "Status: {$info['http_code']}\n";
    echo "Response: $response\n\n";

    if ($err) {
        echo "Curl error: $err\n";
    }

    // 🔍 DEBUG: pokaż błąd dekodowania JSON, jeśli wystąpi
    $json = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ JSON decode error: " . json_last_error_msg() . "\n";
        return null;
    }

    return $json;
}


// Test: Create coaster
$coasterData = [
    'liczba_personelu' => 5,
    'liczba_klientow'  => 10,
    'dl_trasy'         => 500,
    'godziny_od'       => '10:00',
    'godziny_do'       => '18:00',
];
$response = request('POST', 'http://localhost:8080/api/coasters', $coasterData);
$coasterId = $response['id'] ?? null;

if ($coasterId) {
    // Test: Add wagon
    $wagon1 = [
        'ilosc_miejsc'     => 4,
        'predkosc_wagonu'  => 60,
    ];
    request('POST', "http://localhost:8080/api/coasters/$coasterId/wagons", $wagon1);

    $wagon2 = [
        'ilosc_miejsc'     => 6,
        'predkosc_wagonu'  => 70,
    ];
    request('POST', "http://localhost:8080/api/coasters/$coasterId/wagons", $wagon2);

    // Test: Fetch all coasters
    request('GET', 'http://localhost:8080/api/coasters');

    // Test: Delete one wagon — you can store its ID above if needed
    // $wagonId = ... ;
    // request('DELETE', "http://localhost:8080/api/coasters/$coasterId/wagons/$wagonId");
} else {
    echo "Nie udało się stworzyć kolejki.\n";
}
