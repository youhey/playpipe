<div class="topic-rating" data-topic-rating>
    @if (! $rateable)
        <span class="topic-rating-disabled">NO_TOPIC_ID</span>
    @else
        <div class="topic-rating-label">
            <span>SIGNAL_RATING</span>
            @if ($errorMessage)
                <span class="topic-rating-error">{{ $errorMessage }}</span>
            @elseif ($rating !== null)
                <span class="topic-rating-synced">SYNCED</span>
            @endif
        </div>

        <div class="topic-rating-controls" role="group" aria-label="Topic signal rating">
            @for ($star = 1; $star <= 5; ++$star)
                <button
                    type="button"
                    class="topic-rating-star @if($rating !== null && $rating > 0 && $star <= $rating) is-selected @endif"
                    wire:click="rate({{ $star }})"
                    wire:loading.attr="disabled"
                    aria-label="Rate Good {{ $star }}"
                >★</button>
            @endfor

            <button
                type="button"
                class="topic-rating-bad @if($rating === -1) is-selected @endif"
                wire:click="rate(-1)"
                wire:loading.attr="disabled"
                aria-label="Rate Bad"
            >👎 BAD_SIGNAL</button>

            @if ($rating !== null)
                <button
                    type="button"
                    class="topic-rating-clear"
                    wire:click="clear"
                    wire:loading.attr="disabled"
                    aria-label="Clear Rating"
                >CLEAR_SIGNAL</button>
            @endif
        </div>
    @endif
</div>
