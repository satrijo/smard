<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aerodrome_warnings', function (Blueprint $table) {
            $table->id();
            $table->string('airport_code', 4); // WAHL
            $table->string('warning_number'); // AD WRNG 1
            $table->integer('sequence_number'); // 1
            $table->datetime('start_time'); // Waktu mulai validitas
            $table->datetime('end_time'); // Waktu berakhir validitas
            $table->json('phenomena'); // Array fenomena cuaca

            $table->enum('source', ['OBS', 'FCST']); // Sumber data
            $table->enum('intensity', ['WKN', 'INTSF', 'NC']); // Intensitas
            $table->datetime('observation_time')->nullable(); // Waktu observasi (jika OBS)
            $table->enum('status', ['ACTIVE', 'CANCELLED', 'EXPIRED'])->default('ACTIVE');
            $table->text('preview_message')->nullable();
            $table->text('translation_message')->nullable();
            // Data forecaster
            $table->integer('forecaster_id');
            $table->string('forecaster_name');
            $table->string('forecaster_nip');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index(['airport_code', 'status']);
            $table->index(['start_time', 'end_time']);
            $table->index('forecaster_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aerodrome_warnings');
    }
};
