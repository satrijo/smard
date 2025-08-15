<?php

/**
 * Script untuk update data forecaster yang sudah ada di database
 * Jalankan dengan: php update_forecasters.php
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AerodromeWarning;

// Data forecaster baru
$newForecasters = [
    1 => ['name' => 'Nurfaijin, S.Si., M.Sc.', 'nip' => '197111101997031001'],
    2 => ['name' => 'Sawardi, S.T', 'nip' => '197109251992021001'],
    3 => ['name' => 'Suharti', 'nip' => '197208181993012001'],
    4 => ['name' => 'Hakim Mubasyir, S.Kom', 'nip' => '197812221998031001'],
    5 => ['name' => 'Agung Surono, SP', 'nip' => '197912182000031001'],
    6 => ['name' => 'Deas Achmad Rivai, S. Kom, M. Si', 'nip' => '198906022010121001'],
    7 => ['name' => 'Gaib Prawoto, A.Md', 'nip' => '197507202009111001'],
    8 => ['name' => 'Rendi Krisnawan, A.Md', 'nip' => '198612122008121001'],
    9 => ['name' => 'Adnan Dendy Mardika, S.Kom', 'nip' => '198908192010121001'],
    10 => ['name' => 'Feriharti Nugrohowati, S.T', 'nip' => '198909052010122001'],
    11 => ['name' => 'Desi Luqman Az Zahro, S.Kom', 'nip' => '198912272012122001'],
    12 => ['name' => 'Nurmaya, S.Tr.Met.', 'nip' => '199101012009112001'],
    13 => ['name' => 'Purwanggoro Sukipiadi, S.Kom', 'nip' => '198311222012121001'],
    14 => ['name' => 'Khamim Sodik, S.Kom', 'nip' => '198408072012121001'],
    15 => ['name' => 'Satriyo Unggul Wicaksono', 'nip' => '199403122013121001'],
];

echo "🔄 Starting forecaster data update...\n";

// Update data forecaster yang sudah ada
$updatedCount = 0;
foreach ($newForecasters as $forecasterId => $forecasterData) {
    $warnings = AerodromeWarning::where('forecaster_id', $forecasterId)->get();
    
    if ($warnings->count() > 0) {
        foreach ($warnings as $warning) {
            $warning->update([
                'forecaster_name' => $forecasterData['name'],
                'forecaster_nip' => $forecasterData['nip']
            ]);
            $updatedCount++;
        }
        echo "✅ Updated {$warnings->count()} warnings for forecaster ID {$forecasterId}: {$forecasterData['name']}\n";
    }
}

echo "\n🎉 Update completed!\n";
echo "📊 Total warnings updated: {$updatedCount}\n";
echo "👥 Total forecasters: " . count($newForecasters) . "\n";

// Tampilkan daftar forecaster baru
echo "\n📋 New forecaster list:\n";
foreach ($newForecasters as $id => $forecaster) {
    echo "  {$id}. {$forecaster['name']} ({$forecaster['nip']})\n";
}

echo "\n✅ Forecaster data has been updated successfully!\n";
