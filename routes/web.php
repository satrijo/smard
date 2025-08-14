<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AerodromeController;
use App\Http\Controllers\AerodromeWarningController;

Route::get('/', [AerodromeController::class, 'index'])->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// These routes will be handled by airport middleware
Route::get('aerodrome', [AerodromeController::class, 'index'])->name('aerodrome');

// Routes for aerodrome warnings
Route::post('aerodrome/warnings', [AerodromeWarningController::class, 'store'])->name('aerodrome.warnings.store');
Route::get('aerodrome/warnings', [AerodromeWarningController::class, 'index'])->name('aerodrome.warnings.index');
Route::post('aerodrome/warnings/{id}/cancel', [AerodromeWarningController::class, 'cancel'])->name('aerodrome.warnings.cancel');
Route::get('aerodrome/warnings/{id}/messages', [AerodromeWarningController::class, 'getMessages'])->name('aerodrome.warnings.messages');
Route::get('aerodrome/warnings/next-sequence', [AerodromeWarningController::class, 'getNextSequence'])->name('aerodrome.warnings.next-sequence');
Route::get('aerodrome/warnings/statistics', [AerodromeWarningController::class, 'getStatistics'])->name('aerodrome.warnings.statistics');

Route::get('aerodrome/archive', [AerodromeWarningController::class, 'archive'])->name('aerodrome.archive');
Route::get('aerodrome/all-warnings', [AerodromeWarningController::class, 'allWarnings'])->name('aerodrome.all-warnings');
Route::get('aerodrome/all-warnings/export-pdf', [AerodromeWarningController::class, 'exportPdf'])->name('aerodrome.all-warnings.export-pdf');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
