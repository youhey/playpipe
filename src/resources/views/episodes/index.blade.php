@extends('layouts.playback')

@section('title', 'Episodes')

@section('content')
    <div class="page-header">
        <div>
            <h1>Episodes</h1>
            <div class="meta-row">
                <span>{{ $episodes->total() }} available</span>
            </div>
        </div>
    </div>

    <form class="filters" method="GET" action="{{ route('episodes.index') }}">
        <input
            class="input"
            type="search"
            name="q"
            value="{{ $filters['q'] ?? '' }}"
            placeholder="Search title or episode key"
            aria-label="Search title or episode key"
        >
        <select class="select" name="character" aria-label="Character">
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
        <button class="button" type="submit">Search</button>
    </form>

    <div class="episode-list">
        @forelse ($episodes as $episode)
            <article class="episode-card">
                <div>
                    <a class="episode-card-title" href="{{ route('episodes.show', $episode) }}">
                        {{ $episode->title }}
                    </a>
                    <div class="meta-row">
                        <span>{{ $episode->published_at?->format('Y-m-d H:i') ?? $episode->recorded_at?->format('Y-m-d H:i') ?? $episode->created_at?->format('Y-m-d H:i') }}</span>
                        <span>{{ $episode->character_name ?: $episode->character_key ?: 'No character' }}</span>
                        <span>{{ $episode->audio_duration_seconds === null ? 'Duration unknown' : gmdate('i:s', $episode->audio_duration_seconds) }}</span>
                        <span>{{ $episode->topics_count }} topics</span>
                    </div>
                </div>
                <div class="meta-row">
                    <span>{{ $episode->episode_key }}</span>
                </div>
                <div class="actions">
                    <a class="button secondary" href="{{ route('episodes.show', $episode) }}">Open</a>
                </div>
            </article>
        @empty
            <div class="panel">
                <p class="muted">No episodes found.</p>
            </div>
        @endforelse
    </div>

    <div class="pagination">
        {{ $episodes->links() }}
    </div>
@endsection
