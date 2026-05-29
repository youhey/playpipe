# API

Phase 1 では Episode upload API、Episode read API、feedback API は未実装です。

実装済みなのは、将来の `voicepipe -> playpipe` 連携に備えた Sanctum personal access token 管理だけです。

## API Token Management

管理画面:

```txt
/admin
```

CLI:

```bash
php artisan playpipe:users:create-api-user user@example.test --name="Playpipe API User"
php artisan playpipe:users:rotate-api-token user@example.test
```

既定 token name:

```txt
playpipe-api
```

既定 ability:

```txt
episodes:write
```

発行可能な ability:

- `episodes:write`
- `episodes:read`
- `feedback:write`
- `feedback:sync`

plain text token は発行直後に一度だけ表示され、DB には保存されません。管理画面では token hash、OAuth token、API secret を表示しません。

## Not Implemented

以下の HTTP API は Phase 1 では存在しません。

- `POST /api/episodes`
- episode upload
- episode read/list/latest endpoints
- feedback write/sync endpoints
