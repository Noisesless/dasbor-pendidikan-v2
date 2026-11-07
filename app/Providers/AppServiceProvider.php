<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tier 1: CRITICAL Auth (Login Brute Force Protection)
        // Maksimal 5 percobaan per menit per kombinasi IP + email
        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));

            return Limit::perMinute(5)
                ->by($request->ip().'|'.$email)
                ->response(function (Request $request, array $headers) {
                    return response()->view('auth.login', [
                        'throttleError' => 'Terlalu banyak percobaan masuk. Silakan tunggu 1 menit sebelum mencoba lagi.',
                    ], 429, $headers);
                });
        });

        // Tier 2: AI & LLM Computational Engine (Chatbot RAG)
        // Maksimal 30 pertanyaan per menit per user terautentikasi atau IP
        RateLimiter::for('chatbot-ask', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'answer' => 'Mohon menunggu sejenak. Frekuensi pertanyaan Anda melebihi batas (30 pertanyaan per menit). Silakan coba lagi beberapa saat lagi.',
                        'sources' => ['Sistem Proteksi Dasbor'],
                        'follow_ups' => [],
                    ], 429, $headers);
                });
        });

        // Tier 3: Internal Diagnostics (Chatbot Ping & DB Health)
        // Maksimal 60 request per menit per IP
        RateLimiter::for('chatbot-ping', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
