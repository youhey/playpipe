<section class="broadcast-panel">
    <div class="panel-header">
        <span>Now Playing</span>
        <span>{{ $episode->published_at?->format('Y.m.d') ?? $episode->recorded_at?->format('Y.m.d') ?? $episode->created_at?->format('Y.m.d') }}</span>
    </div>
    <div class="panel-body">
        <h2 class="episode-title">{{ $episode->title }}</h2>

        <div wire:ignore data-episode-player-root>
            <div
                class="player-frame listen-player"
                data-listen-player
                data-duration-seconds="{{ $episode->audio_duration_seconds }}"
                data-playback-status="{{ $playbackStatus }}"
                data-resume-seconds="{{ $resumeSeconds }}"
            >
                <button class="play-square" type="button" data-listen-play aria-label="Play episode">▷</button>
                <div class="waveform waveform-visualizer" data-listen-waveform aria-hidden="true">
                    @for ($i = 0; $i < 32; ++$i)
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
        </div>

        <div class="meta-row">
            <span>Published: {{ $episode->published_at?->format('Y-m-d H:i') ?? 'N/A' }}</span>
            <span>Recorded: {{ $episode->recorded_at?->format('Y-m-d H:i') ?? 'N/A' }}</span>
            <span>Size: {{ $episode->audio_size_bytes === null ? 'N/A' : number_format($episode->audio_size_bytes) . ' bytes' }}</span>
            <span class="playback-badge is-{{ str_replace('_', '-', $playbackStatus) }}" data-playback-badge>{{ $this->playbackLabel() }}</span>
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
