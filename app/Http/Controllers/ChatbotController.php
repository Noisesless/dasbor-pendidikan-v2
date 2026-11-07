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
            'question' => ['required', 'string', 'max:600'],
            'mode' => ['nullable', 'string', 'in:auto,online,offline'],
            'history' => ['nullable', 'array', 'max:8'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,model'],
            'history.*.text' => ['required_with:history', 'string', 'max:1200'],
        ]);

        $result = $rag->ask(
            (string) $validated['question'],
            (string) ($validated['mode'] ?? 'auto'),
            (array) ($validated['history'] ?? [])
        );

        return response()->json($result);
    }
}
