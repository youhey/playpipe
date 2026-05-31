@extends('listen.layout')

@section('title', 'Episodes')

@section('content')
    @php
        $featuredEpisode = $episodes->getCollection()->first();
    @endphp

    <header class="transmission-header">
        <h1 class="transmission-title">Transmission_Log</h1>
        <div class="transmission-meta">
            <span>Encrypted Feed</span>
            <span>Latency: 12ms</span>
            <span>{{ $episodes->total() }} Available</span>
        </div>
    </header>

    <div class="signal-strip">
        <span>Frequency: 99.7 MHz [Live]</span>
        <div class="signal-bars" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>

    <form class="filter-row" method="GET" action="{{ route('listen.episodes.index') }}">
        <label class="sr-only" for="episode-search">Search title or episode key</label>
        <input
            id="episode-search"
            class="input"
            type="search"
            name="q"
            value="{{ $filters['q'] ?? '' }}"
            placeholder="Search title or episode key"
        >
        <label class="sr-only" for="episode-character">Character</label>
        <select id="episode-character" class="select" name="character">
            <option value="">All characters</option>
            @foreach ($characters as $character)
                <option
                    value="{{ $character->character_key }}"
                    @selected(($filters['character'] ?? '') === $character->character_key)
                >
                    {{ $character->character_name ?: $character->character_key }}
                </option>
            @endforeach
        </select>
        <button class="filter-button" type="submit">Scan</button>
    </form>

    <div class="episode-list">
        @forelse ($episodes as $episode)
            @php
                $playback = $episode->playbacks->first();
                $playbackStatus = $playback?->status ?? 'unplayed';
                $playbackLabel = match ($playbackStatus) {
                    'completed' => 'COMPLETED',
                    'in_progress' => 'IN_PROGRESS',
                    default => 'UNPLAYED',
                };
                $resumeSeconds = $playbackStatus === 'in_progress' ? max(0, (int) ($playback?->last_position_seconds ?? 0)) : 0;
            @endphp
            <article class="broadcast-panel protocol-card">
                <div class="panel-header">
                    <span>EP_{{ str_pad((string) ($loop->iteration + (($episodes->currentPage() - 1) * $episodes->perPage())), 3, '0', STR_PAD_LEFT) }} // {{ $featuredEpisode && $featuredEpisode->is($episode) ? 'Current' : 'Archived' }}</span>
                    <span>{{ $episode->published_at?->format('Y.m.d') ?? $episode->recorded_at?->format('Y.m.d') ?? $episode->created_at?->format('Y.m.d') }}</span>
                </div>
                <div class="panel-body">
                    <h2 class="episode-title">
                        <a href="{{ route('listen.episodes.show', $episode) }}">{{ $episode->title }}</a>
                    </h2>

                    <div class="protocol-grid">
                        <div>
                            <span>Published</span>
                            <strong>{{ $episode->published_at?->format('Y.m.d H:i') ?? 'N/A' }}</strong>
                        </div>
                        <div>
                            <span>Recorded</span>
                            <strong>{{ $episode->recorded_at?->format('Y.m.d H:i') ?? 'N/A' }}</strong>
                        </div>
                        <div>
                            <span>Duration</span>
                            <strong>{{ $episode->audio_duration_seconds === null ? '--:--' : gmdate('i:s', $episode->audio_duration_seconds) }}</strong>
                        </div>
                        <div>
                            <span>Size</span>
                            <strong>{{ $episode->audio_size_bytes === null ? 'N/A' : number_format($episode->audio_size_bytes) . ' bytes' }}</strong>
                        </div>
                        <div>
                            <span>Topics</span>
                            <strong>{{ $episode->topics_count ?? 0 }}</strong>
                        </div>
                        <div>
                            <span>Status</span>
                            <strong>{{ $featuredEpisode && $featuredEpisode->is($episode) ? 'Current' : 'Archived' }}</strong>
                        </div>
                        <div>
                            <span>Playback</span>
                            <strong>{{ $playbackLabel }}</strong>
                        </div>
                    </div>

                    <div class="meta-row">
                        <span>{{ $episode->character_name ?: $episode->character_key ?: 'No character' }}</span>
                        <span>{{ $episode->language }}</span>
                        <span class="playback-badge is-{{ str_replace('_', '-', $playbackStatus) }}" data-playback-badge>{{ $playbackLabel }}</span>
                        @if ($resumeSeconds >= 5)
                            <span class="resume-hint">RESUME {{ gmdate('i:s', $resumeSeconds) }}</span>
                        @endif
                    </div>
                    <div class="episode-key">{{ $episode->episode_key }}</div>
                    <div class="actions" style="margin-top: 14px;">
                        <a class="button" href="{{ route('listen.episodes.show', $episode) }}">Open Protocol</a>
                    </div>
                </div>
            </article>
        @empty
            <div class="empty-panel">
                No episodes found.
            </div>
        @endforelse
    </div>

    <div class="pagination">
        {{ $episodes->onEachSide(1)->links('listen.pagination') }}
    </div>
@endsection
