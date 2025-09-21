<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LegalController;

Route::get('/', [LegalController::class, 'index'])->name('home');
Route::post('/analyze', [LegalController::class, 'analyze'])->name('analyze');
Route::post('/upload', [LegalController::class, 'upload'])->name('upload');

// PDF viewer helpers
Route::get('/pdf-url', [LegalController::class, 'pdfUrl'])->name('pdf.url');
Route::post('/stamp', [LegalController::class, 'stamp'])->name('stamp');

// TTS
Route::get('/tts/{lang}', [LegalController::class, 'tts'])->name('tts');
