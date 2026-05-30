@extends('listen.layout')

@section('title', $episode->title)

@section('content')
    <header class="transmission-header">
        <h1 class="transmission-title">Episode_Detail</h1>
        <div class="transmission-meta">
            <span>{{ $episode->episode_key }}</span>
            <span>{{ $episode->language }}</span>
            <span>{{ $episode->character_name ?: $episode->character_key ?: 'No character' }}</span>
        </div>
    </header>

    <div class="stack">
        <section class="broadcast-panel">
            <div class="panel-header">
                <span>Now Playing</span>
                <span>{{ $episode->published_at?->format('Y.m.d') ?? $episode->recorded_at?->format('Y.m.d') ?? $episode->created_at?->format('Y.m.d') }}</span>
            </div>
            <div class="panel-body">
                <h2 class="episode-title">{{ $episode->title }}</h2>

                <div
                    class="player-frame listen-player"
                    data-listen-player
                    data-duration="{{ $episode->audio_duration_seconds === null ? '' : gmdate('i:s', $episode->audio_duration_seconds) }}"
                >
                    <button class="play-square" type="button" data-listen-play aria-label="Play episode">▷</button>
                    <div class="waveform" data-listen-waveform aria-hidden="true">
                        @for ($i = 0; $i < 32; $i++)
                            <span></span>
                        @endfor
                    </div>
                    <span class="duration" data-listen-duration>{{ $episode->audio_duration_seconds === null ? '--:--' : gmdate('i:s', $episode->audio_duration_seconds) }}</span>
                </div>

                <audio class="listen-audio" preload="metadata" data-listen-audio>
                    <source src="{{ route('listen.episodes.audio', $episode) }}" type="audio/mpeg">
                </audio>
                <noscript>
                    <audio class="audio-player" controls preload="metadata">
                        <source src="{{ route('listen.episodes.audio', $episode) }}" type="audio/mpeg">
                    </audio>
                </noscript>

                <div class="meta-row">
                    <span>Published: {{ $episode->published_at?->format('Y-m-d H:i') ?? 'N/A' }}</span>
                    <span>Recorded: {{ $episode->recorded_at?->format('Y-m-d H:i') ?? 'N/A' }}</span>
                    <span>Size: {{ $episode->audio_size_bytes === null ? 'N/A' : number_format($episode->audio_size_bytes) . ' bytes' }}</span>
                </div>

                <div class="actions" style="margin-top: 14px;">
                    <a class="button secondary" href="{{ route('listen.episodes.index') }}">Episodes</a>
                    <a class="button" href="{{ route('listen.episodes.download', $episode) }}">Download MP3</a>
                </div>
            </div>
        </section>

        <section class="broadcast-panel">
            <div class="panel-header">
                <span>Scenario Sections</span>
                <span>{{ $episode->sections->count() }} Blocks</span>
            </div>
            <div class="panel-body">
                <div class="section-list">
                    @forelse ($episode->sections as $section)
                        <article class="section-card">
                            <div class="section-kicker">
                                <span>{{ str_pad((string) $section->sort_order, 3, '0', STR_PAD_LEFT) }}</span>
                                <span>{{ $section->section_type }}</span>
                                @if ($section->estimated_duration_seconds !== null)
                                    <span>{{ $section->estimated_duration_seconds }} sec</span>
                                @endif
                            </div>
                            <h3>{{ $section->title }}</h3>
                            <p class="section-text">{{ $section->text }}</p>
                        </article>
                    @empty
                        <p class="section-text">No sections found.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="broadcast-panel">
            <div class="panel-header">
                <span>Protocol_Feed</span>
                <span>{{ $episode->topics->count() }} Topics</span>
            </div>
            <div class="panel-body">
                <div class="topic-list">
                    @forelse ($episode->topics as $topic)
                        <details class="topic-card" @if($loop->first) open @endif>
                            <summary>
                                <span class="topic-index">{{ str_pad((string) $topic->sort_order, 3, '0', STR_PAD_LEFT) }}</span>
                                <span class="topic-name">{{ $topic->title }}</span>
                                <span class="topic-toggle">⌄</span>
                            </summary>
                            <div class="topic-card-body">
                                <div class="topic-source">
                                    @if ($topic->source_name)
                                        {{ $topic->source_name }}
                                    @endif
                                    @if ($topic->topic_id)
                                        // {{ $topic->topic_id }}
                                    @endif
                                </div>
                                @if ($topic->summary)
                                    <p class="topic-text">{{ $topic->summary }}</p>
                                @endif
                                @if ($topic->why_it_matters)
                                    <p class="topic-text">{{ $topic->why_it_matters }}</p>
                                @endif
                                <div class="actions">
                                    @if ($topic->url)
                                        <a class="button secondary" href="{{ $topic->url }}" target="_blank" rel="noopener noreferrer">Source</a>
                                    @endif
                                    @if ($topic->discussion_url)
                                        <a class="button secondary" href="{{ $topic->discussion_url }}" target="_blank" rel="noopener noreferrer">Discussion</a>
                                    @endif
                                </div>
                            </div>
                        </details>
                    @empty
                        <p class="topic-text">No topics found.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endsection
