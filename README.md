# playpipe

Private Playback Pipeline for Rendered Radio Episodes.

`playpipe` is a tiny private Laravel app that will receive rendered radio episode audio and scenario JSON for playback, download, viewing, and feedback.

Phase 1 provides the application foundation only: Laravel, Filament, Google OAuth admin access, Sanctum API token management, Docker Compose, CI, Dependabot, Makefile workflow, and Laravel Cloud compatibility.

Episode upload, MP3 playback, scenario viewing, and feedback sync are planned for later phases and are not implemented in Phase 1.

## Position

```txt
digestpipe
  -> radiopipe
      -> voicepipe
          -> playpipe
```

`playpipe` is the downstream private playback app for artifacts that will be produced by `voicepipe` and `radiopipe`.

## Repository Structure

```txt
.
├── docs/
├── docker/
├── src/
├── .github/
├── .env.example
├── docker-compose.yml
├── Makefile
├── README.md
├── AGENTS.md
└── composer.lock
```

The Laravel application lives under `src/`. Do not place Laravel framework files in the repository root.

## Local Setup

```bash
make build
make up
```

Local defaults:

- Web: `http://localhost:8080`
- Admin panel: `http://localhost:8080/admin`
- MinIO console: `http://localhost:9001`
- MinIO credentials: `minioadmin` / `minioadmin`

The local stack uses MySQL, Valkey, and MinIO. Override forwarded ports in the root `.env` when running sibling apps at the same time.

## Development Workflow

Use the Makefile as the normal entrypoint:

```bash
make build
make up
make test
make lint
make fix
make down
```

`make test` runs PHPUnit through the `php-cli` container. `make lint` runs PHPStan, PHP-CS-Fixer dry-run, and Composer audit. Node/Vite tasks run through the `node` service with `make front-build`.

## Admin Panel

The Filament admin panel is available at `/admin`.

Authentication is Google OAuth only. Password login, registration, password reset, and invite flows are disabled.

Required admin environment variables:

```env
PLAYPIPE_ADMIN_ALLOWED_EMAILS=admin@example.test
PLAYPIPE_ADMIN_DEV_LOGIN_ENABLED=false
PLAYPIPE_ADMIN_DEV_LOGIN_EMAIL=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

`PLAYPIPE_ADMIN_ALLOWED_EMAILS` is comma-separated. Matching is case-insensitive and trims whitespace. If the allow list is empty, no user can access the admin panel.

For local browser debugging, `GET /_local/admin/login` logs in the configured development user only when `APP_ENV` is `local` or `testing`, `PLAYPIPE_ADMIN_DEV_LOGIN_ENABLED=true`, and the dev email is also allow-listed.

## API Tokens

Sanctum personal access token metadata is managed from the Filament admin panel. Plain text tokens are shown only once immediately after issue and are not stored.

CLI helpers:

```bash
php artisan playpipe:users:create-api-user user@example.test --name="Playpipe API User"
php artisan playpipe:users:rotate-api-token user@example.test
```

Default token name: `playpipe-api`.
Default ability: `episodes:write`.

Allowed abilities:

- `episodes:write`
- `episodes:read`
- `feedback:write`
- `feedback:sync`

Episode upload and feedback APIs are not implemented in Phase 1.

## Laravel Cloud

The production target is Laravel Cloud.

Important assumptions:

- Application containers are ephemeral.
- Persistent binary objects must use an S3-compatible disk, not local filesystem storage.
- Logs go to stdout/stderr.
- DB, cache, session, queue, and filesystem are selected by environment variables.
- Laravel MySQL is the expected database unless explicitly changed later.

The authoritative Composer project is `src/composer.json` and `src/composer.lock`.
The root `composer.lock` is copied from `src/composer.lock` only as a Laravel Cloud detection workaround.

Refresh it after dependency changes when needed:

```bash
cp src/composer.lock composer.lock
```

## GitHub Workflow

GitHub Actions CI runs on pull requests and pushes to `main`. It validates Composer metadata, installs dependencies, audits Composer packages, runs MySQL migrations, PHPUnit, PHPStan, and PHP-CS-Fixer dry-run.

Dependabot opens weekly Monday PRs for Composer dependencies under `/src` and GitHub Actions workflows under `/`.

## Environment Files

Tracked examples:

- `.env.example`
- `src/.env.example`

Ignored local files:

- `.env`
- `.env.*`
- `src/.env`
- `src/.env.*`

Do not commit Google OAuth secrets, API tokens, S3 credentials, or real production environment values.
