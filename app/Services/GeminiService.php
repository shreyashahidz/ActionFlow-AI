<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    public function analyze(array $payload): array
    {
        $key   = config('services.gemini.key');
        $model = config('services.gemini.model');
        if (!$key) {
            throw new \RuntimeException('GEMINI_API_KEY is not set in .env');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $attempts = 0;
        $lastErr = '';
        while ($attempts < 3) {
            $attempts++;
            $res = Http::withHeaders(['x-goog-api-key' => $key])
                ->timeout(20)->acceptJson()->post($url, $payload);

            if ($res->successful()) {
                $text = data_get($res->json(), 'candidates.0.content.parts.0.text');
                $data = is_string($text) ? json_decode($text, true) : null;
                if (is_array($data)) {
                    return $data;
                }
                $lastErr = 'Gemini returned empty or non-JSON output';
            } elseif (in_array($res->status(), [429, 500, 503])) {
                $lastErr = "Gemini HTTP {$res->status()} (rate limit/overload)";
                if ($attempts < 3) {
                    sleep(2);
                    continue;
                }
            } else {
                throw new \RuntimeException('Gemini error ' . $res->status() . ': ' .
                    data_get($res->json(), 'error.message', $res->body()));
            }
        }
        throw new \RuntimeException($lastErr ?: 'Gemini failed after retries');
    }

    public function generateBriefing(array $openTasks): string
    {
        $key   = config('services.gemini.key');
        $model = config('services.gemini.model');
        if (!$key) {
            throw new \RuntimeException('GEMINI_API_KEY is not set in .env');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
        $taskList = empty($openTasks) ? 'No pending tasks at the moment.' : json_encode($openTasks, JSON_PRETTY_PRINT);

        $prompt = <<<TXT
You are ActionFlow AI assistant. Given this list of open tasks and priorities, provide a crisp 3-5 bullet executive daily briefing. Highlight urgent items, deadlines, and key focuses. Be encouraging and concise.

Open Tasks:
{$taskList}
TXT;

        $payload = [
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.4,
            ],
        ];

        $res = Http::withHeaders(['x-goog-api-key' => $key])
            ->timeout(20)->acceptJson()->post($url, $payload);

        if ($res->successful()) {
            return (string)data_get($res->json(), 'candidates.0.content.parts.0.text', 'No summary generated.');
        }

        throw new \RuntimeException('Failed to generate daily summary: ' . data_get($res->json(), 'error.message', $res->body()));
    }
}
