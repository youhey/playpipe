# API

`playpipe` の private Web API は Laravel Sanctum personal access token で保護します。

Phase 2 では `voicepipe` などの renderer から、MP3 file と `radiopipe` Episode JSON を受け取る write-only API を提供します。Phase 3 の playback UI は browser login session で保護された Web route として提供し、API token では保護しません。

Machine-readable contract は [`docs/openapi.yaml`](openapi.yaml) です。`voicepipe` などの Rust application はこの OpenAPI document を integration contract として参照します。

## API Token

API token は Filament admin の `/admin` から管理できます。

CLI でも token を発行・再発行できます。

```bash
php artisan playpipe:users:create-api-user user@example.test --name="Playpipe API User"
php artisan playpipe:users:rotate-api-token user@example.test --ability=episodes:write
```

既定 token name は `playpipe-api` です。Episode upload には `episodes:write` ability が必要です。

plain text token は発行直後だけ表示され、DB には保存されません。token hash、OAuth token、API secret は管理画面に表示しません。

## POST /api/episodes

OpenAPI operation:

```txt
POST /api/episodes
docs/openapi.yaml#/paths/~1api~1episodes/post
```

```http
POST /api/episodes
Authorization: Bearer {PLAYPIPE_API_TOKEN}
Content-Type: multipart/form-data
```

Required ability:

```txt
episodes:write
```

Request fields:

| field | required | type | note |
|---|---:|---|---|
| `audio` | yes | file | MP3 file |
| `episode_json` | yes | JSON part, JSON string, or file | `radiopipe` Episode JSON. Preferred contract is an `application/json` multipart part |
| `audio_duration_seconds` | no | integer | renderer 側で分かる場合だけ送る |
| `recorded_at` | no | datetime | renderer 側の生成日時 |
| `voicepipe_version` | no | string | 任意 |
| `render_metadata_json` | no | JSON string | speaker / speed / pitch などの renderer metadata |

Example:

```bash
curl -X POST "https://example.test/api/episodes" \
  -H "Authorization: Bearer ${PLAYPIPE_API_TOKEN}" \
  -F "audio=@episode.mp3;type=audio/mpeg" \
  -F "episode_json=@episode.json;type=application/json" \
  -F "audio_duration_seconds=900" \
  -F "recorded_at=2026-05-29T07:10:00+09:00" \
  -F "voicepipe_version=voicepipe-2026.05"
```

Response:

```json
{
  "episode": {
    "episode_key": "episode_2026-05-29_0700_neko_nyan_001",
    "status": "available",
    "title": "今日のギークニュース",
    "language": "ja",
    "audio": {
      "disk": "s3",
      "path": "episodes/episode_2026-05-29_0700_neko_nyan_001/audio.mp3",
      "size_bytes": 12345678,
      "duration_seconds": 900
    },
    "sections_count": 5,
    "topics_count": 5,
    "created_at": "2026-05-30T03:00:00+00:00"
  }
}
```

## Episode JSON

`episode_json` の documented schema は `docs/openapi.yaml#/components/schemas/RadiopipeEpisodePayload` です。安定した必須 field だけを要求し、`radiopipe` / `voicepipe` 側の追加 field は forward compatibility のため許可します。

最低限、以下を要求します。

- `episode`
- `episode.episode_key`
- `episode.title`
- `episode.language`
- `episode.scenario_json`
- `episode.scenario_json.sections`
- 各 section の `type`, `title`, `text`

`episode.topics` がある場合は `episode_topics` に展開保存します。topic の詳細 field は optional です。

元の Episode JSON payload は DB の `episodes.episode_json` と configured object storage disk の `episodes/{episode_key}/episode.json` に保存します。

## Duplicate

同じ `episode_key` が既に存在する場合、デフォルトでは差し替えません。

```json
{
  "message": "Episode already exists.",
  "episode_key": "episode_2026-05-29_0700_neko_nyan_001"
}
```

HTTP status は `409 Conflict` です。

## Validation Errors

Validation error は Laravel 標準の `422 Unprocessable Entity` です。

```json
{
  "message": "The episode_json must contain episode.scenario_json.sections.0.text.",
  "errors": {
    "episode_json": [
      "The episode_json must contain episode.scenario_json.sections.0.text."
    ]
  }
}
```

OpenAPI schema validation tests verify success, duplicate, validation, authentication, and authorization response shapes against `docs/openapi.yaml`.

## Storage

Upload files は `config('playpipe.upload.storage_disk')` の disk に保存します。local default は MinIO backed `s3` disk です。

保存 path:

```txt
episodes/{episode_key}/audio.mp3
episodes/{episode_key}/episode.json
```

Production upload は local filesystem に永続保存しません。`storage/app`、`public` disk、`php artisan storage:link`、公開 URLには依存しません。

## Playback UI

Episode upload API は引き続き write-only です。Episode playback と MP3 download は API token ではなく、browser login session で保護された `/listen` Web route で提供します。

```txt
GET /listen
GET /listen/episodes
GET /listen/episodes/{episode_key}
GET /listen/episodes/{episode_key}/audio
GET /listen/episodes/{episode_key}/download
```

Audio route は private object を public bucket にせず、temporary URL redirect または Laravel stream response で返します。

詳細は [`docs/playback.md`](playback.md) を参照してください。

## Not Implemented

Phase 3 では以下を提供しません。

- Episode read/list/latest API
- Good / Bad feedback API
- radiopipe feedback sync
