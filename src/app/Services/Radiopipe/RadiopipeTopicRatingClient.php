<?php

namespace App\Services\Radiopipe;

use App\Exceptions\RadiopipeTopicRatingException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RadiopipeTopicRatingClient
{
    /**
     * @return array<string, mixed>
     */
    public function rate(string $topicId, int $rating): array
    {
        $this->validateTopicId($topicId);
        $this->validateRating($rating);

        return $this->send('put', $topicId, ['rating' => $rating], $rating);
    }

    /**
     * @return array<string, mixed>
     */
    public function clear(string $topicId): array
    {
        $this->validateTopicId($topicId);

        return $this->send('delete', $topicId);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function send(string $method, string $topicId, array $payload = [], ?int $rating = null): array
    {
        $url = $this->url($topicId);

        try {
            $response = match ($method) {
                'put' => $this->request()->put($url, $payload),
                'delete' => $this->request()->delete($url),
                default => throw new RadiopipeTopicRatingException('Unsupported radiopipe topic rating request.'),
            };
        } catch (ConnectionException $exception) {
            Log::warning('Radiopipe topic rating request failed.', [
                'topic_id' => $topicId,
                'rating' => $rating,
                'error' => Str::limit($exception->getMessage(), 160),
            ]);

            throw new RadiopipeTopicRatingException('Radiopipe topic rating request failed.');
        }

        if (! $response->successful()) {
            Log::warning('Radiopipe topic rating request failed.', [
                'topic_id' => $topicId,
                'rating' => $rating,
                'status' => $response->status(),
                'response' => $this->safeBodySummary($response->body()),
            ]);

            throw new RadiopipeTopicRatingException('Radiopipe topic rating request failed.', $response->status());
        }

        $json = $response->json();

        if (! is_array($json) || array_is_list($json)) {
            return [];
        }

        /** @var array<string, mixed> $json */
        return $json;
    }

    private function request(): PendingRequest
    {
        $token = $this->configuredToken();
        $request = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout($this->requestTimeout());

        $maxRetries = $this->maxRetries();

        if ($maxRetries > 0) {
            return $request->retry($maxRetries, 200, throw: false);
        }

        return $request;
    }

    private function url(string $topicId): string
    {
        return rtrim($this->configuredBaseUrl(), '/') . '/api/topics/' . rawurlencode($topicId) . '/rating';
    }

    private function configuredBaseUrl(): string
    {
        $baseUrl = config('playpipe.radiopipe.base_url');

        if (! is_string($baseUrl) || trim($baseUrl) === '') {
            throw new RadiopipeTopicRatingException('Radiopipe API URL is not configured.');
        }

        return trim($baseUrl);
    }

    private function configuredToken(): string
    {
        $token = config('playpipe.radiopipe.token');

        if (! is_string($token) || trim($token) === '') {
            throw new RadiopipeTopicRatingException('Radiopipe API token is not configured.');
        }

        return trim($token);
    }

    private function requestTimeout(): int
    {
        $timeout = config('playpipe.radiopipe.request_timeout', 10);

        return max(1, is_numeric($timeout) ? (int) $timeout : 10);
    }

    private function maxRetries(): int
    {
        $maxRetries = config('playpipe.radiopipe.max_retries', 2);

        return max(0, is_numeric($maxRetries) ? (int) $maxRetries : 2);
    }

    private function validateTopicId(string $topicId): void
    {
        if (trim($topicId) === '') {
            throw new RadiopipeTopicRatingException('Topic id is required for rating sync.');
        }
    }

    private function validateRating(int $rating): void
    {
        if (! in_array($rating, [-1, 1, 2, 3, 4, 5], true)) {
            throw new RadiopipeTopicRatingException('Topic rating must be -1 or 1..5.');
        }
    }

    private function safeBodySummary(string $body): string
    {
        $token = config('playpipe.radiopipe.token');

        if (is_string($token) && trim($token) !== '') {
            $body = str_replace(trim($token), '[redacted]', $body);
        }

        return Str::limit($body, 300);
    }
}
