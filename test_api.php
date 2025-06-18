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

    // DEBUG: pokaż błąd dekodowania JSON, jeśli wystąpi
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


// Test: Update coaster
$updateData = [
    'liczba_personelu' => 8,
    'liczba_klientow'  => 20,
    'godziny_od'       => '09:00',
    'godziny_do'       => '19:00',
];

if ($coasterId) {
    $responseUpdate = request('PUT', "http://localhost:8080/api/coasters/$coasterId", $updateData);

    // Sprawdź odpowiedź update
    if (isset($responseUpdate['message'])) {
        echo "Update coaster: " . $responseUpdate['message'] . "\n";
    }

    // Pobierz wagony żeby wybrać ID do usunięcia
    $wagonsResp = request('GET', "http://localhost:8080/api/coasters");
    $wagonIdToDelete = null;

    foreach ($wagonsResp as $coaster) {
        if ($coaster['id'] === $coasterId && !empty($coaster['wagons'])) {
            $wagonIdToDelete = $coaster['wagons'][0]['id'] ?? null;
            break;
        }
    }

    if ($wagonIdToDelete) {
        // Test: Delete wagon
        $responseDeleteWagon = request('DELETE', "http://localhost:8080/api/coasters/$coasterId/wagons/$wagonIdToDelete");
        if (isset($responseDeleteWagon['message'])) {
            echo "Delete wagon: " . $responseDeleteWagon['message'] . "\n";
        }
    } else {
        echo "Brak wagonów do usunięcia.\n";
    }
} else {
    echo "Nie udało się stworzyć kolejki, więc update/delete wagon nie jest możliwy.\n";
}
