<?php

namespace Tests\Unit\Services\Listen;

use App\Exceptions\RadiopipeTopicRatingException;
use App\Models\Episode;
use App\Models\EpisodeTopic;
use App\Models\TopicRating;
use App\Models\User;
use App\Services\Listen\TopicRatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * @internal
 */
class TopicRatingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'playpipe.radiopipe.base_url' => 'https://radiopipe.example.test',
            'playpipe.radiopipe.token' => 'radiopipe-secret-token',
            'playpipe.radiopipe.max_retries' => 0,
        ]);
    }

    public function testPutSuccessStoresLocalTopicRating(): void
    {
        Http::fake(['*' => Http::response($this->ratingResponse(5))]);
        $user = User::factory()->create();
        $topic = $this->topic();

        $rating = app(TopicRatingService::class)->rate($user, $topic, 5);

        self::assertSame(5, $rating->rating);
        self::assertSame($topic->topic_id, $rating->topic_id);
        self::assertSame($topic->id, $rating->latest_episode_topic_id);
        self::assertNotNull($rating->rated_at);
        self::assertNotNull($rating->synced_at);
        self::assertNull($rating->last_sync_error);
        $this->assertDatabaseHas('topic_ratings', [
            'user_id' => $user->id,
            'topic_id' => $topic->topic_id,
            'rating' => 5,
        ]);
    }

    public function testDeleteSuccessRemovesLocalTopicRating(): void
    {
        Http::fake(['*' => Http::response($this->clearResponse())]);
        $user = User::factory()->create();
        $topic = $this->topic();
        TopicRating::factory()->create([
            'user_id' => $user->id,
            'topic_id' => $topic->topic_id,
            'latest_episode_topic_id' => $topic->id,
            'rating' => 3,
        ]);

        app(TopicRatingService::class)->clear($user, $topic);

        $this->assertDatabaseMissing('topic_ratings', [
            'user_id' => $user->id,
            'topic_id' => $topic->topic_id,
        ]);
    }

    public function testRadiopipeFailureDoesNotChangeLocalRating(): void
    {
        Http::fake(['*' => Http::response(['message' => 'upstream failed'], 500)]);
        $user = User::factory()->create();
        $topic = $this->topic();
        TopicRating::factory()->create([
            'user_id' => $user->id,
            'topic_id' => $topic->topic_id,
            'latest_episode_topic_id' => $topic->id,
            'rating' => 2,
        ]);

        try {
            app(TopicRatingService::class)->rate($user, $topic, 5);
            self::fail('Exception was not thrown.');
        } catch (RadiopipeTopicRatingException) {
            $this->assertDatabaseHas('topic_ratings', [
                'user_id' => $user->id,
                'topic_id' => $topic->topic_id,
                'rating' => 2,
            ]);
        }
    }

    public function testEmptyTopicIdIsRejected(): void
    {
        $this->expectException(RadiopipeTopicRatingException::class);

        app(TopicRatingService::class)->rate(User::factory()->create(), $this->topic(['topic_id' => null]), 1);
    }

    public function testInvalidRatingIsRejected(): void
    {
        $this->expectException(RadiopipeTopicRatingException::class);

        app(TopicRatingService::class)->rate(User::factory()->create(), $this->topic(), 0);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function topic(array $overrides = []): EpisodeTopic
    {
        $episode = Episode::factory()->create(['status' => Episode::STATUS_AVAILABLE]);

        return EpisodeTopic::factory()->create(array_merge([
            'episode_id' => $episode->id,
            'topic_id' => 'upstream:236',
        ], $overrides));
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
