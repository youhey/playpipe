<?php

namespace App\Http\Controllers\Episodes;

use App\Models\Episode;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EpisodeDownloadController extends Controller
{
    public function __invoke(Episode $episode): StreamedResponse
    {
        abort_unless($episode->status === Episode::STATUS_AVAILABLE, 404);

        $storage = Storage::disk($episode->audio_disk);

        abort_unless($storage->exists($episode->audio_path), 404);

        $stream = $storage->readStream($episode->audio_path);

        if (! is_resource($stream)) {
            throw new RuntimeException('Episode audio stream could not be opened.');
        }

        return response()->streamDownload(static function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, $episode->episode_key . '.mp3', [
            'Content-Type' => 'audio/mpeg',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
