<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\EpisodeResource;
use App\Filament\Resources\EpisodeResource\Pages\ListEpisodes;
use App\Filament\Resources\EpisodeResource\Pages\ViewEpisode;
use App\Models\Episode;
use App\Models\EpisodeSection;
use App\Models\EpisodeTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @internal
 */
class EpisodeResourceTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthorizedAdminCanAccessEpisodeResource(): void
    {
        $this->actingAsAdmin();
        $episode = $this->episodeWithContent();

        $this->get(EpisodeResource::getUrl('index'))->assertOk();

        $component = Livewire::test(ListEpisodes::class);
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->assertCanSeeTableRecords([$episode]);
        $component->assertSee('サンプルエピソード');
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->assertTableActionExists('playback', record: $episode);
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->assertTableActionExists('download', record: $episode);
    }

    public function testAuthorizedAdminCanViewEpisodeResourceDetail(): void
    {
        $this->actingAsAdmin();
        $episode = $this->episodeWithContent();

        $this->get(EpisodeResource::getUrl('view', ['record' => $episode]))
            ->assertOk()
            ->assertSee('サンプルエピソード');

        $component = Livewire::test(ViewEpisode::class, ['record' => $episode->getRouteKey()]);
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->assertActionExists('playback');
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->assertActionExists('download');
    }

    public function testEpisodeResourceIsReadOnly(): void
    {
        $episode = $this->episodeWithContent();

        self::assertFalse(EpisodeResource::canCreate());
        self::assertFalse(EpisodeResource::canEdit($episode));
        self::assertFalse(EpisodeResource::canDelete($episode));
        self::assertFalse(EpisodeResource::canDeleteAny());
    }

    private function actingAsAdmin(): void
    {
        config(['playpipe.admin.allowed_emails' => ['admin@example.test']]);

        $this->actingAs(User::factory()->create(['email' => 'admin@example.test']));
    }

    private function episodeWithContent(): Episode
    {
        /** @var Episode $episode */
        $episode = Episode::factory()->create([
            'episode_key' => 'episode-admin',
            'title' => 'サンプルエピソード',
            'audio_path' => 'episodes/episode-admin/audio.mp3',
        ]);

        EpisodeSection::factory()->create(['episode_id' => $episode->id]);
        EpisodeTopic::factory()->create(['episode_id' => $episode->id]);

        return $episode;
    }
}
