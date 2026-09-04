<?php

namespace App\Http\Controllers;

use App\Services\GroqLandingChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingChatController extends Controller
{
    public function store(Request $request, GroqLandingChatService $chat): JsonResponse
    {
        if (! $chat->configured()) {
            return response()->json([
                'message' => 'The MCARE assistant is unavailable right now.',
            ], 503);
        }

        $request->merge([
            'message' => $chat->sanitizeText((string) $request->input('message')),
        ]);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:'.GroqLandingChatService::MAX_MESSAGE_LENGTH],
            'history' => ['sometimes', 'array', 'max:'.GroqLandingChatService::MAX_HISTORY],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:'.GroqLandingChatService::MAX_MESSAGE_LENGTH],
        ]);

        $result = $chat->reply(
            $validated['message'],
            is_array($validated['history'] ?? null) ? $validated['history'] : [],
        );

        if (! $result['ok']) {
            $status = $result['status'] === 429 ? 429 : 502;

            return response()->json([
                'message' => $result['reply'],
            ], $status);
        }

        return response()->json([
            'reply' => $result['reply'],
        ]);
    }
}
