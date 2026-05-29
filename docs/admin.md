# Admin

Filament admin panel は `/admin` で提供します。

Phase 1 の admin panel で扱うもの:

- Dashboard
- Laravel Cloud deployment status widget
- User metadata の read-only 一覧
- Sanctum API token metadata の一覧
- API token 発行
- token 個別失効
- User 単位の全 token 失効

## Authentication

Google OAuth のみを使います。

無効なもの:

- password login
- registration
- password reset
- invite flow

`PLAYPIPE_ADMIN_ALLOWED_EMAILS` に含まれる email だけが通過できます。allow list が空の場合、誰も admin panel に入れません。

## Local Dev Login

local browser debug 用に `GET /_local/admin/login` を用意しています。

有効条件:

- `APP_ENV=local` または `testing`
- `PLAYPIPE_ADMIN_DEV_LOGIN_ENABLED=true`
- `PLAYPIPE_ADMIN_DEV_LOGIN_EMAIL` が設定済み
- dev email が `PLAYPIPE_ADMIN_ALLOWED_EMAILS` に含まれる

production では 404 になります。

## Token Safety

plain text token は発行直後の modal / CLI output で一度だけ表示されます。

管理画面に表示しないもの:

- token hash
- plain text token の再表示
- OAuth token
- API secret
