<?php

namespace App\Http\Controllers;

use App\Services\EducationRagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatbotController extends Controller
{
    public function ask(Request $request, EducationRagService $rag): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
            'history' => ['nullable', 'array', 'max:10'],
            'history.*.role' => ['nullable', 'string'],
            'history.*.text' => ['nullable', 'string', 'max:5000'],
        ]);

        $question = trim((string) $validated['question']);
        $history = (array) ($validated['history'] ?? []);

        try {
            $result = $rag->ask($question, $history);
        } catch (\Throwable $e) {
            $result = [
                'answer' => "Mohon maaf, terjadi kendala saat menyusun data analitik: {$e->getMessage()}",
                'sources' => ['Sistem Data Banjarnegara'],
                'follow_ups' => [
                    'Mengapa analisis di halaman Rekomendasi belum sempurna terkait data guru MI dan MTs?',
                    'Bagaimana perbandingan rasio guru per kecamatan jika siswa MI dan MTs dipisahkan?',
                ],
                'debug' => [
                    'engine' => 'error-fallback',
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toIso8601String(),
                ],
            ];
        }

        // Tulis log debug produksi
        try {
            $logDir = storage_path('logs');
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
            $line = sprintf(
                "[%s] IP: %s | Q: %s | Engine: %s (%s) | %d ms | AnsLen: %d\n",
                now()->toIso8601String(),
                $request->ip(),
                mb_substr(str_replace(["\r", "\n"], ' ', $question), 0, 80),
                $result['debug']['engine'] ?? 'unknown',
                $result['debug']['model'] ?? 'unknown',
                $result['debug']['duration_ms'] ?? 0,
                strlen($result['answer'] ?? '')
            );
            @file_put_contents($logDir . '/chatbot.log', $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable) {
            // silent catch untuk logging
        }

        return response()->json($result);
    }

    /**
     * Endpoint diagnostik kesehatan koneksi Chatbot di Production.
     */
    public function ping(): JsonResponse
    {
        $dbOk = false;
        try {
            DB::connection()->getPdo();
            $dbOk = true;
        } catch (\Throwable) {
            $dbOk = false;
        }

        $apiKey = (string) config('services.gemini.api_key', '');
        $hasKey = $apiKey !== '';

        $lastLogs = [];
        $logFile = storage_path('logs/chatbot.log');
        if (file_exists($logFile)) {
            $lines = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines)) {
                $lastLogs = array_slice($lines, -8);
            }
        }

        return response()->json([
            'status' => 'OK',
            'database_connected' => $dbOk,
            'gemini_api_key_set' => $hasKey,
            'preferred_model' => config('services.gemini.model', 'gemini-3.5-flash-lite'),
            'queue_connection' => config('queue.default', 'sync'),
            'last_interactions' => $lastLogs,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
