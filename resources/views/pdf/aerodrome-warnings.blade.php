<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Peringatan Aerodrome Warning</title>
    <style>
                 body {
             font-family: Arial, sans-serif;
             font-size: 9px;
             line-height: 1.2;
             color: #333;
             margin: 0;
             padding: 12px;
         }
        
                 .header {
             text-align: center;
             margin-bottom: 15px;
             border-bottom: 2px solid #333;
             padding-bottom: 10px;
         }
         
         .header h1 {
             margin: 0;
             font-size: 18px;
             font-weight: bold;
             color: #1f2937;
         }
         
         .header p {
             margin: 2px 0;
             font-size: 10px;
             color: #6b7280;
         }
        
                 .info-section {
             margin-bottom: 12px;
             padding: 8px;
             background-color: #f9fafb;
             border-radius: 5px;
         }
         
         .info-grid {
             display: table;
             width: 100%;
             margin-bottom: 8px;
         }
         
         .info-item {
             display: table-cell;
             width: 20%;
             text-align: center;
             padding: 6px 4px;
             border: 1px solid #e5e7eb;
             background-color: white;
         }
         
         .info-item:first-child {
             border-left: 1px solid #e5e7eb;
         }
         
         .info-number {
             font-size: 14px;
             font-weight: bold;
             margin-bottom: 2px;
         }
         
         .info-label {
             font-size: 8px;
             color: #6b7280;
         }
        
                 .filter-info {
             margin-bottom: 12px;
             padding: 6px;
             background-color: #eff6ff;
             border-left: 4px solid #3b82f6;
         }
         
         .filter-info h3 {
             margin: 0 0 2px 0;
             font-size: 10px;
             color: #1e40af;
         }
         
         .filter-info p {
             margin: 0;
             font-size: 9px;
             color: #374151;
         }
        
                 .warning-item {
             margin-bottom: 12px;
             border: 1px solid #e5e7eb;
             border-radius: 5px;
             overflow: hidden;
             page-break-inside: avoid;
         }
         
         .warning-header {
             background-color: #f3f4f6;
             padding: 6px;
             border-bottom: 1px solid #e5e7eb;
         }
         
         .warning-title {
             font-size: 11px;
             font-weight: bold;
             margin: 0;
             color: #1f2937;
         }
         
         .warning-status {
             display: inline-block;
             padding: 2px 5px;
             border-radius: 3px;
             font-size: 8px;
             font-weight: bold;
             margin-left: 6px;
         }
        
        .status-active {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .status-expired {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .status-cancellation {
            background-color: #e0e7ff;
            color: #3730a3;
        }
        
                 .warning-content {
             padding: 10px;
         }
         
         .warning-section {
             margin-bottom: 10px;
         }
         
         .warning-section:last-child {
             margin-bottom: 0;
         }
         
         .section-title {
             font-size: 9px;
             font-weight: bold;
             color: #6b7280;
             margin-bottom: 3px;
             text-transform: uppercase;
         }
         
         .section-content {
             font-size: 9px;
             line-height: 1.2;
             color: #374151;
         }
        
                 .sandi-box {
             background-color: #f9fafb;
             border: 1px solid #e5e7eb;
             padding: 5px;
             font-family: 'Courier New', monospace;
             font-size: 8px;
             word-break: break-all;
             line-height: 1.1;
         }
         
         .translation-box {
             background-color: #eff6ff;
             border: 1px solid #bfdbfe;
             padding: 5px;
             font-size: 8px;
             line-height: 1.1;
         }
        
                 .details-grid {
             display: table;
             width: 100%;
             margin-top: 6px;
         }
         
         .detail-item {
             display: table-cell;
             width: 25%;
             padding: 3px;
             font-size: 8px;
             vertical-align: top;
         }
         
         .detail-label {
             font-weight: bold;
             color: #6b7280;
             margin-bottom: 1px;
             font-size: 7px;
         }
         
         .detail-value {
             color: #374151;
             word-break: break-word;
         }
        
                 .phenomena-list {
             margin-top: 6px;
         }
         
         .phenomenon-tag {
             display: inline-block;
             background-color: #f3f4f6;
             border: 1px solid #d1d5db;
             padding: 1px 3px;
             border-radius: 2px;
             font-size: 7px;
             margin-right: 3px;
             margin-bottom: 1px;
         }
        
                 .footer {
             margin-top: 15px;
             text-align: center;
             font-size: 8px;
             color: #6b7280;
             border-top: 1px solid #e5e7eb;
             padding-top: 8px;
         }
         
         .page-break {
             page-break-before: always;
         }
         
         .no-data {
             text-align: center;
             padding: 30px;
             color: #6b7280;
             font-style: italic;
         }
         
         /* Ensure proper page breaks */
         .warning-item {
             page-break-inside: avoid;
             orphans: 1;
             widows: 1;
         }
          
         /* Keep header with the next section */
         .warning-header {
             page-break-after: avoid;
         }
          
         /* Keep content together */
         .warning-content {
             page-break-inside: avoid;
         }
          
         /* Do not split a section across pages */
         .warning-section {
             page-break-inside: avoid;
         }
          
         /* Keep content blocks intact */
         .section-content {
             page-break-inside: avoid;
         }

         /* Ensure code blocks are not split */
         .sandi-box,
         .translation-box {
             page-break-inside: avoid;
         }

         /* Keep detail grids together */
         .details-grid,
         .phenomena-list {
             page-break-inside: avoid;
         }
    </style>
</head>
<body>
         <div class="header">
         <h1>{{ $title }}</h1>
         <p>Bandar Udara Tunggul Wulung (WAHL)</p>
         <p>Dibuat pada: {{ $generatedAt }}</p>
     </div>

    <div class="info-section">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-number">{{ $statistics['total'] }}</div>
                <div class="info-label">Total</div>
            </div>
            <div class="info-item">
                <div class="info-number" style="color: #166534;">{{ $statistics['active'] }}</div>
                <div class="info-label">Aktif</div>
            </div>
            <div class="info-item">
                <div class="info-number" style="color: #92400e;">{{ $statistics['expired'] }}</div>
                <div class="info-label">Expired</div>
            </div>
            <div class="info-item">
                <div class="info-number" style="color: #991b1b;">{{ $statistics['cancelled'] }}</div>
                <div class="info-label">Dibatalkan</div>
            </div>
            <div class="info-item">
                <div class="info-number" style="color: #3730a3;">{{ $statistics['cancellations'] }}</div>
                <div class="info-label">Pembatalan</div>
            </div>
        </div>
    </div>

    <div class="filter-info">
        <h3>Filter yang Diterapkan:</h3>
        <p>{{ $filterDescription }}</p>
    </div>

                   @if($warnings->count() > 0)
          @foreach($warnings as $index => $warning)
            
            <div class="warning-item">
                <div class="warning-header">
                    <h3 class="warning-title">
                        {{ $warning['is_cancellation'] ? 'Pembatalan' : 'Peringatan' }} #{{ $warning['sequence_number'] }}
                        <span class="warning-status 
                            @if($warning['is_cancellation']) status-cancellation
                            @elseif($warning['status'] === 'ACTIVE') status-active
                            @elseif($warning['status'] === 'EXPIRED') status-expired
                            @elseif($warning['status'] === 'CANCELLED') status-cancelled
                            @endif">
                            @if($warning['is_cancellation'])
                                Pembatalan
                            @elseif($warning['status'] === 'ACTIVE')
                                Aktif
                            @elseif($warning['status'] === 'EXPIRED')
                                Expired
                            @elseif($warning['status'] === 'CANCELLED')
                                Dibatalkan
                            @else
                                {{ $warning['status'] }}
                            @endif
                        </span>
                    </h3>
                </div>
                
                <div class="warning-content">
                    <div class="warning-section">
                        <div class="section-title">Sandi</div>
                        <div class="section-content">
                            <div class="sandi-box">{{ $warning['preview_message'] }}</div>
                        </div>
                    </div>
                    
                    <div class="warning-section">
                        <div class="section-title">Terjemahan</div>
                        <div class="section-content">
                            <div class="translation-box">{{ $warning['translation_message'] }}</div>
                        </div>
                    </div>
                    
                    <div class="warning-section">
                        <div class="section-title">Detail Informasi</div>
                        <div class="details-grid">
                            <div class="detail-item">
                                <div class="detail-label">Diterbitkan oleh</div>
                                <div class="detail-value">{{ $warning['forecaster_name'] }} ({{ $warning['forecaster_nip'] }})</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Waktu terbit</div>
                                <div class="detail-value">{{ \Carbon\Carbon::parse($warning['created_at'])->format('d/m/Y H:i') }} UTC</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Berlaku dari</div>
                                <div class="detail-value">{{ \Carbon\Carbon::parse($warning['startTime'])->format('d/m/Y H:i') }} UTC</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Berlaku sampai</div>
                                <div class="detail-value">{{ \Carbon\Carbon::parse($warning['valid_to'])->format('d/m/Y H:i') }} UTC</div>
                            </div>
                        </div>
                    </div>
                    
                    @if(!$warning['is_cancellation'] && count($warning['phenomena']) > 0)
                        <div class="warning-section">
                            <div class="section-title">Fenomena Cuaca</div>
                            <div class="phenomena-list">
                                @foreach($warning['phenomena'] as $phenomenon)
                                    <span class="phenomenon-tag">{{ $phenomenon['name'] }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <div class="no-data">
            <p>Tidak ada peringatan ditemukan berdasarkan filter yang diterapkan.</p>
        </div>
    @endif

    <div class="footer">
        <p>Dokumen ini dibuat secara otomatis oleh sistem SMARD (Smart Aerodrome Warning System)</p>
        <p>© {{ date('Y') }} Stasiun Meteorologi Tunggul Wulung - Badan Meteorologi, Klimatologi, dan Geofisika</p>
    </div>
</body>
</html>
