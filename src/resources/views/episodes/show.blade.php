@extends('layouts.playback')

@section('title', $episode->title)

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $episode->title }}</h1>
            <div class="meta-row">
                <span>{{ $episode->episode_key }}</span>
                <span>{{ $episode->language }}</span>
                <span>{{ $episode->character_name ?: $episode->character_key ?: 'No character' }}</span>
            </div>
        </div>
        <div class="actions">
            <a class="button secondary" href="{{ route('episodes.index') }}">Episodes</a>
            <a class="button" href="{{ route('episodes.download', $episode) }}">Download MP3</a>
        </div>
    </div>

    <div class="stack">
        <section class="panel">
            <h2>Player</h2>
            <audio class="audio-player" controls preload="metadata">
                <source src="{{ route('episodes.audio', $episode) }}" type="audio/mpeg">
            </audio>
            <div class="meta-row" style="margin-top: 12px;">
                <span>Published: {{ $episode->published_at?->format('Y-m-d H:i') ?? 'N/A' }}</span>
                <span>Recorded: {{ $episode->recorded_at?->format('Y-m-d H:i') ?? 'N/A' }}</span>
                <span>Duration: {{ $episode->audio_duration_seconds === null ? 'N/A' : gmdate('i:s', $episode->audio_duration_seconds) }}</span>
                <span>Size: {{ $episode->audio_size_bytes === null ? 'N/A' : number_format($episode->audio_size_bytes) . ' bytes' }}</span>
            </div>
        </section>

        <section class="panel">
            <h2>Scenario Sections</h2>
            @forelse ($episode->sections as $section)
                <article class="section-block">
                    <div class="meta-row">
                        <span class="pill">{{ $section->section_type }}</span>
                        <span>#{{ $section->sort_order }}</span>
                        @if ($section->estimated_duration_seconds !== null)
                            <span>{{ $section->estimated_duration_seconds }} sec</span>
                        @endif
                    </div>
                    <h3>{{ $section->title }}</h3>
                    <div class="section-text">{{ $section->text }}</div>
                </article>
            @empty
                <p class="muted">No sections found.</p>
            @endforelse
        </section>

        <section class="panel">
            <h2>Topics</h2>
            @forelse ($episode->topics as $topic)
                <article class="topic-block">
                    <div class="meta-row">
                        <span>#{{ $topic->sort_order }}</span>
                        @if ($topic->source_name)
                            <span>{{ $topic->source_name }}</span>
                        @endif
                        @if ($topic->topic_id)
                            <span>{{ $topic->topic_id }}</span>
                        @endif
                    </div>
                    <h3>{{ $topic->title }}</h3>
                    @if ($topic->summary)
                        <p class="topic-text">{{ $topic->summary }}</p>
                    @endif
                    @if ($topic->why_it_matters)
                        <p class="topic-text">{{ $topic->why_it_matters }}</p>
                    @endif
                    <div class="actions">
                        @if ($topic->url)
                            <a href="{{ $topic->url }}" target="_blank" rel="noopener noreferrer">Source</a>
                        @endif
                        @if ($topic->discussion_url)
                            <a href="{{ $topic->discussion_url }}" target="_blank" rel="noopener noreferrer">Discussion</a>
                        @endif
                    </div>
                </article>
            @empty
                <p class="muted">No topics found.</p>
            @endforelse
        </section>
    </div>
@endsection
