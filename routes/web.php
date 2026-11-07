<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DemografiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\AdvancedAnalysisController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\InfrastrukturController;
use App\Http\Controllers\PetaController;
use App\Http\Controllers\RekomendasiController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/rekomendasi', [RekomendasiController::class, 'index'])->name('rekomendasi');
    Route::get('/demografi', [DemografiController::class, 'index'])->name('demografi');
    Route::get('/infrastruktur', [InfrastrukturController::class, 'index'])->name('infrastruktur');
    Route::get('/guru', [GuruController::class, 'index'])->name('guru');
    Route::get('/peta', [PetaController::class, 'index'])->name('peta');
    Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])
        ->middleware('throttle:30,1')
        ->name('chatbot.ask');

    Route::prefix('analisis')->group(function () {
        Route::get('/gap', [AnalysisController::class, 'gap'])->name('gap');
        Route::get('/angka-keluar', [AnalysisController::class, 'dropout'])->name('keluar');
        Route::get('/literasi', [AnalysisController::class, 'literacy'])->name('literasi');
        Route::get('/madrasah', [AnalysisController::class, 'madrasah'])->name('madrasah');
        Route::get('/indikator', [AnalysisController::class, 'indicator'])->name('indikator');
        Route::get('/akreditasi-paud', [AnalysisController::class, 'paud'])->name('paud');
        Route::get('/walidata', [AnalysisController::class, 'walidata'])->name('walidata');
        Route::get('/kebijakan', [AnalysisController::class, 'kebijakan'])->name('kebijakan');
        Route::get('/data-health', [AdvancedAnalysisController::class, 'dataHealth'])->name('data-health');
        Route::get('/sarana', [AdvancedAnalysisController::class, 'sarana'])->name('sarana');
        Route::get('/regrouping', [AdvancedAnalysisController::class, 'regrouping'])->name('regrouping');
    });
});
