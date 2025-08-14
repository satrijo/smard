<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AerodromeWarning;

class AerodromeWarningSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample data untuk testing
        AerodromeWarning::create([
            'airport_code' => 'WAHL',
            'warning_number' => 'AD WRNG 1',
            'sequence_number' => 1,
            'start_time' => now()->addHours(1),
            'end_time' => now()->addHours(3),
            'phenomena' => [
                [
                    'type' => 'TS',
                    'details' => []
                ],
                [
                    'type' => 'VIS',
                    'details' => [
                        'visibility' => 4500,
                        'visibilityCause' => 'HZ'
                    ]
                ]
            ],
            'source' => 'OBS',
            'intensity' => 'INTSF',
            'observation_time' => now(),
            'preview_message' => 'WAHL AD WRNG 1 VALID 151200/151500 TS VIS 4500M HZ OBS AT 151200Z INTSF=',
            'translation_message' => 'AERODROME WARNING STASIUN METEOROLOGI TUNGGUL WULUNG NOMOR 1, BERLAKU TANGGAL 15 AGUSTUS 2025, ANTARA PUKUL 12.00 - 15.00 UTC, BADAI GUNTUR, VISIBILITY 4500 METER, TERJADI KEKABURAN UDARA YANG MENYEBABKAN JARAK PANDANG BERKURANG (HAZE), BERDASARKAN DATA OBSERVASI PUKUL 12.00 UTC, DIPRAKIRAKAN INTENSITAS AKAN MENINGKAT.',
            'status' => 'ACTIVE',
            'forecaster_id' => 1,
            'forecaster_name' => 'Ahmad Fauzi',
            'forecaster_nip' => '198501012010011001',
        ]);

        AerodromeWarning::create([
            'airport_code' => 'WAHL',
            'warning_number' => 'AD WRNG 2',
            'sequence_number' => 2,
            'start_time' => now()->addHours(2),
            'end_time' => now()->addHours(4),
            'phenomena' => [
                [
                    'type' => 'SFC WSPD',
                    'details' => [
                        'windSpeed' => 25,
                        'gustSpeed' => 35
                    ]
                ]
            ],
            'source' => 'FCST',
            'intensity' => 'WKN',
            'observation_time' => null,
            'preview_message' => 'WAHL AD WRNG 2 VALID 151300/151600 SFC WSPD 25KT MAX 35KT FCST WKN=',
            'translation_message' => 'AERODROME WARNING STASIUN METEOROLOGI TUNGGUL WULUNG NOMOR 2, BERLAKU TANGGAL 15 AGUSTUS 2025, ANTARA PUKUL 13.00 - 16.00 UTC, ANGIN KENCANG 25 KNOTS MAKSIMUM 35 KNOTS, BERDASARKAN DATA FORECAST, DIPRAKIRAKAN INTENSITAS AKAN BERKURANG.',
            'status' => 'ACTIVE',
            'forecaster_id' => 2,
            'forecaster_name' => 'Siti Nurhaliza',
            'forecaster_nip' => '198602152010012002',
        ]);
    }
}
