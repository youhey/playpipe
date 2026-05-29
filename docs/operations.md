# Operations

## Local Stack

通常の起動:

```bash
make build
make up
```

停止:

```bash
make down
```

local DB は `make up` で `migrate:refresh` と `db:seed` が実行されます。重要な local data を保存しないでください。

## Checks

```bash
make test
make lint
```

`make lint` は PHPStan、PHP-CS-Fixer dry-run、Composer audit を実行します。

## Laravel Cloud

production では local filesystem を persistent storage として使いません。将来の MP3 などの binary object は S3 compatible disk に保存します。

Laravel Cloud では環境変数で DB、cache、session、queue、filesystem を切り替えます。Laravel MySQL を標準 DB として扱います。

root `composer.lock` は Laravel Cloud detection workaround です。依存関係の正は `src/composer.json` と `src/composer.lock` です。

## External APIs

Phase 1 の automated tests は real external APIs を呼びません。

Laravel Cloud status widget は `LARAVEL_CLOUD_API_TOKEN` と `LARAVEL_CLOUD_ENVIRONMENT_ID` が設定されている場合だけ Laravel Cloud API を呼びます。
