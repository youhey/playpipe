# AGENTS.md

この文書は、このリポジトリで作業する Agent 向けのプロジェクト固有ルールです。

## Project Overview

`playpipe` は、将来的に `voicepipe` が生成した MP3 と、`radiopipe` の Episode JSON / scenario JSON を受け取り、プライベート Web アプリとして再生・ダウンロード・シナリオ閲覧・トピック評価を行う downstream Laravel アプリです。

Phase 1 では機能本体ではなく、Laravel / Filament / Google OAuth / API Token / Docker Compose / CI / Dependabot / Makefile / Laravel Cloud 対応の土台だけを扱います。

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

## Phase 1 Scope

Phase 1 で実装するもの:

- Laravel app under `src/`
- Docker Compose local stack
- Makefile workflow
- Filament admin panel at `/admin`
- Google OAuth only admin authentication
- admin email allow list
- safe local dev login helper
- User read-only admin resource
- Sanctum API token admin resource
- API token issue / rotate Artisan commands
- GitHub Actions CI
- Dependabot
- Laravel Cloud detection root `composer.lock`
- README and docs

## Not Implemented In Phase 1

以下は Phase 1 では実装しません。

- `POST /api/episodes`
- MP3 upload
- MP3 playback UI
- MP3 download
- scenario JSON viewer
- topic Good / Bad 評価 UI
- radiopipe feedback sync
- playback analytics
- public podcast feed
- audio synthesis
- AI generation
