<?php

namespace Tests\Feature\Livewire\Listen;

use App\Livewire\Listen\TopicRatingControls;
use App\Models\Episode;
use App\Models\EpisodeTopic;
use App\Models\TopicRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * @internal
 */
class TopicRatingControlsTest extends TestCase
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

    public function testAuthenticatedUserCanRateTopicWithStar(): void
    {
        Http::fake(['*' => Http::response($this->ratingResponse(4))]);
        $user = $this->allowedUser();
        $topic = $this->topic();

        $component = $this->testComponent($user, $topic);
        $component->call('rate', 4);
        $component->assertSet('rating', 4);

        $this->assertDatabaseHas('topic_ratings', [
            'user_id' => $user->id,
            'topic_id' => $topic->topic_id,
            'rating' => 4,
        ]);
        Http::assertSent(static fn (Request $request): bool => $request->method() === 'PUT'
            && $request['rating'] === 4);
    }

    public function testAuthenticatedUserCanRateTopicAsBad(): void
    {
        Http::fake(['*' => Http::response($this->ratingResponse(-1))]);
        $user = $this->allowedUser();
        $topic = $this->topic();

        $component = $this->testComponent($user, $topic);
        $component->call('rate', -1);
        $component->assertSet('rating', -1);

        $this->assertDatabaseHas('topic_ratings', [
            'user_id' => $user->id,
            'topic_id' => $topic->topic_id,
            'rating' => -1,
        ]);
    }

    public function testClickingSameRatingClearsRating(): void
    {
        Http::fake(['*' => Http::response($this->clearResponse())]);
        $user = $this->allowedUser();
        $topic = $this->topic();
        TopicRating::factory()->create([
            'user_id' => $user->id,
            'topic_id' => $topic->topic_id,
            'latest_episode_topic_id' => $topic->id,
            'rating' => 3,
        ]);

        $component = $this->testComponent($user, $topic);
        $component->call('rate', 3);
        $component->assertSet('rating', null);

        $this->assertDatabaseMissing('topic_ratings', [
            'user_id' => $user->id,
            'topic_id' => $topic->topic_id,
        ]);
        Http::assertSent(static fn (Request $request): bool => $request->method() === 'DELETE');
    }

    public function testClearDeletesLocalRatingAfterUpstreamSuccess(): void
    {
        Http::fake(['*' => Http::response($this->clearResponse())]);
        $user = $this->allowedUser();
        $topic = $this->topic();
        TopicRating::factory()->create([
            'user_id' => $user->id,
            'topic_id' => $topic->topic_id,
            'latest_episode_topic_id' => $topic->id,
            'rating' => 5,
        ]);

        $component = $this->testComponent($user, $topic);
        $component->call('clear');
        $component->assertSet('rating', null);

        $this->assertDatabaseMissing('topic_ratings', [
            'user_id' => $user->id,
            'topic_id' => $topic->topic_id,
        ]);
    }

    public function testGuestCannotRate(): void
    {
        $component = app(TopicRatingControls::class);
        $component->topic = $this->topic();

        $this->expectException(HttpException::class);

        $component->rate(1);
    }

    public function testTopicWithoutTopicIdCannotBeRated(): void
    {
        $this->actingAs($this->allowedUser());
        $component = app(TopicRatingControls::class);
        $component->topic = $this->topic(['topic_id' => null]);

        $this->expectException(HttpException::class);

        $component->rate(1);
    }

    public function testRadiopipeFailureShowsErrorAndKeepsPreviousRating(): void
    {
        Http::fake(['*' => Http::response(['message' => 'failed'], 500)]);
        $user = $this->allowedUser();
        $topic = $this->topic();
        TopicRating::factory()->create([
            'user_id' => $user->id,
            'topic_id' => $topic->topic_id,
            'latest_episode_topic_id' => $topic->id,
            'rating' => 2,
        ]);

        $component = $this->testComponent($user, $topic);
        $component->call('rate', 5);
        $component->assertSet('rating', 2);
        $component->assertSet('errorMessage', 'SYNC_FAILED');

        $this->assertDatabaseHas('topic_ratings', [
            'user_id' => $user->id,
            'topic_id' => $topic->topic_id,
            'rating' => 2,
        ]);
    }

    public function testSelectedRatingIsRendered(): void
    {
        $user = $this->allowedUser();
        $topic = $this->topic();
        TopicRating::factory()->create([
            'user_id' => $user->id,
            'topic_id' => $topic->topic_id,
            'latest_episode_topic_id' => $topic->id,
            'rating' => 4,
        ]);

        $component = $this->testComponent($user, $topic);
        $component->assertSee('SIGNAL_RATING');
        $component->assertSee('SYNCED');
        $component->assertSee('CLEAR_SIGNAL');
        $component->assertSee('is-selected', false);
    }

    public function testTopicWithoutTopicIdRendersDisabledLabel(): void
    {
        $component = $this->testComponent($this->allowedUser(), $this->topic(['topic_id' => null]));

        $component->assertSee('NO_TOPIC_ID');
    }

    private function allowedUser(): User
    {
        $email = 'listener-' . Str::uuid() . '@example.test';
        config(['playpipe.admin.allowed_emails' => [$email]]);

        return User::factory()->create(['email' => $email]);
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
     * @return Testable<TopicRatingControls>
     */
    private function testComponent(User $user, EpisodeTopic $topic): Testable
    {
        Livewire::actingAs($user);

        return Livewire::test(TopicRatingControls::class, ['topic' => $topic]);
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
