<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use JsonException;

/**
 * Episode upload API の入力を検証する FormRequest。
 */
class StoreEpisodeRequest extends FormRequest
{
    /** @var array<string, mixed>|null */
    private ?array $episodePayload = null;

    private ?string $episodeJsonRaw = null;

    /** @var array<string, mixed>|null */
    private ?array $renderMetadataPayload = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $configuredAudioMaxKb = config('playpipe.upload.audio_max_kb', 102400);
        $audioMaxKb = is_int($configuredAudioMaxKb) || is_string($configuredAudioMaxKb)
            ? (int) $configuredAudioMaxKb
            : 102400;

        return [
            'audio' => [
                'required',
                'file',
                'mimetypes:audio/mpeg,audio/mp3,application/octet-stream',
                'max:' . $audioMaxKb,
            ],
            'episode_json' => ['required'],
            'audio_duration_seconds' => ['nullable', 'integer', 'min:0'],
            'recorded_at' => ['nullable', 'date'],
            'voicepipe_version' => ['nullable', 'string', 'max:100'],
            'render_metadata_json' => ['nullable'],
        ];
    }

    public function audioFile(): UploadedFile
    {
        $audio = $this->file('audio');

        if (! $audio instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'audio' => ['The audio field must contain an uploaded file.'],
            ]);
        }

        return $audio;
    }

    /**
     * @return array<string, mixed>
     */
    public function episodePayload(): array
    {
        if ($this->episodePayload === null) {
            $this->episodePayload = $this->decodeJsonObject('episode_json');
        }

        return $this->episodePayload;
    }

    public function episodeJsonRaw(): string
    {
        if ($this->episodeJsonRaw === null) {
            $this->episodeJsonRaw = $this->rawJsonValue('episode_json');
        }

        return $this->episodeJsonRaw;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function renderMetadataPayload(): ?array
    {
        if (! $this->has('render_metadata_json') && ! $this->hasFile('render_metadata_json')) {
            return null;
        }

        if ($this->renderMetadataPayload === null) {
            $this->renderMetadataPayload = $this->decodeJsonObject('render_metadata_json');
        }

        return $this->renderMetadataPayload;
    }

    public function audioDurationSeconds(): ?int
    {
        $value = $this->validated('audio_duration_seconds');

        if ($value === null) {
            return null;
        }

        return is_int($value) || is_string($value) ? (int) $value : null;
    }

    public function recordedAt(): ?string
    {
        $value = $this->validated('recorded_at');

        return is_string($value) ? $value : null;
    }

    public function voicepipeVersion(): ?string
    {
        $value = $this->validated('voicepipe_version');

        return is_string($value) ? $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $field): array
    {
        $json = $this->rawJsonValue($field);

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                $field => ["The {$field} field must contain valid JSON."],
            ]);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw ValidationException::withMessages([
                $field => ["The {$field} field must contain a JSON object."],
            ]);
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    private function rawJsonValue(string $field): string
    {
        $file = $this->file($field);

        if ($file instanceof UploadedFile) {
            $path = $file->getRealPath();
            $contents = is_string($path) ? file_get_contents($path) : false;

            if (! is_string($contents)) {
                throw ValidationException::withMessages([
                    $field => ["The {$field} field could not be read."],
                ]);
            }

            return $contents;
        }

        $value = $this->input($field);

        if (! is_string($value)) {
            throw ValidationException::withMessages([
                $field => ["The {$field} field must contain JSON."],
            ]);
        }

        return $value;
    }
}
