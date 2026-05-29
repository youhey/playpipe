# Playback

Phase 3 adds a private browser UI for listening to uploaded episodes, downloading MP3 files, and reading scenario sections and topics.

## Routes

All playback routes require a browser login session.

```txt
GET /episodes
GET /episodes/{episode_key}
GET /episodes/{episode_key}/audio
GET /episodes/{episode_key}/download
```

`episode_key` is used in URLs instead of DB ids.

## Authentication

Playback uses the same Laravel session auth as the admin foundation. Unauthenticated users are redirected to `/login`, which starts Google OAuth. Local development may use `/_local/admin/login` only when the Phase 1 safety flags allow it.

API tokens are not used for playback or download routes. API tokens remain for machine-to-machine upload.

## Private Storage

Uploaded MP3 files remain private object storage objects.

The audio route first tries a short-lived temporary object storage URL. If the configured disk does not support temporary URLs, Laravel streams the object through the authenticated route with `audio/mpeg`.

The download route streams the private object through Laravel as `{episode_key}.mp3`.

The app does not depend on:

- public buckets
- public disks
- `php artisan storage:link`
- unauthenticated direct object URLs

## UI

The daily playback UI is separate from Filament admin:

- `/episodes`: available Episode list
- `/episodes/{episode_key}`: detail page with HTML5 audio, download link, scenario sections, and topics

Filament remains the admin/inspection surface:

- `/admin`: admin dashboard
- `/admin/episodes`: Episode metadata
- `/admin/episode-sections`: extracted scenario sections
- `/admin/episode-topics`: extracted topic snapshots

## Range Requests

Phase 3 does not implement full Laravel-side Range request handling. Browser playback prefers temporary URL redirect when the disk supports it. The fallback stream response is sufficient for small files and test coverage, but advanced seek behavior may be revisited later.

## Not Implemented

Phase 3 does not implement Good / Bad feedback UI, radiopipe feedback sync, public podcast feeds, playback analytics, PWA support, or custom audio-player JavaScript.
