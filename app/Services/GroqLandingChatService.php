<?php

namespace App\Services;

use App\Models\TrainingProgram;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GroqLandingChatService
{
    public const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    public const MAX_HISTORY = 8;

    public const MAX_MESSAGE_LENGTH = 500;

    public const MAX_COMPLETION_TOKENS = 1024;

    public const OFF_TOPIC_REPLY = 'That is not related to this website. I can only help with questions about MCARE Hub, such as admissions, training programs, fees, enrollment, payments, and alumni claims.';

    public function configured(): bool
    {
        return filled(config('services.groq.key'));
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{ok: bool, reply: string, status: ?int}
     */
    public function reply(string $message, array $history = []): array
    {
        if (! $this->configured()) {
            return [
                'ok' => false,
                'reply' => 'The MCARE assistant is unavailable right now.',
                'status' => 503,
            ];
        }

        $messages = array_merge(
            [
                ['role' => 'system', 'content' => $this->systemPrompt()],
            ],
            $this->sanitizeHistory($history),
            [
                ['role' => 'user', 'content' => $this->sanitizeText($message)],
            ],
        );

        $lastStatus = null;

        foreach ($this->modelsToTry() as $model) {
            try {
                $response = Http::acceptJson()
                    ->withToken((string) config('services.groq.key'))
                    ->connectTimeout(8)
                    ->timeout(max(8, (int) config('services.groq.timeout', 30)))
                    ->post(self::ENDPOINT, $this->completionPayload($model, $messages));
            } catch (ConnectionException $exception) {
                Log::warning('Groq landing chat could not be reached.', [
                    'error' => $exception->getMessage(),
                ]);

                return [
                    'ok' => false,
                    'reply' => 'The MCARE assistant could not be reached. Please try again in a moment.',
                    'status' => null,
                ];
            } catch (Throwable $exception) {
                report($exception);

                return [
                    'ok' => false,
                    'reply' => 'The MCARE assistant could not reply. Please try again later.',
                    'status' => null,
                ];
            }

            $lastStatus = $response->status();
            $reply = $this->sanitizeReply(trim((string) $response->json('choices.0.message.content')));

            if ($response->successful() && $reply !== '') {
                return [
                    'ok' => true,
                    'reply' => $reply,
                    'status' => $response->status(),
                ];
            }

            Log::warning('Groq landing chat was rejected.', [
                'status' => $response->status(),
                'model' => $model,
                'finish_reason' => $response->json('choices.0.finish_reason'),
                'error' => $response->json('error.code') ?: $response->json('error.type'),
            ]);

            $shouldTryNext = in_array($response->status(), [400, 404], true)
                || ($response->successful() && $reply === '');

            if (! $shouldTryNext) {
                break;
            }
        }

        return [
            'ok' => false,
            'reply' => 'The MCARE assistant could not reply. Please try again later.',
            'status' => $lastStatus,
        ];
    }

    /**
     * @param  list<array{role?: mixed, content?: mixed}>  $history
     * @return list<array{role: string, content: string}>
     */
    public function sanitizeHistory(array $history): array
    {
        $clean = [];

        foreach (array_slice($history, -self::MAX_HISTORY) as $turn) {
            $role = is_array($turn) ? (string) ($turn['role'] ?? '') : '';
            $content = is_array($turn) ? $this->sanitizeText((string) ($turn['content'] ?? '')) : '';

            if (! in_array($role, ['user', 'assistant'], true) || $content === '') {
                continue;
            }

            $clean[] = [
                'role' => $role,
                'content' => $content,
            ];
        }

        return $clean;
    }

    public function sanitizeText(string $value): string
    {
        $value = trim(strip_tags($value));

        if ($value === '') {
            return '';
        }

        return mb_substr($value, 0, self::MAX_MESSAGE_LENGTH);
    }

    public function sanitizeReply(string $value): string
    {
        $value = trim(strip_tags($value));

        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\[([^\]]+)\]\((?:https?:\/\/|www\.)[^)\s]+\)/i', '$1', $value) ?? $value;
        $value = preg_replace('/\*{1,2}([^*]+)\*{1,2}/', '$1', $value) ?? $value;
        $value = preg_replace('~\bhttps?://[^\s<>"\']+~i', '', $value) ?? $value;
        $value = preg_replace('~\bwww\.[^\s<>"\']+~i', '', $value) ?? $value;
        $value = preg_replace('~\blocalhost(?::\d+)?(?:/[^\s<>"\']*)?~i', '', $value) ?? $value;
        $value = preg_replace('~\s/(?:applications|alumni|login|enrollment)[^\s<>"\']*~i', '', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+([,.;:!?])/u', '$1', $value) ?? $value;

        return trim($value);
    }

    /** @return list<string> */
    private function modelsToTry(): array
    {
        $preferred = trim((string) config('services.groq.model', 'llama-3.3-70b-versatile'));

        return array_values(array_unique(array_filter([
            $preferred,
            'openai/gpt-oss-20b',
            'llama-3.3-70b-versatile',
        ])));
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @return array<string, mixed>
     */
    private function completionPayload(string $model, array $messages): array
    {
        $payload = [
            'model' => $model,
            'temperature' => 0.3,
            'max_completion_tokens' => self::MAX_COMPLETION_TOKENS,
            'messages' => $messages,
        ];

        if (str_contains(strtolower($model), 'gpt-oss')) {
            $payload['reasoning_effort'] = 'low';
        }

        return $payload;
    }

    private function systemPrompt(): string
    {
        $organization = (string) config('official_documents.organization.name', 'Mission Care Training and Assessment Center');
        $address = (string) config('official_documents.organization.address', 'San Isidro Poblacion, Pili, Camarines Sur');
        $phone = (string) config('official_documents.organization.phone', '');
        $hours = (string) config('official_documents.organization.course_hours', 786);

        return implode("\n", [
            'You are the MCARE Assistant for the MCARE Hub website only.',
            "Center: {$organization}. Address: {$address}.".($phone !== '' ? " Phone: {$phone}." : ''),
            'MCARE is a TESDA-accredited Caregiving NC II training and assessment center.',
            "Typical Caregiving NC II training hours published by MCARE: {$hours}.",
            'Always reply in the same language as the latest user message, including Filipino/Tagalog, Bikol, English, or any other language they write in. An English question must get an English answer.',
            'The visitor is already on this MCARE Hub website. Explain what to click and what to fill in. Never output URLs, links, website paths, localhost, ports, http, or https.',
            'Write 2-4 short plain sentences. Do not use markdown, asterisks, or bullet lists.',
            'If the user only greets you (hello, hi, kamusta, maayong buntag, and similar), greet them briefly in the same language and invite one question about this MCARE Hub website. Do not refuse a greeting as off-topic.',
            'Answer only questions about this website and MCARE services: admissions, catalog programs, fees, enrollment after approval, payments, sign in, alumni claims, location, and contact details.',
            'How to apply: click Apply now (or Apply for this program on a catalog card). Complete Submit a training application with name, Gmail, contact number, education, and privacy consent, then submit. Keep the application number from the confirmation page or email.',
            'How to check status: click Check status and enter the application number.',
            'How to enroll: after the application is approved, open Enroll, enter that approved application number, then complete the TESDA enrollment form. Application is not the same as enrollment.',
            'Alumni records: click Alumni claim in the header. Sign in: click Sign in.',
            'If the user asks about anything else (weather, recipes, news, homework, other schools, general trivia, or unrelated chat), refuse in the user\'s language. The English meaning must be: '.self::OFF_TOPIC_REPLY,
            'Prefer official MCARE steps over guesses.',
            'Do not invent batch schedules, scholarship awards, assessment dates, or medical advice.',
            'Never ask for passwords, one-time codes, payment card numbers, or ID images in chat.',
            'If a fee or schedule is not in the catalog, say the visitor should apply or contact the center.',
            'Public program catalog:',
            $this->catalogContext(),
        ]);
    }

    private function catalogContext(): string
    {
        $programs = TrainingProgram::query()
            ->active()
            ->orderBy('name')
            ->get(['name', 'code', 'description', 'total_program_fee', 'downpayment_amount']);

        if ($programs->isEmpty()) {
            return 'No public programs are published right now.';
        }

        return $programs
            ->map(function (TrainingProgram $program): string {
                $description = trim((string) $program->description);

                return sprintf(
                    '- %s (%s): %s Total fee PHP %s. Required downpayment PHP %s.',
                    $program->name,
                    $program->code,
                    $description !== '' ? $description : 'No public description.',
                    number_format((float) $program->total_program_fee, 2),
                    number_format((float) $program->downpayment_amount, 2),
                );
            })
            ->implode("\n");
    }
}
