<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreEpisodeRequest;
use App\Services\Episodes\DuplicateEpisodeException;
use App\Services\Episodes\EpisodeIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * voicepipe からの Episode upload を受け付ける private API controller。
 */
class EpisodeUploadController extends Controller
{
    public function __invoke(StoreEpisodeRequest $request, EpisodeIngestionService $service): JsonResponse
    {
        try {
            $episode = $service->ingest(
                audio: $request->audioFile(),
                episodePayload: $request->episodePayload(),
                episodeJsonRaw: $request->episodeJsonRaw(),
                audioDurationSeconds: $request->audioDurationSeconds(),
                recordedAt: $request->recordedAt(),
                voicepipeVersion: $request->voicepipeVersion(),
                renderMetadata: $request->renderMetadataPayload(),
            );
        } catch (DuplicateEpisodeException $exception) {
            return response()->json([
                'message' => 'Episode already exists.',
                'episode_key' => $exception->episodeKey,
            ], 409);
        }

        return response()->json(['episode' => $episode], 201);
    }
}
