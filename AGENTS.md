# AGENTS.md

この文書は、このリポジトリで作業する Agent 向けのプロジェクト固有ルールです。

## Project Overview

`playpipe` は、将来的に `voicepipe` が生成した MP3 と、`radiopipe` の Episode JSON / scenario JSON を受け取り、プライベート Web アプリとして再生・ダウンロード・シナリオ閲覧・トピック評価を行う downstream Laravel アプリです。

Phase 1 では Laravel / Filament / Google OAuth / API Token / Docker Compose / CI / Dependabot / Makefile / Laravel Cloud 対応の土台を実装しました。

Phase 2 では `POST /api/episodes` による MP3 + `radiopipe` Episode JSON upload と、Episode / Section / Topic の保存モデルだけを扱います。

Phase 3 では browser login session で保護された Episode 一覧、詳細、audio、download route と、Filament での Episode 確認画面を扱います。Phase 3.6 では canonical viewer prefix を `/listen` にします。

## Repository Layout

Laravel アプリ本体は必ず `src/` 配下に置きます。

```txt
docs/                 # Development documents
docker/               # Dockerfiles and container configuration
src/                  # Laravel application source
.github/              # GitHub Actions and Dependabot
docker-compose.yml    # Local development environment
Makefile
README.md
AGENTS.md
composer.lock         # Laravel Cloud detection workaround
```

Laravel framework files をリポジトリルート直下に置かないでください。

## Target Runtime

本番の deployment target は Laravel Cloud です。

- Application containers are ephemeral.
- local filesystem を persistent storage として使いません。
- MP3 などの binary object は S3 compatible disk を前提にします。
- upload files は configured object storage disk に保存します。
- logs は stdout / stderr に出します。
- DB / cache / session / queue / filesystem は `.env` で切り替え可能にします。
- Laravel MySQL を標準の DB 前提にします。

## Local Development Stack

Docker Compose を使います。

- `php-cli`: Composer, Artisan, PHPUnit, Pest, PHPStan, PHP-CS-Fixer
- `php-fpm`: Laravel web runtime
- `nginx`: local HTTP frontend
- `node`: npm and Vite
- `mysql`: database
- `valkey`: Redis-compatible cache/session
- `minio`: S3-compatible object storage

Web request path:

```txt
nginx -> php-fpm -> Laravel
```

PHP commands and tests should use `php-cli`.
Node.js, npm, and Vite commands should use `node`.

## Application Defaults

Local defaults:

```env
DB_CONNECTION=mysql
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=database
FILESYSTEM_DISK=s3
LOG_CHANNEL=stderr
LOG_STACK=stderr
```

SQLite、file cache、file session を project default にしないでください。

## Environment File Policy

実 secrets は commit しません。

Tracked:

```txt
.env.example
src/.env.example
```

Ignored:

```txt
.env
.env.*
src/.env
src/.env.*
```

Google OAuth secrets、API tokens、S3 credentials、production environment values を commit しないでください。

## Laravel Cloud Compatibility

- production path で `storage/app` への永続保存を前提にしません。
- persistent storage は S3 compatible disk を使います。
- stderr logging を維持します。
- queue, session, cache, storage は `.env` で切り替えます。
- shell access や mutable container state に依存しません。
- `php artisan storage:link` を deployment assumption にしません。

## Laravel Cloud Repository Detection

authoritative Composer project は `src/composer.json` / `src/composer.lock` です。

root `composer.lock` は Laravel Cloud framework detection workaround として `src/composer.lock` からコピーしたファイルです。root を Composer project root として扱わないでください。

dependencies を更新した場合は `src/` 側を正として扱い、必要なときだけ root lock を refresh します。

```bash
cp src/composer.lock composer.lock
```

## Build and Deploy Expectations

Laravel Cloud build-time tasks:

