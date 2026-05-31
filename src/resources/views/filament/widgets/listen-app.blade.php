<x-filament-widgets::widget>
    <x-filament::section
        description="Private playback viewer"
        heading="Listen App"
    >
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Latest episode</div>
                <div class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                    {{ $latestEpisode?->title ?? 'N/A' }}
                </div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Latest date</div>
                <div class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                    {{ $latestEpisodeDate?->format('Y-m-d H:i:s T') ?? 'N/A' }}
                </div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Available episodes</div>
                <div class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                    {{ number_format($availableEpisodeCount) }}
                </div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Storage disk</div>
                <div class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                    {{ $storageDisk }}
                </div>
            </div>
        </div>

        <div class="mt-5">
            <x-filament::button
                :href="route('listen.home')"
                tag="a"
            >
                OPEN LISTEN
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
