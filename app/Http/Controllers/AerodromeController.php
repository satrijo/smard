<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\AerodromeWarning;

class AerodromeController extends Controller
{
    public function index()
    {
        // Data bandara yang selalu WAHL
        $airport = [
            'name' => 'Tunggul Wulung - Cilacap',
            'icao_code' => 'WAHL',
            'location' => 'Cilacap, Central Java',
            'is_default' => true
        ];

        $selectedPegawai = [
            'id' => 1,
            'nama_lengkap' => 'John Doe',
            'position' => 'Meteorologist',
            'department' => 'Meteorology'
        ];

        $phenomena = [
            ['code' => 'TS', 'name' => 'Badai Guntur (TS)'],
            ['code' => 'GR', 'name' => 'Hujan Es (GR)'],
            ['code' => 'HVY RA', 'name' => 'Hujan Lebat (HVY RA)'],
            ['code' => 'TSRA', 'name' => 'Badai Guntur dengan Hujan (TSRA)'],
            ['code' => 'HVY TSRA', 'name' => 'Badai Guntur dengan Hujan Lebat (HVY TSRA)'],
            ['code' => 'SFC WSPD', 'name' => 'Angin Kencang Tanpa Arah (SFC WSPD)'],
            ['code' => 'SFC WIND', 'name' => 'Angin Kencang Dengan Arah (SFC WIND)'],
            ['code' => 'VIS', 'name' => 'Jarak Pandang Rendah (VIS)'],
            ['code' => 'SQ', 'name' => 'Squall (SQ)'],
            ['code' => 'VA', 'name' => 'Abu Vulkanik (VA)'],
            ['code' => 'TSUNAMI', 'name' => 'Tsunami'],
            ['code' => 'CUSTOM', 'name' => 'Lainnya (Free Text)']
        ];

        // Ambil peringatan aktif dari database
        $activeWarnings = AerodromeWarning::active()
            ->forAirport('WAHL')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($warning) {
                return [
                    'id' => $warning->id,
                    'sequence_number' => $warning->sequence_number,
                    'warningNumber' => $warning->warning_number,
                    'airportCode' => $warning->airport_code,
                    'startTime' => $warning->start_time->toISOString(),
                    'valid_to' => $warning->end_time->toISOString(),
                    'validityEnd' => $warning->end_time->format('dHi'),
                    'phenomena' => collect($warning->phenomena)->map(function ($phenomenon) {
                        return [
                            'name' => $this->getPhenomenonDisplayName($phenomenon['type']),
                            'type' => $phenomenon['type'],
                            'details' => $phenomenon['details'] ?? null
                        ];
                    })->toArray(),
                    'source' => $warning->source,
                    'intensity' => $warning->intensity,
                    'observationTime' => $warning->observation_time?->toISOString(),
                    'preview_message' => $warning->preview_message,
                    'translation_message' => $warning->translation_message,
                    'status' => $warning->status,
                    'forecaster_name' => $warning->forecaster_name,
                    'forecaster_nip' => $warning->forecaster_nip,
                    'created_at' => $warning->created_at->toISOString(),
                ];
            });

        return Inertia::render('aerodrome/Index', [
            'airport' => $airport,
            'selectedPegawai' => $selectedPegawai,
            'phenomena' => $phenomena,
            'activeWarnings' => $activeWarnings,
            'flash' => [
                'success' => session('success'),
                'error' => session('error')
            ],
            'metaTitle' => 'Dashboard - Aerodrome Warning System'
        ]);
    }

    /**
     * Helper method untuk mendapatkan nama display fenomena
     */
    private function getPhenomenonDisplayName($type)
    {
        $names = [
            'TS' => 'Badai Guntur (TS)',
            'GR' => 'Hujan Es (GR)',
            'HVY RA' => 'Hujan Lebat (HVY RA)',
            'TSRA' => 'Badai Guntur dengan Hujan (TSRA)',
            'HVY TSRA' => 'Badai Guntur dengan Hujan Lebat (HVY TSRA)',
            'SFC WSPD' => 'Angin Kencang Tanpa Arah (SFC WSPD)',
            'SFC WIND' => 'Angin Kencang Dengan Arah (SFC WIND)',
            'VIS' => 'Jarak Pandang Rendah (VIS)',
            'SQ' => 'Squall (SQ)',
            'VA' => 'Abu Vulkanik (VA)',
            'TSUNAMI' => 'Tsunami',
            'CUSTOM' => 'Lainnya (Free Text)'
        ];

        return $names[$type] ?? $type;
    }
}