```bash
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Deploy-time task:

```bash
php artisan migrate --force
```

## Testing

通常は Makefile を使います。

```bash
make test
make lint
```

`make test` は PHPUnit を実行します。`make lint` は PHPStan、PHP-CS-Fixer dry-run、Composer audit を実行します。

Automated tests must not call real external APIs. Google OAuth、Laravel Cloud API、将来の upload / feedback API は fake、mock、HTTP fake、fixture を使って検証してください。

## Code Style

既存の Laravel / Filament / sibling app の style を優先します。

- 最小変更を優先します。
- 既存 helper、config、service pattern を再利用します。
- 変更理由コメントは追加しません。
- 複雑な logic の説明が必要な場合だけ短いコメントを追加します。
- 変数名・関数名は英語にします。

## Security

- token hash、OAuth token、API secret を管理画面や logs に表示しません。
- plain text Sanctum token は発行直後に一度だけ表示します。
- allow list が空の場合、admin panel には誰も入れません。
- local dev login helper は `local` / `testing` かつ明示有効化時だけ動かします。
- production で local dev login helper を有効にしません。
- uploaded MP3 を、認証または署名付き access なしで公開しません。
- playback / download route は必ず認証を要求します。

## Episode Upload Constraints

- `POST /api/episodes` は Sanctum token と `episodes:write` ability を必須にします。
- upload files は `PLAYPIPE_AUDIO_DISK` / `playpipe.upload.storage_disk` の object storage disk に保存します。
- production upload を local filesystem に永続保存しません。
- `ffmpeg` / `ffprobe` dependency を `playpipe` に導入しません。
- duration は request の `audio_duration_seconds` がある場合だけ保存します。
- Episode JSON は original uploaded payload を保存します。
- DB では Episode、scenario section、topic snapshot に展開します。

## Episode Upload API Contract

The playpipe Episode Upload API contract is defined in `docs/openapi.yaml`.

Do not change Episode Upload API request or response schema definitions unless explicitly requested.

When Episode Upload API behavior, request fields, response shape, or accepted `episode_json` shape is intentionally changed, update `docs/openapi.yaml`, `docs/api.md` if applicable, and schema validation tests in the same task.

This rule is important because `voicepipe` and other downstream Rust applications may rely on the OpenAPI schema.

## Playback Constraints

- uploaded MP3 files を public storage から公開しません。
- playback and download routes must require authentication.
- audio route は temporary URL redirect を優先し、不可なら Laravel stream response にします。
- browser UI は simple Blade / Livewire を優先し、SPA complexity を追加しません。
- playback UI のために `ffmpeg` / `ffprobe` dependency を追加しません。
- raw Episode JSON を画面に不用意に dump しません。
- external links は `noopener noreferrer` を付けます。
- feedback UI / feedback sync は Phase 3 では実装しません。

## Listen Viewer UI

The `/listen` viewer is a separate authenticated visual experience from Filament admin.

Do not replace the `/listen` UI with Filament components or generic admin styling.

Visual fidelity to `docs/design/listen-viewer/reference.png`, `prototype.html`, and `DESIGN.md` is a priority for this area.

Authentication and allowed-email access rules are shared with the admin foundation, but routes, controllers, views, CSS, and JavaScript should remain separated.

Do not use remote hotlinked portrait images in production code.

## Phase 1 Scope

Phase 1 で実装するもの:

- Laravel app under `src/`
- Docker Compose local stack
- Makefile workflow
- Filament admin panel at `/admin`
- Google OAuth only admin authentication
- admin email allow list
- safe local dev login helper
- Sanctum API token admin resource
- API token issue / rotate Artisan commands
- GitHub Actions CI
- Dependabot
- Laravel Cloud detection root `composer.lock`
- README and docs

## Phase 2 Scope

Phase 2 で実装するもの:

- `POST /api/episodes`
- Sanctum `episodes:write` protected upload API
- MP3 object storage 保存
- uploaded Episode JSON object storage 保存
- Episode / EpisodeSection / EpisodeTopic models and migrations
- Episode JSON minimum structure validation
- duplicate `episode_key` の `409 Conflict`
- upload API feature tests

## Phase 3 Scope

Phase 3 で実装するもの:

- logged-in Episode list at `/listen/episodes`
- logged-in Episode detail at `/listen/episodes/{episode_key}`
- authenticated audio route
- authenticated MP3 download route
- scenario section display
- topic display with source/discussion links
- read-only Filament Episode / Section / Topic resources
- playback feature tests

## Not Implemented

以下は明示依頼があるまで実装しません。

- topic Good / Bad 評価 UI
- radiopipe feedback sync
- playback analytics
- public podcast feed
- audio synthesis
- AI generation
