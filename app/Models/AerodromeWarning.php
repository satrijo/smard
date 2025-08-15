<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AerodromeWarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'airport_code',
        'warning_number',
        'sequence_number',
        'start_time',
        'end_time',
        'phenomena',
        'source',
        'intensity',
        'observation_time',
        'preview_message',
        'translation_message',
        'status',
        'forecaster_id',
        'forecaster_name',
        'forecaster_nip',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'observation_time' => 'datetime',
        'phenomena' => 'array',
    ];

    /**
     * Scope untuk peringatan aktif
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope untuk peringatan yang masih valid (belum expired berdasarkan end_time)
     */
    public function scopeValid($query)
    {
        return $query->where('end_time', '>', now());
    }

    /**
     * Scope untuk peringatan aktif dan valid
     */
    public function scopeActiveAndValid($query)
    {
        return $query->active()->valid();
    }

    /**
     * Scope untuk peringatan yang sudah dibatalkan
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'CANCELLED');
    }

    /**
     * Scope untuk peringatan yang sudah expired
     */
    public function scopeExpired($query)
    {
        return $query->where('end_time', '<=', now());
    }

    /**
     * Update status peringatan yang sudah expired menjadi EXPIRED
     */
    public static function updateExpiredWarnings()
    {
        return self::active()
            ->where('end_time', '<=', now())
            ->update(['status' => 'EXPIRED']);
    }

    /**
     * Scope untuk peringatan berdasarkan bandara
     */
    public function scopeForAirport($query, $airportCode)
    {
        return $query->where('airport_code', $airportCode);
    }

    /**
     * Mendapatkan nomor urut berikutnya untuk bandara tertentu
     * Reset setiap hari pada jam 00:00 UTC
     */
    public static function getNextSequenceNumber($airportCode)
    {
        // Gunakan UTC timezone untuk reset harian
        $utcDate = now()->utc()->toDateString();
        
        $lastWarning = self::where('airport_code', $airportCode)
            ->whereDate('created_at', $utcDate)
            ->orderBy('sequence_number', 'desc')
            ->first();

        return $lastWarning ? $lastWarning->sequence_number + 1 : 1;
    }

    /**
     * Generate warning number berdasarkan sequence number
     */
    public static function generateWarningNumber($sequenceNumber)
    {
        return "AD WRNG {$sequenceNumber}";
    }

    /**
     * Format validity time untuk pesan baku
     */
    public function getFormattedValidityAttribute()
    {
        $start = $this->start_time->format('dHi');
        $end = $this->end_time->format('dHi');
        return "{$start}/{$end}";
    }

    /**
     * Format phenomena untuk pesan baku
     */
    public function getFormattedPhenomenaAttribute()
    {
        return collect($this->phenomena)->map(function ($phenomenon) {
            $type = $phenomenon['type'];
            $details = $phenomenon['details'] ?? null;

            switch ($type) {
                case 'SFC WSPD':
                    if ($details && isset($details['windSpeed']) && isset($details['gustSpeed'])) {
                        return "SFC WSPD {$details['windSpeed']}KT MAX {$details['gustSpeed']}KT";
                    }
                    return $type;
                
                case 'SFC WIND':
                    if ($details && isset($details['windDirection']) && isset($details['windSpeed']) && isset($details['gustSpeed'])) {
                        return "SFC WIND {$details['windDirection']}/{$details['windSpeed']}KT MAX {$details['gustSpeed']}KT";
                    }
                    return $type;
                
                case 'VIS':
                    if ($details && isset($details['visibility'])) {
                        $vis = "VIS {$details['visibility']}M";
                        if (isset($details['visibilityCause'])) {
                            $vis .= " {$details['visibilityCause']}";
                        }
                        return $vis;
                    }
                    return $type;
                
                case 'CUSTOM':
                    if ($details && isset($details['customDescription'])) {
                        return $details['customDescription'];
                    }
                    return $type;
                
                case 'TOX CHEM':
                    if ($details && isset($details['toxChemDescription']) && !empty(trim($details['toxChemDescription']))) {
                        return "TOX CHEM {$details['toxChemDescription']}";
                    }
                    return $type;
                
                default:
                    return $type;
            }
        })->join(' ');
    }

    /**
     * Generate pesan baku aerodrome warning
     */
    public function generateStandardMessage()
    {
        // Jika ini adalah pembatalan (tidak ada fenomena dan ada preview message dengan CNL)
        if (empty($this->phenomena) && str_contains($this->preview_message ?? '', 'CNL AD WRNG')) {
            return $this->preview_message;
        }
        
        $message = "{$this->airport_code} {$this->warning_number} VALID {$this->formatted_validity} {$this->formatted_phenomena}";
        
        if ($this->source === 'OBS' && $this->observation_time) {
            $obsTime = $this->observation_time->format('dHi');
            $message .= " OBS AT {$obsTime}Z";
        } else {
            $message .= " {$this->source}";
        }
        
        $message .= " {$this->intensity}=";
        
        return $message;
    }

    /**
     * Generate pesan pembatalan sesuai format standar
     */
    public function generateCancellationMessage($cancellationSequenceNumber = null)
    {
        $warningNum = $this->sequence_number;
        $cancellationNum = $cancellationSequenceNumber ?? '?';
        
        // Format waktu validitas yang akan dibatalkan
        $startTime = $this->start_time->format('dHi');
        $endTime = $this->end_time->format('dHi');
        $validityPeriod = "{$startTime}/{$endTime}";
        
        // Format waktu validitas pembatalan (dari sekarang sampai sisa waktu validitas)
        $now = now();
        $cancellationStart = $now->format('dHi');
        $cancellationEnd = $this->end_time->format('dHi'); // Sisa waktu validitas
        $cancellationValidity = "{$cancellationStart}/{$cancellationEnd}";
        
        return "WAHL AD WRNG {$cancellationNum} VALID {$cancellationValidity} CNL AD WRNG {$warningNum} {$validityPeriod}=";
    }

    /**
     * Generate terjemahan pesan pembatalan dalam Bahasa Indonesia
     */
    public function generateCancellationTranslation($cancellationSequenceNumber = null)
    {
        $warningNum = $this->sequence_number;
        $cancellationNum = $cancellationSequenceNumber ?? '?';
        $startTime = $this->start_time;
        $endTime = $this->end_time;
        
        $startDay = $startTime->format('d');
        $startMonth = $startTime->format('n') - 1; // 0-based index
        $startTimeFormatted = $startTime->format('H.i');
        $endTimeFormatted = $endTime->format('H.i');
        
        $monthNames = [
            'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
            'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'
        ];
        
        // Format waktu pembatalan (dari sekarang sampai sisa waktu validitas)
        $now = now();
        $cancellationStartDay = $now->format('d');
        $cancellationStartMonth = $monthNames[$now->format('n') - 1];
        $cancellationStartTime = $now->format('H.i');
        $cancellationEndTime = $this->end_time->format('H.i'); // Sisa waktu validitas
        
        return "AERODROME WARNING STASIUN METEOROLOGI TUNGGUL WULUNG NOMOR {$cancellationNum}, BERLAKU TANGGAL {$cancellationStartDay} {$cancellationStartMonth} 2025, ANTARA PUKUL {$cancellationStartTime} - {$cancellationEndTime} UTC, BAHWA PERINGATAN DINI CUACA NOMOR {$warningNum} (TANGGAL {$startDay} {$monthNames[$startMonth]} 2025, ANTARA PUKUL {$startTimeFormatted} - {$endTimeFormatted} UTC) TELAH BERAKHIR / DIBATALKAN.";
    }

    /**
     * Get preview message atau generate jika tidak ada
     */
    public function getPreviewMessageAttribute()
    {
        return $this->preview_message ?? $this->generateStandardMessage();
    }

    /**
     * Get translation message atau generate jika tidak ada
     */
    public function getTranslationMessageAttribute()
    {
        return $this->translation_message ?? $this->generateIndonesianTranslation();
    }

    /**
     * Generate terjemahan Indonesia (untuk fallback)
     */
    public function generateIndonesianTranslation()
    {
        // Format tanggal dan waktu
        $startDate = $this->start_time;
        $endDate = $this->end_time;
        
        $formatIndonesianDate = function($date) {
            $day = $date->format('d');
            $month = $date->format('n') - 1; // 0-based index
            $year = $date->format('Y');
            
            $monthNames = [
                'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
                'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'
            ];
            
            return "{$day} {$monthNames[$month]} {$year}";
        };

        $formatIndonesianTime = function($date) {
            return $date->format('H.i');
        };

        // Terjemahan fenomena
        $translatePhenomena = function() {
            return collect($this->phenomena)->map(function ($phenomenon) {
                $type = $phenomenon['type'];
                $details = $phenomenon['details'] ?? [];

                switch ($type) {
                    case "TS":
                        return "BADAI GUNTUR";
                    case "GR":
                        return "HUJAN ES";
                    case "HVY RA":
                        return "HUJAN LEBAT";
                    case "TSRA":
                        return "BADAI GUNTUR DENGAN HUJAN";
                    case "HVY TSRA":
                        return "BADAI GUNTUR DENGAN HUJAN LEBAT";
                    case "SFC WSPD":
                        if (isset($details['windSpeed']) && isset($details['gustSpeed'])) {
                            return "ANGIN KENCANG {$details['windSpeed']} KNOTS MAKSIMUM {$details['gustSpeed']} KNOTS";
                        }
                        return "ANGIN KENCANG";
                    case "SFC WIND":
                        if (isset($details['windDirection']) && isset($details['windSpeed']) && isset($details['gustSpeed'])) {
                            return "ANGIN KENCANG DARI ARAH {$details['windDirection']} DERAJAT DENGAN KECEPATAN {$details['windSpeed']} KNOTS MAKSIMUM {$details['gustSpeed']} KNOTS";
                        }
                        return "ANGIN KENCANG DENGAN ARAH";
                    case "VIS":
                        if (isset($details['visibility'])) {
                            $visText = "VISIBILITY {$details['visibility']} METER";
                            if (isset($details['visibilityCause'])) {
                                $causeTranslations = [
                                    'HZ' => 'TERJADI KEKABURAN UDARA YANG MENYEBABKAN JARAK PANDANG BERKURANG (HAZE)',
                                    'BR' => 'TERJADI KABUT TIPIS YANG MENYEBABKAN JARAK PANDANG BERKURANG',
                                    'RA' => 'TERJADI HUJAN YANG MENYEBABKAN JARAK PANDANG BERKURANG',
                                    'FG' => 'TERJADI KABUT TEBAL YANG MENYEBABKAN JARAK PANDANG BERKURANG'
                                ];
                                $visText .= ", " . ($causeTranslations[$details['visibilityCause']] ?? $details['visibilityCause']);
                            }
                            return $visText;
                        }
                        return "JARAK PANDANG RENDAH";
                    case "SQ":
                        return "SQUALL";
                    case "VA":
                        return "ABU VULKANIK";
                    case "TOX CHEM":
                        if (isset($details['toxChemDescription']) && !empty(trim($details['toxChemDescription']))) {
                            return "BAHAN KIMIA BERACUN {$details['toxChemDescription']}";
                        }
                        return "BAHAN KIMIA BERACUN";
                    case "TSUNAMI":
                        return "TSUNAMI";
                    case "CUSTOM":
                        return $details['customDescription'] ?? "FENOMENA LAINNYA";
                    default:
                        return $type;
                }
            })->join(", ");
        };

        // Terjemahan sumber
        $translateSource = function() {
            switch ($this->source) {
                case "OBS":
                    return "BERDASARKAN DATA OBSERVASI";
                case "FCST":
                    return "BERDASARKAN DATA FORECAST";
                default:
                    return $this->source;
            }
        };

        // Terjemahan intensitas
        $translateIntensity = function() {
            switch ($this->intensity) {
                case "WKN":
                    return "DIPRAKIRAKAN INTENSITAS AKAN BERKURANG";
                case "INTSF":
                    return "DIPRAKIRAKAN INTENSITAS AKAN MENINGKAT";
                case "NC":
                    return "DIPRAKIRAKAN INTENSITAS TANPA PERUBAHAN";
                default:
                    return $this->intensity;
            }
        };

        // Waktu observasi jika ada
        $observationTimeText = "";
        if ($this->source === "OBS" && $this->observation_time) {
            $observationTimeText = " PUKUL " . $formatIndonesianTime($this->observation_time) . " UTC";
        }

        // Nomor urut dari warning number
        $sequenceNumber = str_replace('AD WRNG ', '', $this->warning_number);

        // Membuat terjemahan lengkap
        $translation = "AERODROME WARNING STASIUN METEOROLOGI TUNGGUL WULUNG NOMOR {$sequenceNumber}, BERLAKU TANGGAL {$formatIndonesianDate($startDate)}, ANTARA PUKUL {$formatIndonesianTime($startDate)} - {$formatIndonesianTime($endDate)} UTC, {$translatePhenomena()}, {$translateSource()}{$observationTimeText}, {$translateIntensity()}.";

        return $translation;
    }
}
