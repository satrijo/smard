<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\AerodromeWarning;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;

class AerodromeWarningController extends Controller
{
    public function store(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'airportCode' => 'required|string|max:4|in:WAHL', // Hanya WAHL yang diizinkan
            'warningNumber' => 'required|string',
            'startTime' => 'required|date',
            'endTime' => 'required|date|after:startTime',
            'phenomena' => 'required|array|min:1',
            'source' => 'required|in:OBS,FCST',
            'intensity' => 'required|in:WKN,INTSF,NC',
            'observationTime' => 'nullable|date',
            'previewMessage' => 'nullable|string',
            'translationMessage' => 'nullable|string',
            'forecaster_id' => 'required|integer',
            'forecaster_name' => 'required|string',
            'forecaster_nip' => 'required|string',
        ]);

        try {
            // Generate sequence number untuk hari ini
            $sequenceNumber = AerodromeWarning::getNextSequenceNumber($validated['airportCode']);
            
            // Generate warning number
            $warningNumber = AerodromeWarning::generateWarningNumber($sequenceNumber);

            // Format phenomena untuk disimpan ke database
            $formattedPhenomena = collect($validated['phenomena'])->map(function ($phenomenon) {
                $formatted = [
                    'type' => $phenomenon['type'],
                    'details' => []
                ];

                // Tambahkan detail berdasarkan tipe fenomena
                switch ($phenomenon['type']) {
                    case 'SFC WSPD':
                        if (isset($phenomenon['windSpeed'])) $formatted['details']['windSpeed'] = $phenomenon['windSpeed'];
                        if (isset($phenomenon['gustSpeed'])) $formatted['details']['gustSpeed'] = $phenomenon['gustSpeed'];
                        break;
                    
                    case 'SFC WIND':
                        if (isset($phenomenon['windDirection'])) $formatted['details']['windDirection'] = $phenomenon['windDirection'];
                        if (isset($phenomenon['windSpeed'])) $formatted['details']['windSpeed'] = $phenomenon['windSpeed'];
                        if (isset($phenomenon['gustSpeed'])) $formatted['details']['gustSpeed'] = $phenomenon['gustSpeed'];
                        break;
                    
                    case 'VIS':
                        if (isset($phenomenon['visibility'])) $formatted['details']['visibility'] = $phenomenon['visibility'];
                        if (isset($phenomenon['visibilityCause'])) $formatted['details']['visibilityCause'] = $phenomenon['visibilityCause'];
                        break;
                    
                    case 'CUSTOM':
                        if (isset($phenomenon['customDescription'])) $formatted['details']['customDescription'] = $phenomenon['customDescription'];
                        break;
                    
                    case 'TOX CHEM':
                        if (isset($phenomenon['toxChemDescription']) && !empty(trim($phenomenon['toxChemDescription']))) {
                            $formatted['details']['toxChemDescription'] = $phenomenon['toxChemDescription'];
                        }
                        break;
                }

                return $formatted;
            })->toArray();

            // Simpan ke database
            $warning = AerodromeWarning::create([
                'airport_code' => $validated['airportCode'],
                'warning_number' => $warningNumber,
                'sequence_number' => $sequenceNumber,
                'start_time' => $validated['startTime'],
                'end_time' => $validated['endTime'],
                'phenomena' => $formattedPhenomena,
                'source' => $validated['source'],
                'intensity' => $validated['intensity'],
                'observation_time' => $validated['observationTime'],
                'preview_message' => $validated['previewMessage'] ?? null,
                'translation_message' => $validated['translationMessage'] ?? null,
                'forecaster_id' => $validated['forecaster_id'],
                'forecaster_name' => $validated['forecaster_name'],
                'forecaster_nip' => $validated['forecaster_nip'],
                'status' => 'ACTIVE',
            ]);

            // Kirim pesan WhatsApp
            try {
                $whatsappService = new WhatsAppService();
                $whatsappService->sendAerodromeWarning($warning);
            } catch (\Exception $e) {
                // Log error tapi jangan gagalkan proses utama
                Log::error('Failed to send WhatsApp message for warning: ' . $e->getMessage());
            }

            return back()->with('success', 'Peringatan berhasil diterbitkan');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function index()
    {
        // Update peringatan yang sudah expired
        AerodromeWarning::updateExpiredWarnings();
        
        // Ambil peringatan aktif dan valid dari database (exclude pembatalan)
        $activeWarnings = AerodromeWarning::activeAndValid()
            ->forAirport('WAHL')
            ->where(function ($query) {
                // Exclude peringatan yang berisi "CNL AD WRNG" (pembatalan)
                $query->whereNull('preview_message')
                      ->orWhere('preview_message', 'NOT LIKE', '%CNL AD WRNG%');
            })
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

        return response()->json([
            'success' => true,
            'data' => $activeWarnings
        ]);
    }

    public function cancel($id)
    {
        try {
            $warning = AerodromeWarning::findOrFail($id);
            
            // Generate nomor urut pembatalan untuk hari ini
            $cancellationSequenceNumber = AerodromeWarning::getNextSequenceNumber($warning->airport_code);
            
            // Generate warning number untuk pembatalan
            $cancellationWarningNumber = AerodromeWarning::generateWarningNumber($cancellationSequenceNumber);
            
            // Format waktu validitas pembatalan (dari sekarang sampai sisa waktu validitas)
            $now = now();
            $cancellationStartTime = $now;
            $cancellationEndTime = $warning->end_time; // Sisa waktu validitas
            
            // Generate pesan pembatalan
            $cancellationMessage = $warning->generateCancellationMessage($cancellationSequenceNumber);
            $cancellationTranslation = $warning->generateCancellationTranslation($cancellationSequenceNumber);
            
            // Gunakan pesan yang diedit jika ada
            $editedMessage = request()->input('edited_message');
            $editedTranslation = request()->input('edited_translation');
            
            if ($editedMessage) {
                $cancellationMessage = $editedMessage;
            }
            if ($editedTranslation) {
                $cancellationTranslation = $editedTranslation;
            }
            
            // Ambil data forecaster yang sedang melakukan pembatalan dari request
            $cancellingForecasterId = request()->input('forecaster_id', $warning->forecaster_id);
            $cancellingForecasterName = request()->input('forecaster_name', $warning->forecaster_name);
            $cancellingForecasterNip = request()->input('forecaster_nip', $warning->forecaster_nip);
            
            // Simpan pembatalan sebagai peringatan baru
            $cancellationWarning = AerodromeWarning::create([
                'airport_code' => $warning->airport_code,
                'warning_number' => $cancellationWarningNumber,
                'sequence_number' => $cancellationSequenceNumber,
                'start_time' => $cancellationStartTime,
                'end_time' => $cancellationEndTime,
                'phenomena' => [], // Pembatalan tidak memiliki fenomena
                'source' => 'FCST', // Pembatalan biasanya berdasarkan forecast
                'intensity' => 'NC', // No Change
                'observation_time' => null,
                'preview_message' => $cancellationMessage,
                'translation_message' => $cancellationTranslation,
                'forecaster_id' => $cancellingForecasterId,
                'forecaster_name' => $cancellingForecasterName,
                'forecaster_nip' => $cancellingForecasterNip,
                'status' => 'ACTIVE',
            ]);
            
            // Update status peringatan yang dibatalkan menjadi CANCELLED (TIDAK MENGUBAH SANDI)
            $warning->update(['status' => 'CANCELLED']);

            // Kirim pesan WhatsApp untuk pembatalan
            try {
                $whatsappService = new WhatsAppService();
                $whatsappService->sendCancellationMessage($cancellationWarning, $warning);
            } catch (\Exception $e) {
                // Log error tapi jangan gagalkan proses utama
                Log::error('Failed to send WhatsApp cancellation message: ' . $e->getMessage());
            }

            // Jika request dari AJAX/Inertia, kembalikan JSON
            if (request()->expectsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => 'Peringatan berhasil dibatalkan',
                    'cancellation_message' => $cancellationMessage,
                    'cancellation_translation' => $cancellationTranslation,
                    'cancellation_sequence_number' => $cancellationSequenceNumber,
                    'cancellation_warning_id' => $cancellationWarning->id
                ]);
            }

            // Jika request biasa, redirect dengan flash message
            return back()->with('success', 'Peringatan berhasil dibatalkan');

        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Get preview dan translation untuk warning tertentu
     */
    public function getMessages($id)
    {
        try {
            $warning = AerodromeWarning::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'preview_message' => $warning->preview_message,
                    'translation_message' => $warning->translation_message,
                    'generated_preview' => $warning->generateStandardMessage(),
                    'generated_translation' => $warning->generateIndonesianTranslation()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sequence number berikutnya untuk form
     */
    public function getNextSequence()
    {
        try {
            $sequenceNumber = AerodromeWarning::getNextSequenceNumber('WAHL');
            
            return response()->json([
                'success' => true,
                'sequence_number' => $sequenceNumber
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get warning statistics
     */
    public function getStatistics()
    {
        try {
            $stats = [
                'active' => AerodromeWarning::active()->forAirport('WAHL')->count(),
                'active_and_valid' => AerodromeWarning::activeAndValid()
                    ->forAirport('WAHL')
                    ->where(function ($query) {
                        // Exclude peringatan yang berisi "CNL AD WRNG" (pembatalan)
                        $query->whereNull('preview_message')
                              ->orWhere('preview_message', 'NOT LIKE', '%CNL AD WRNG%');
                    })->count(),
                'expired' => AerodromeWarning::expired()->forAirport('WAHL')->count(),
                'cancelled' => AerodromeWarning::cancelled()->forAirport('WAHL')->count(),
                'total' => AerodromeWarning::forAirport('WAHL')->count(),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get archive page with today's warnings including cancellations
     */
    public function archive()
    {
        // Ambil peringatan hari ini saja (termasuk pembatalan) untuk arsip
        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        
        $todayWarnings = AerodromeWarning::forAirport('WAHL')
            ->whereBetween('created_at', [$today, $tomorrow])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($warning) {
                $isCancellation = str_contains($warning->getRawOriginal('preview_message') ?? '', 'CNL AD WRNG');
                
                return [
                    'id' => $warning->id,
                    'sequence_number' => $warning->sequence_number,
                    'warningNumber' => $warning->warning_number,
                    'airportCode' => $warning->airport_code,
                    'startTime' => $warning->start_time->toISOString(),
                    'valid_to' => $warning->end_time->toISOString(),
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
                    'preview_message' => $warning->getRawOriginal('preview_message'), // Ambil langsung dari kolom database tanpa accessor
                    'translation_message' => $warning->getRawOriginal('translation_message'), // Ambil langsung dari kolom database tanpa accessor
                    'status' => $warning->status,
                    'forecaster_name' => $warning->forecaster_name,
                    'forecaster_nip' => $warning->forecaster_nip,
                    'created_at' => $warning->created_at->toISOString(),
                    'is_cancellation' => $isCancellation,
                    'type' => $isCancellation ? 'cancellation' : 'warning'
                ];
            });

        return Inertia::render('aerodrome/Archive', [
            'warnings' => $todayWarnings,
            'metaTitle' => 'Arsip Hari Ini - Aerodrome Warning System'
        ]);
    }

    /**
     * Get all warnings page with filters and pagination
     */
    public function allWarnings(Request $request)
    {
        // Filter parameters
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $status = $request->get('status');
        $phenomenon = $request->get('phenomenon');
        $forecaster = $request->get('forecaster');
        $search = $request->get('search');
        $perPage = $request->get('per_page', 20);
        
        // Build query
        $query = AerodromeWarning::forAirport('WAHL');
        
        // Apply date filter
        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        }
        
        // Apply status filter
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        // Apply forecaster filter
        if ($forecaster && $forecaster !== 'all') {
            $query->where('forecaster_name', 'LIKE', "%{$forecaster}%");
        }
        
        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('sequence_number', 'LIKE', "%{$search}%")
                  ->orWhere('preview_message', 'LIKE', "%{$search}%")
                  ->orWhere('translation_message', 'LIKE', "%{$search}%");
            });
        }
        
        // Get paginated results
        $warnings = $query->orderBy('created_at', 'desc')
                          ->paginate($perPage)
                          ->through(function ($warning) {
                              $isCancellation = str_contains($warning->getRawOriginal('preview_message') ?? '', 'CNL AD WRNG');
                              
                              return [
                                  'id' => $warning->id,
                                  'sequence_number' => $warning->sequence_number,
                                  'warningNumber' => $warning->warning_number,
                                  'airportCode' => $warning->airport_code,
                                  'startTime' => $warning->start_time->toISOString(),
                                  'valid_to' => $warning->end_time->toISOString(),
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
                                  'preview_message' => $warning->getRawOriginal('preview_message'),
                                  'translation_message' => $warning->getRawOriginal('translation_message'),
                                  'status' => $warning->status,
                                  'forecaster_name' => $warning->forecaster_name,
                                  'forecaster_nip' => $warning->forecaster_nip,
                                  'created_at' => $warning->created_at->toISOString(),
                                  'is_cancellation' => $isCancellation,
                                  'type' => $isCancellation ? 'cancellation' : 'warning'
                              ];
                          });

        // Get statistics
        $statistics = [
            'total' => AerodromeWarning::forAirport('WAHL')->count(),
            'active' => AerodromeWarning::active()->forAirport('WAHL')->count(),
            'expired' => AerodromeWarning::expired()->forAirport('WAHL')->count(),
            'cancelled' => AerodromeWarning::cancelled()->forAirport('WAHL')->count(),
            'cancellations' => AerodromeWarning::forAirport('WAHL')
                ->where('preview_message', 'LIKE', '%CNL AD WRNG%')
                ->count(),
        ];

        // Get filter options
        $filterOptions = [
            'forecasters' => AerodromeWarning::forAirport('WAHL')
                ->distinct()
                ->pluck('forecaster_name')
                ->filter()
                ->values(),
                         'phenomena' => [
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
                 'TOX CHEM' => 'Bahan Kimia Beracun (TOX CHEM)',
                 'TSUNAMI' => 'Tsunami',
                 'CUSTOM' => 'Lainnya (Free Text)'
             ]
        ];

        return Inertia::render('aerodrome/AllWarnings', [
            'warnings' => $warnings,
            'filters' => $request->all(),
            'statistics' => $statistics,
            'filterOptions' => $filterOptions,
            'metaTitle' => 'Semua Peringatan - Aerodrome Warning System'
        ]);
    }

    /**
     * Export warnings to PDF based on filters
     */
    public function exportPdf(Request $request)
    {
        // Filter parameters (same as allWarnings method)
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $status = $request->get('status');
        $phenomenon = $request->get('phenomenon');
        $forecaster = $request->get('forecaster');
        $search = $request->get('search');
        
        // Build query (same as allWarnings method)
        $query = AerodromeWarning::forAirport('WAHL');
        
        // Apply date filter
        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        }
        
        // Apply status filter
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        // Apply forecaster filter
        if ($forecaster && $forecaster !== 'all') {
            $query->where('forecaster_name', 'LIKE', "%{$forecaster}%");
        }
        
        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('sequence_number', 'LIKE', "%{$search}%")
                  ->orWhere('preview_message', 'LIKE', "%{$search}%")
                  ->orWhere('translation_message', 'LIKE', "%{$search}%");
            });
        }
        
        // Get all results (no pagination for PDF)
        $warnings = $query->orderBy('created_at', 'desc')
                          ->get()
                          ->map(function ($warning) {
                              $isCancellation = str_contains($warning->getRawOriginal('preview_message') ?? '', 'CNL AD WRNG');
                              
                              return [
                                  'id' => $warning->id,
                                  'sequence_number' => $warning->sequence_number,
                                  'warningNumber' => $warning->warning_number,
                                  'airportCode' => $warning->airport_code,
                                  'startTime' => $warning->start_time->toISOString(),
                                  'valid_to' => $warning->end_time->toISOString(),
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
                                  'preview_message' => $warning->getRawOriginal('preview_message'),
                                  'translation_message' => $warning->getRawOriginal('translation_message'),
                                  'status' => $warning->status,
                                  'forecaster_name' => $warning->forecaster_name,
                                  'forecaster_nip' => $warning->forecaster_nip,
                                  'created_at' => $warning->created_at->toISOString(),
                                  'is_cancellation' => $isCancellation,
                                  'type' => $isCancellation ? 'cancellation' : 'warning'
                              ];
                          });

        // Get statistics
        $statistics = [
            'total' => AerodromeWarning::forAirport('WAHL')->count(),
            'active' => AerodromeWarning::active()->forAirport('WAHL')->count(),
            'expired' => AerodromeWarning::expired()->forAirport('WAHL')->count(),
            'cancelled' => AerodromeWarning::cancelled()->forAirport('WAHL')->count(),
            'cancellations' => AerodromeWarning::forAirport('WAHL')
                ->where('preview_message', 'LIKE', '%CNL AD WRNG%')
                ->count(),
        ];

        // Generate filter description
        $filterDescription = $this->generateFilterDescription($request->all());

        // Generate dynamic title based on filters
        $title = $this->generateDynamicTitle($request->all(), $warnings->count());

        // Generate PDF
        $pdf = Pdf::loadView('pdf.aerodrome-warnings', [
            'warnings' => $warnings,
            'statistics' => $statistics,
            'filterDescription' => $filterDescription,
            'title' => $title,
            'generatedAt' => now()->format('d/m/Y H:i:s')
        ]);

        // Set paper size and orientation
        $pdf->setPaper('A4', 'portrait');

        // Generate filename
        $filename = 'aerodrome-warnings-' . now()->format('Y-m-d-H-i-s') . '.pdf';

        // Return PDF for download
        return $pdf->download($filename);
    }

    /**
     * Generate dynamic title for PDF based on filters
     */
    private function generateDynamicTitle($filters, $count)
    {
        $titleParts = ['LAPORAN AERODROME WARNING'];
        
        // Add status-specific title
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $statusLabels = [
                'ACTIVE' => 'AKTIF',
                'EXPIRED' => 'EXPIRED',
                'CANCELLED' => 'DIBATALKAN'
            ];
            $titleParts[] = $statusLabels[$filters['status']] ?? strtoupper($filters['status']);
        }
        
        // Add date-specific title
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $dateFrom = date('d/m/Y', strtotime($filters['date_from']));
            $dateTo = date('d/m/Y', strtotime($filters['date_to']));
            
            if ($dateFrom === $dateTo) {
                $titleParts[] = "TANGGAL " . $dateFrom;
            } else {
                $titleParts[] = "PERIODE " . $dateFrom . " - " . $dateTo;
            }
        }
        
        // Add forecaster-specific title
        if (!empty($filters['forecaster']) && $filters['forecaster'] !== 'all') {
            $titleParts[] = "FORECASTER " . strtoupper($filters['forecaster']);
        }
        
        // Add search-specific title
        if (!empty($filters['search'])) {
            $titleParts[] = "PENCARIAN: " . strtoupper($filters['search']);
        }
        
        // Add count information
        if ($count > 0) {
            $titleParts[] = "(" . $count . " DATA)";
        }
        
        return implode(' - ', $titleParts);
    }

    /**
     * Generate filter description for PDF
     */
    private function generateFilterDescription($filters)
    {
        $descriptions = [];

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $descriptions[] = "Tanggal: " . date('d/m/Y', strtotime($filters['date_from'])) . " - " . date('d/m/Y', strtotime($filters['date_to']));
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $statusLabels = [
                'ACTIVE' => 'Aktif',
                'EXPIRED' => 'Expired',
                'CANCELLED' => 'Dibatalkan'
            ];
            $descriptions[] = "Status: " . ($statusLabels[$filters['status']] ?? $filters['status']);
        }

        if (!empty($filters['forecaster']) && $filters['forecaster'] !== 'all') {
            $descriptions[] = "Forecaster: " . $filters['forecaster'];
        }

        if (!empty($filters['search'])) {
            $descriptions[] = "Pencarian: " . $filters['search'];
        }

        return empty($descriptions) ? "Semua peringatan" : implode(", ", $descriptions);
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
            'TOX CHEM' => 'Bahan Kimia Beracun (TOX CHEM)',
            'TSUNAMI' => 'Tsunami',
            'CUSTOM' => 'Lainnya (Free Text)'
        ];

        return $names[$type] ?? $type;
    }
}
