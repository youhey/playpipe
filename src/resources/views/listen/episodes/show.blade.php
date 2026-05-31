@extends('listen.layout')

@section('title', $episode->title)

@section('content')
    @php
        $playback = $episode->playbacks->first();
        $playbackStatus = $playback?->status ?? 'unplayed';
        $playbackLabel = match ($playbackStatus) {
            'completed' => 'COMPLETED',
            'in_progress' => 'IN_PROGRESS',
            default => 'UNPLAYED',
        };
        $knownDurationSeconds = $playback?->duration_seconds ?? $episode->audio_duration_seconds;
        $lastPositionSeconds = max(0, (int) ($playback?->last_position_seconds ?? 0));
        $resumeSeconds = 0;

        if ($playbackStatus === 'in_progress' && $lastPositionSeconds >= 5) {
            $resumeSeconds = $knownDurationSeconds !== null && $lastPositionSeconds >= max(0, $knownDurationSeconds - 10)
                ? 0
                : $lastPositionSeconds;
        }
    @endphp

    <header class="transmission-header">
        <h1 class="transmission-title">Episode_Detail</h1>
        <div class="transmission-meta">
            <span>{{ $episode->episode_key }}</span>
            <span>{{ $episode->language }}</span>
            <span>{{ $episode->character_name ?: $episode->character_key ?: 'No character' }}</span>
            <span class="playback-badge is-{{ str_replace('_', '-', $playbackStatus) }}" data-playback-badge>{{ $playbackLabel }}</span>
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
                    data-duration-seconds="{{ $episode->audio_duration_seconds }}"
                    data-playback-status="{{ $playbackStatus }}"
                    data-resume-seconds="{{ $resumeSeconds }}"
                    data-playback-start-url="{{ route('listen.episodes.playback.start', $episode) }}"
                    data-playback-progress-url="{{ route('listen.episodes.playback.progress', $episode) }}"
                    data-playback-complete-url="{{ route('listen.episodes.playback.complete', $episode) }}"
                >
                    <button class="play-square" type="button" data-listen-play aria-label="Play episode">▷</button>
                    <div class="waveform waveform-visualizer" data-listen-waveform aria-hidden="true">
                        @for ($i = 0; $i < 32; $i++)
                            <span></span>
                        @endfor
                    </div>
                    <span class="duration" data-listen-duration>{{ $episode->audio_duration_seconds === null ? '00:00 / --:--' : '00:00 / ' . gmdate('i:s', $episode->audio_duration_seconds) }}</span>
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
                    <span class="playback-badge is-{{ str_replace('_', '-', $playbackStatus) }}" data-playback-badge>{{ $playbackLabel }}</span>
                    @if ($resumeSeconds > 0)
                        <span class="resume-hint">RESUME {{ gmdate('i:s', $resumeSeconds) }}</span>
                    @endif
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
                <div class="section-list" data-section-list>
                    @php
                        $sectionStartSeconds = 0;
                        $sectionFallbackSeconds = 30;
                    @endphp
                    @forelse ($episode->sections as $section)
                        @php
                            $sectionDurationSeconds = max(1, (int) ($section->estimated_duration_seconds ?? $sectionFallbackSeconds));
                            $sectionEndSeconds = $sectionStartSeconds + $sectionDurationSeconds;
                        @endphp
                        <article
                            class="section-card"
                            data-section
                            data-start-seconds="{{ $sectionStartSeconds }}"
                            data-end-seconds="{{ $sectionEndSeconds }}"
                        >
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
                        @php
                            $sectionStartSeconds = $sectionEndSeconds;
                        @endphp
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
