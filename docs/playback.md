# Playback

Phase 3 adds a private browser UI for listening to uploaded episodes, downloading MP3 files, and reading scenario sections and topics.

Phase 3.6 makes `/listen` the canonical authenticated viewer prefix. `/admin` remains the Filament admin tool and `/api` remains the machine-to-machine API surface.

## Routes

All playback routes require a browser login session.

```txt
GET /listen
GET /listen/episodes
GET /listen/episodes/{episode_key}
GET /listen/episodes/{episode_key}/audio
GET /listen/episodes/{episode_key}/download
```

`episode_key` is used in URLs instead of DB ids.

For compatibility, old `/episodes` viewer routes redirect to the matching `/listen/episodes` routes.

## Authentication

Playback uses the same Laravel session auth and allowed-email access rules as the admin foundation. Unauthenticated users are redirected to `/login`, which starts Google OAuth. Authenticated users not in `PLAYPIPE_ADMIN_ALLOWED_EMAILS` receive `403 Forbidden`. Local development may use `/_local/admin/login` only when the Phase 1 safety flags allow it.

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

## Listen Viewer UI

The daily playback UI is separate from Filament admin and uses dedicated Listen routes, controllers, views, CSS, and JavaScript:

- `/listen`: current transmission / viewer home
- `/listen/episodes`: available Episode archive
- `/listen/episodes/{episode_key}`: detail page with custom visual player, download link, scenario sections, and topics

The visual direction is documented in:

- `docs/design/listen-viewer/DESIGN.md`
- `docs/design/listen-viewer/prototype.html`
- `docs/design/listen-viewer/reference.png`

Filament remains the admin/inspection surface:

- `/admin`: admin dashboard
- `/admin/episodes`: Episode metadata
- `/admin/episode-sections`: extracted scenario sections
- `/admin/episode-topics`: extracted topic snapshots

## Playback State

The Listen viewer tracks playback state per user and per episode.

- `UNPLAYED`: represented by no `episode_playbacks` record
- `IN_PROGRESS`: persisted after the user starts playback
- `COMPLETED`: persisted when playback reaches the end

`COMPLETED` is terminal. Replaying an episode does not move it back to `IN_PROGRESS`.

Server-side state synchronization is handled by the `EpisodePlayer` Livewire component and `EpisodePlaybackService`. The browser JavaScript remains responsible for low-level `HTMLAudioElement` control, time display, waveform visualizer state, and scenario section tracking.

Progress sync runs through Livewire every 5 seconds while audio is playing and immediately on pause. `pagehide`, `beforeunload`, `sendBeacon`, and `fetch keepalive` sync are intentionally not implemented in this phase.

When an episode is `IN_PROGRESS`, the detail page provides a resume position. Resume starts at `0` when the saved position is below 5 seconds, within the final 10 seconds, or already at the duration.

## Range Requests

Phase 3 does not implement full Laravel-side Range request handling. Browser playback prefers temporary URL redirect when the disk supports it. The fallback stream response is sufficient for small files and test coverage, but advanced seek behavior may be revisited later.

## Not Implemented

Phase 3.6 does not implement Good / Bad feedback UI, radiopipe feedback sync, public podcast feeds, playback analytics, PWA support, real waveform generation, or `ffmpeg` / `ffprobe` integration.
