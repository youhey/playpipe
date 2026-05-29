# Environment

`playpipe` は root `.env` と `src/.env` を分けます。

- root `.env`: Docker Compose の DB 名、port forward など
- `src/.env`: Laravel application settings

実 secrets を commit しないでください。

## Local Defaults

```env
APP_NAME="playpipe"
APP_ENV="local"
APP_URL="http://localhost:8080"

DB_CONNECTION="mysql"
DB_HOST="mysql"
DB_PORT=3306
DB_DATABASE="playpipe"
DB_USERNAME="playpipe"
DB_PASSWORD="playpipe"

SESSION_DRIVER="redis"
CACHE_STORE="redis"
QUEUE_CONNECTION="database"
FILESYSTEM_DISK="s3"
LOG_CHANNEL="stderr"
LOG_STACK="stderr"

REDIS_CLIENT="phpredis"
REDIS_HOST="valkey"
REDIS_PORT=6379

AWS_ACCESS_KEY_ID="minioadmin"
AWS_SECRET_ACCESS_KEY="minioadmin"
AWS_DEFAULT_REGION="us-east-1"
AWS_BUCKET="playpipe-local"
AWS_ENDPOINT="http://minio:9000"
AWS_URL="http://localhost:9000/playpipe-local"
AWS_USE_PATH_STYLE_ENDPOINT=true
```

## Admin Auth

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

PLAYPIPE_ADMIN_ALLOWED_EMAILS=
PLAYPIPE_ADMIN_DEV_LOGIN_ENABLED=false
PLAYPIPE_ADMIN_DEV_LOGIN_EMAIL=
```

`PLAYPIPE_ADMIN_ALLOWED_EMAILS` は comma-separated です。前後空白を trim し、大文字小文字を区別しません。empty の場合は誰も admin panel に入れません。

`PLAYPIPE_ADMIN_DEV_LOGIN_ENABLED=true` は local/testing 限定です。`PLAYPIPE_ADMIN_DEV_LOGIN_EMAIL` は allow list にも含めてください。

## Laravel Cloud

```env
LARAVEL_CLOUD_API_TOKEN=
LARAVEL_CLOUD_ENVIRONMENT_ID=
```

この設定は admin dashboard の Laravel Cloud status widget で使います。未設定の場合、外部 API は呼びません。

## Root Docker Compose Defaults

```env
DB_DATABASE="playpipe"
DB_USERNAME="playpipe"
DB_PASSWORD="playpipe"
DB_ROOT_PASSWORD="root"
FORWARD_MYSQL_PORT=3306
FORWARD_NGINX_PORT=8080
FORWARD_VALKEY_PORT=6379
FORWARD_MINIO_SERVICE_PORT=9000
FORWARD_MINIO_CONSOLE_PORT=9001
```
