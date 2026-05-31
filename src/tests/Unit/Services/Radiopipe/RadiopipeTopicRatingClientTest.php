<?php

namespace Tests\Unit\Services\Radiopipe;

use App\Exceptions\RadiopipeTopicRatingException;
use App\Services\Radiopipe\RadiopipeTopicRatingClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * @internal
 */
class RadiopipeTopicRatingClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'playpipe.radiopipe.base_url' => 'https://radiopipe.example.test/',
            'playpipe.radiopipe.token' => 'radiopipe-secret-token',
            'playpipe.radiopipe.request_timeout' => 5,
            'playpipe.radiopipe.max_retries' => 0,
        ]);
    }

    public function testRatingMinusOneIsAccepted(): void
    {
        Http::fake(['*' => Http::response($this->ratingResponse(-1))]);

        $response = app(RadiopipeTopicRatingClient::class)->rate('upstream:236', -1);

        self::assertSame(-1, data_get($response, 'topic_rating.rating'));
    }

    public function testRatingsOneThroughFiveAreAccepted(): void
    {
        Http::fake(['*' => Http::response($this->ratingResponse(1))]);
        $client = app(RadiopipeTopicRatingClient::class);

        foreach ([1, 2, 3, 4, 5] as $rating) {
            $client->rate('upstream:' . $rating, $rating);
        }

        Http::assertSentCount(5);
    }

    public function testRatingZeroIsRejected(): void
    {
        $this->expectException(RadiopipeTopicRatingException::class);

        app(RadiopipeTopicRatingClient::class)->rate('upstream:236', 0);
    }

    public function testRatingSixIsRejected(): void
    {
        $this->expectException(RadiopipeTopicRatingException::class);

        app(RadiopipeTopicRatingClient::class)->rate('upstream:236', 6);
    }

    public function testEmptyTopicIdIsRejected(): void
    {
        $this->expectException(RadiopipeTopicRatingException::class);

        app(RadiopipeTopicRatingClient::class)->rate('', 1);
    }

    public function testPutRequestUsesEncodedTopicIdAndAuthorizationBearerToken(): void
    {
        Http::fake(['*' => Http::response($this->ratingResponse(1))]);

        app(RadiopipeTopicRatingClient::class)->rate('upstream:236', 1);

        Http::assertSent(static fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === 'https://radiopipe.example.test/api/topics/upstream%3A236/rating'
            && $request->hasHeader('Authorization', 'Bearer radiopipe-secret-token')
            && $request->hasHeader('Accept', 'application/json')
            && $request['rating'] === 1);
    }

    public function testClearRequestUsesDelete(): void
    {
        Http::fake(['*' => Http::response($this->clearResponse())]);

        app(RadiopipeTopicRatingClient::class)->clear('upstream:236');

        Http::assertSent(static fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'https://radiopipe.example.test/api/topics/upstream%3A236/rating'
            && $request->hasHeader('Authorization', 'Bearer radiopipe-secret-token'));
    }

    public function testTokenIsNotExposedInExceptionMessage(): void
    {
        Http::fake(['*' => Http::response(['message' => 'server failed'], 500)]);

        try {
            app(RadiopipeTopicRatingClient::class)->rate('upstream:236', 1);
            self::fail('Exception was not thrown.');
        } catch (RadiopipeTopicRatingException $exception) {
            self::assertStringNotContainsString('radiopipe-secret-token', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function ratingResponse(int $rating): array
    {
        return [
            'topic_rating' => [
                'topic_id' => 'upstream:236',
                'upstream' => [
                    'provider' => 'digestpipe',
                    'id' => 236,
                ],
                'rating' => $rating,
                'rated_at' => '2026-05-31T10:15:00+09:00',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function clearResponse(): array
    {
        return [
            'topic_rating' => [
                'topic_id' => 'upstream:236',
                'upstream' => [
                    'provider' => 'digestpipe',
                    'id' => 236,
                ],
                'rating' => null,
                'rated_at' => null,
            ],
        ];
    }
}
