<?php

namespace App\Http\Controllers;

use App\Services\EducationRagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $result = $rag->ask($question, $history);

        return response()->json($result);
    }
}
