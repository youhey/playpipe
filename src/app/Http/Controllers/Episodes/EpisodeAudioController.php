<?php

namespace App\Http\Controllers\Episodes;

use App\Models\Episode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class EpisodeAudioController extends Controller
{
    public function __invoke(Episode $episode): RedirectResponse|StreamedResponse
    {
        abort_unless($episode->status === Episode::STATUS_AVAILABLE, 404);

        $storage = Storage::disk($episode->audio_disk);

        abort_unless($storage->exists($episode->audio_path), 404);

        try {
            return redirect()->away($storage->temporaryUrl(
                $episode->audio_path,
                now()->addMinutes(5),
                ['ResponseContentType' => 'audio/mpeg'],
            ));
        } catch (Throwable) {
            $stream = $storage->readStream($episode->audio_path);

            if (! is_resource($stream)) {
                throw new RuntimeException('Episode audio stream could not be opened.');
            }

            $headers = [
                'Content-Type' => 'audio/mpeg',
                'Content-Disposition' => 'inline; filename="' . $this->fileName($episode) . '"',
                'Cache-Control' => 'private, no-store',
            ];

            $headers['Content-Length'] = (string) $storage->size($episode->audio_path);

            return response()->stream(static function () use ($stream): void {
                try {
                    fpassthru($stream);
                } finally {
                    fclose($stream);
                }
            }, 200, $headers);
        }
    }

    private function fileName(Episode $episode): string
    {
        return $episode->episode_key . '.mp3';
    }
}
