<?php

namespace App\Services;

class CoasterAnalyzer
{
    public static function analyze(array $data): array
    {
        $analysis = [];

        $personel = $data['liczba_personelu'] ?? 0;
        $wagons = $data['wagons'] ?? [];
        $klienci = $data['liczba_klientow'] ?? 0;

        $wymaganyPersonel = 1 + 2 * count($wagons);
        $analysis['personel_status'] = $personel < $wymaganyPersonel ? 'brak' :
            ($personel > $wymaganyPersonel ? 'nadmiar' : 'ok');
        $analysis['personel_roznica'] = abs($personel - $wymaganyPersonel);

        $godzinyOd = $data['godziny_od'] ?? '08:00';
        $godzinyDo = $data['godziny_do'] ?? '16:00';

        $start = \DateTime::createFromFormat('H:i', $godzinyOd);
        $end = \DateTime::createFromFormat('H:i', $godzinyDo);
        $workMinutes = $start && $end ? $start->diff($end)->h * 60 + $start->diff($end)->i : 480;

        $dlTrasy = $data['dl_trasy'] ?? 1000;
        $minSpeed = min(array_column($wagons, 'predkosc_wagonu')) ?: 1;
        $czasSek = 2 * ($dlTrasy / $minSpeed) + 300;
        $kursyNaDzien = intdiv($workMinutes * 60, (int)$czasSek);

        $sumaMiejsc = array_sum(array_column($wagons, 'ilosc_miejsc'));
        $przepustowosc = $sumaMiejsc * $kursyNaDzien;

        $analysis['przepustowosc'] = $przepustowosc;
        $analysis['klienci_status'] = $przepustowosc >= $klienci ? 'ok' : 'przeciążenie';
        $analysis['klienci_roznica'] = $przepustowosc - $klienci;

        return $analysis;
    }
}
