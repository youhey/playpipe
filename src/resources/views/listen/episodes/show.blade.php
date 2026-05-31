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
        <livewire:listen.episode-player :episode="$episode" />

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
                                <livewire:listen.topic-rating-controls :topic="$topic" :key="'topic-rating-'.$topic->id" />
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
