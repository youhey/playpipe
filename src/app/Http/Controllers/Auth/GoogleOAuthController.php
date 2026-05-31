<?php

namespace App\Http\Controllers\Auth;

use App\Admin\AdminAccess;
use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/**
 * Google OAuth による管理画面ログインを処理する Controller。
 */
class GoogleOAuthController extends Controller
{
    /**
     * Google OAuth の認可画面へリダイレクトする。
     */
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google OAuth callback を処理して管理者セッションを開始する。
     */
    public function callback(AdminAccess $adminAccess, Request $request): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();
        $email = $googleUser->getEmail();

        abort_unless($adminAccess->isAllowedEmail($email), 403);

        $email = trim((string) $email);
        $name = $googleUser->getName();
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = is_string($name) && trim($name) !== '' ? $name : $email;
        $user->google_id = $googleUser->getId();
        $user->avatar_url = $googleUser->getAvatar();
        $user->email_verified_at = now();

        if (! $user->exists) {
            $user->password = Str::password(32);
        }

        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        $this->forgetUnsafeIntendedUrl($request);

        return redirect()->intended($this->adminDashboardUrl());
    }

    private function adminDashboardUrl(): string
    {
        return Filament::getUrl() ?? url('/admin');
    }

    private function forgetUnsafeIntendedUrl(Request $request): void
    {
        $intendedUrl = $request->session()->get('url.intended');

        if (! is_string($intendedUrl) || ! $this->isSafeIntendedUrl($intendedUrl, $request)) {
            $request->session()->forget('url.intended');
        }
    }

    private function isSafeIntendedUrl(string $url, Request $request): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return false;
        }

        $host = $parts['host'] ?? null;

        if (is_string($host) && ! hash_equals($request->getHost(), $host)) {
            return false;
        }

        $port = $parts['port'] ?? null;

        if (is_int($port) && $request->getPort() !== $port) {
            return false;
        }

        $path = '/' . ltrim($parts['path'] ?? '/', '/');

        if ($path === '/auth/google/callback' || $path === '/logout') {
            return false;
        }

        return $path !== '/api' && ! str_starts_with($path, '/api/');
    }
}
