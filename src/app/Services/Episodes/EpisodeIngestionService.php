<?php

namespace App\Services\Episodes;

use App\Models\Episode;
use App\Models\EpisodeSection;
use App\Models\EpisodeTopic;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class EpisodeIngestionService
{
    /**
     * @param array<string, mixed> $episodePayload
     * @param array<string, mixed>|null $renderMetadata
     *
     * @return array<string, mixed>
     */
    public function ingest(
        UploadedFile $audio,
        array $episodePayload,
        string $episodeJsonRaw,
        ?int $audioDurationSeconds,
        ?string $recordedAt,
        ?string $voicepipeVersion,
        ?array $renderMetadata,
    ): array {
        $validated = $this->validateEpisodePayload($episodePayload);
        $episodeKey = $validated['episode_key'];

        if (DB::table('episodes')->where('episode_key', $episodeKey)->exists()) {
            throw new DuplicateEpisodeException($episodeKey);
        }

        $disk = $this->storageDisk();
        $audioPath = "episodes/{$episodeKey}/audio.mp3";
        $episodeJsonPath = "episodes/{$episodeKey}/episode.json";

        $this->storeAudio($disk, $audioPath, $audio);
        $this->storeJson($disk, $episodeJsonPath, $episodeJsonRaw);

        /** @var Episode $episode */
        $episode = DB::transaction(function () use (
            $audio,
            $audioDurationSeconds,
            $audioPath,
            $disk,
            $episodePayload,
            $recordedAt,
            $renderMetadata,
            $validated,
            $voicepipeVersion,
        ): Episode {
            $episode = Episode::query()->create([
                'episode_key' => $validated['episode_key'],
                'status' => $validated['status'],
                'title' => $validated['title'],
                'language' => $validated['language'],
                'character_key' => $validated['character_key'],
                'character_name' => $validated['character_name'],
                'published_at' => $validated['published_at'],
                'processed_at' => $validated['processed_at'],
                'recorded_at' => $recordedAt === null ? null : CarbonImmutable::parse($recordedAt),
                'audio_disk' => $disk,
                'audio_path' => $audioPath,
                'audio_size_bytes' => $audio->getSize(),
                'audio_duration_seconds' => $audioDurationSeconds,
                'voicepipe_version' => $voicepipeVersion,
                'episode_json' => $episodePayload,
                'scenario_json' => $validated['scenario_json'],
                'render_metadata_json' => $renderMetadata,
            ]);

            $this->createSections($episode, $validated['sections']);
            $this->createTopics($episode, $validated['topics']);

            return $episode->load(['sections', 'topics']);
        });

        return $this->responsePayload($episode);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{
     *     episode_key: string,
     *     status: string,
     *     title: string,
     *     language: string,
     *     character_key: string|null,
     *     character_name: string|null,
     *     published_at: CarbonImmutable|null,
     *     processed_at: CarbonImmutable|null,
     *     scenario_json: array<string, mixed>,
     *     sections: list<array<string, mixed>>,
     *     topics: list<array<string, mixed>>
     * }
     */
    private function validateEpisodePayload(array $payload): array
    {
        $episode = $this->requiredObject($payload, 'episode', 'episode');
        $episodeKey = $this->requiredString($episode, 'episode_key', 'episode.episode_key');

        if (preg_match('/\A[A-Za-z0-9._-]+\z/', $episodeKey) !== 1) {
            throw ValidationException::withMessages([
                'episode_json' => ['The episode_json episode.episode_key may only contain letters, numbers, dots, underscores, and hyphens.'],
            ]);
        }

        $scenarioJson = $this->requiredObject($episode, 'scenario_json', 'episode.scenario_json');
        $sections = $this->requiredList($scenarioJson, 'sections', 'episode.scenario_json.sections');

        $validatedSections = [];

        foreach ($sections as $index => $section) {
            if (! is_array($section) || array_is_list($section)) {
                $this->missing("episode.scenario_json.sections.{$index}");
            }

            /** @var array<string, mixed> $section */
            $validatedSections[] = [
                'section_type' => $this->requiredString($section, 'type', "episode.scenario_json.sections.{$index}.type"),
                'title' => $this->requiredString($section, 'title', "episode.scenario_json.sections.{$index}.title"),
                'text' => $this->requiredString($section, 'text', "episode.scenario_json.sections.{$index}.text"),
                'estimated_duration_seconds' => $this->optionalUnsignedInteger($section, 'estimated_duration_seconds', "episode.scenario_json.sections.{$index}.estimated_duration_seconds"),
                'raw_section_json' => $section,
                'sort_order' => $index + 1,
            ];
        }

        $topics = [];
        $rawTopics = $this->optionalList($episode, 'topics', 'episode.topics');

        foreach ($rawTopics as $index => $topic) {
            if (! is_array($topic) || array_is_list($topic)) {
                $this->missing("episode.topics.{$index}");
            }

            /** @var array<string, mixed> $topic */
            $topics[] = [
                'topic_id' => $this->optionalString($topic, 'topic_id', "episode.topics.{$index}.topic_id"),
                'status' => $this->optionalString($topic, 'status', "episode.topics.{$index}.status"),
                'title' => $this->optionalString($topic, 'title', "episode.topics.{$index}.title") ?? '',
                'summary' => $this->optionalString($topic, 'summary', "episode.topics.{$index}.summary"),
                'why_it_matters' => $this->optionalString($topic, 'why_it_matters', "episode.topics.{$index}.why_it_matters"),
                'source_name' => $this->optionalString($topic, 'source_name', "episode.topics.{$index}.source_name"),
                'url' => $this->optionalString($topic, 'url', "episode.topics.{$index}.url"),
                'discussion_url' => $this->optionalString($topic, 'discussion_url', "episode.topics.{$index}.discussion_url"),
                'sort_order' => $this->optionalUnsignedInteger($topic, 'sort_order', "episode.topics.{$index}.sort_order") ?? ($index + 1),
                'raw_topic_json' => $topic,
            ];
        }

        $character = $this->optionalObject($episode, 'character', 'episode.character');

        return [
            'episode_key' => $episodeKey,
            'status' => Episode::STATUS_AVAILABLE,
            'title' => $this->requiredString($episode, 'title', 'episode.title'),
            'language' => $this->requiredString($episode, 'language', 'episode.language'),
            'character_key' => $character === null ? null : $this->optionalString($character, 'key', 'episode.character.key'),
            'character_name' => $character === null ? null : $this->optionalString($character, 'name', 'episode.character.name'),
            'published_at' => $this->optionalDateTime($episode, 'published_at', 'episode.published_at'),
            'processed_at' => $this->optionalDateTime($episode, 'processed_at', 'episode.processed_at'),
            'scenario_json' => $scenarioJson,
            'sections' => $validatedSections,
            'topics' => $topics,
        ];
    }

    /**
     * @param list<array<string, mixed>> $sections
     */
    private function createSections(Episode $episode, array $sections): void
    {
        foreach ($sections as $section) {
            EpisodeSection::query()->create([
                'episode_id' => $episode->id,
                'section_type' => $section['section_type'],
                'title' => $section['title'],
                'text' => $section['text'],
                'estimated_duration_seconds' => $section['estimated_duration_seconds'],
                'sort_order' => $section['sort_order'],
                'raw_section_json' => $section['raw_section_json'],
            ]);
        }
    }

    /**
     * @param list<array<string, mixed>> $topics
     */
    private function createTopics(Episode $episode, array $topics): void
    {
        foreach ($topics as $topic) {
            EpisodeTopic::query()->create([
                'episode_id' => $episode->id,
                'topic_id' => $topic['topic_id'],
                'status' => $topic['status'],
                'title' => $topic['title'],
                'summary' => $topic['summary'],
                'why_it_matters' => $topic['why_it_matters'],
                'source_name' => $topic['source_name'],
                'url' => $topic['url'],
                'discussion_url' => $topic['discussion_url'],
                'sort_order' => $topic['sort_order'],
                'raw_topic_json' => $topic['raw_topic_json'],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function responsePayload(Episode $episode): array
    {
        return [
            'episode_key' => $episode->episode_key,
            'status' => $episode->status,
            'title' => $episode->title,
            'language' => $episode->language,
            'audio' => [
                'disk' => $episode->audio_disk,
                'path' => $episode->audio_path,
                'size_bytes' => $episode->audio_size_bytes,
                'duration_seconds' => $episode->audio_duration_seconds,
            ],
            'sections_count' => $episode->sections->count(),
            'topics_count' => $episode->topics->count(),
            'created_at' => $episode->created_at?->toAtomString(),
        ];
    }

    private function storageDisk(): string
    {
        $disk = config('playpipe.upload.storage_disk', 's3');

        return is_string($disk) && $disk !== '' ? $disk : 's3';
    }

    private function storeAudio(string $disk, string $path, UploadedFile $audio): void
    {
        $realPath = $audio->getRealPath();
        $stream = is_string($realPath) ? fopen($realPath, 'r') : false;

        if ($stream === false) {
            throw new RuntimeException('Uploaded audio file could not be read.');
        }

        try {
            $stored = Storage::disk($disk)->put($path, $stream);
        } finally {
            fclose($stream);
        }

        if ($stored === false) {
            throw new RuntimeException('Uploaded audio file could not be stored.');
        }
    }

    private function storeJson(string $disk, string $path, string $json): void
    {
        if (Storage::disk($disk)->put($path, $json) === false) {
            throw new RuntimeException('Uploaded episode JSON could not be stored.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function requiredObject(array $payload, string $key, string $path): array
    {
        $value = $payload[$key] ?? null;

        if (! is_array($value) || array_is_list($value)) {
            $this->missing($path);
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|null
     */
    private function optionalObject(array $payload, string $key, string $path): ?array
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null) {
            return null;
        }

        $value = $payload[$key];

        if (! is_array($value) || array_is_list($value)) {
            $this->missing($path);
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<mixed>
     */
    private function requiredList(array $payload, string $key, string $path): array
    {
        $value = $payload[$key] ?? null;

        if (! is_array($value) || ! array_is_list($value) || $value === []) {
            $this->missing($path);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<mixed>
     */
    private function optionalList(array $payload, string $key, string $path): array
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null) {
            return [];
        }

        $value = $payload[$key];

        if (! is_array($value) || ! array_is_list($value)) {
            $this->missing($path);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredString(array $payload, string $key, string $path): string
    {
        $value = $payload[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            $this->missing($path);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function optionalString(array $payload, string $key, string $path): ?string
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null) {
            return null;
        }

        $value = $payload[$key];

        if (! is_string($value)) {
            $this->missing($path);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function optionalUnsignedInteger(array $payload, string $key, string $path): ?int
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null) {
            return null;
        }

        $value = $payload[$key];

        if (! is_int($value) || $value < 0) {
            $this->missing($path);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function optionalDateTime(array $payload, string $key, string $path): ?CarbonImmutable
    {
        $value = $this->optionalString($payload, $key, $path);

        if ($value === null) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'episode_json' => ["The episode_json {$path} value must be a valid datetime."],
            ]);
        }
    }

    /**
     * @return never
     */
    private function missing(string $path): void
    {
        throw ValidationException::withMessages([
            'episode_json' => ["The episode_json must contain {$path}."],
        ]);
    }
}
