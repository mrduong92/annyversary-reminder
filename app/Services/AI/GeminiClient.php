<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient
{
    private string $apiKey;
    private string $model;
    private string $embeddingModel;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
        $this->model = config('services.gemini.model', 'gemini-2.0-flash-lite');
        $this->embeddingModel = config('services.gemini.embedding_model', 'text-embedding-004');
    }

    public function generateContent(array $messages, array $tools = [], string $systemPrompt = ''): array
    {
        $payload = ['contents' => $messages];

        if ($systemPrompt) {
            $payload['system_instruction'] = ['parts' => [['text' => $systemPrompt]]];
        }

        if (!empty($tools)) {
            $payload['tools'] = [['function_declarations' => $tools]];
        }

        $response = Http::timeout(30)
            ->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", $payload);

        if (!$response->successful()) {
            Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('Gemini API error: ' . $response->body());
        }

        return $response->json();
    }

    public function embed(string $text): array
    {
        $response = Http::timeout(15)
            ->post("{$this->baseUrl}/models/{$this->embeddingModel}:embedContent?key={$this->apiKey}", [
                'model' => "models/{$this->embeddingModel}",
                'content' => ['parts' => [['text' => $text]]],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Gemini embedding error: ' . $response->body());
        }

        return $response->json('embedding.values', []);
    }

    public function streamGenerateContent(array $messages, string $systemPrompt = ''): \Generator
    {
        $payload = ['contents' => $messages];

        if ($systemPrompt) {
            $payload['system_instruction'] = ['parts' => [['text' => $systemPrompt]]];
        }

        $response = Http::timeout(60)
            ->withOptions(['stream' => true])
            ->post("{$this->baseUrl}/models/{$this->model}:streamGenerateContent?alt=sse&key={$this->apiKey}", $payload);

        $body = $response->getBody();
        $buffer = '';

        while (!$body->eof()) {
            $chunk = $body->read(1024);
            $buffer .= $chunk;

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);

                if (str_starts_with($line, 'data: ')) {
                    $json = substr($line, 6);
                    $data = json_decode($json, true);

                    if ($text = data_get($data, 'candidates.0.content.parts.0.text')) {
                        yield $text;
                    }
                }
            }
        }
    }
}
