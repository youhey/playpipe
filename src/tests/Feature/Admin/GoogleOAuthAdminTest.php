<?php

namespace Tests\Feature\Admin;

use App\Filament\Widgets\ListenAppWidget;
use App\Models\Episode;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @internal
 */
class GoogleOAuthAdminTest extends TestCase
{
    use RefreshDatabase;

    public function testAllowedGoogleEmailCanLogIn(): void
    {
        config(['playpipe.admin.allowed_emails' => ['admin@example.test']]);
        $this->fakeGoogleUser(email: 'Admin@Example.Test');

        $response = $this->get(route('auth.google.callback'));

        self::assertSame(url('/admin'), $response->headers->get('Location'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'Admin@Example.Test',
            'google_id' => 'google-admin',
            'avatar_url' => 'https://example.test/avatar.png',
        ]);
    }

    public function testAllowedGoogleEmailReturnsToIntendedListenUrl(): void
    {
        config(['playpipe.admin.allowed_emails' => ['admin@example.test']]);
        $this->fakeGoogleUser(email: 'admin@example.test');

        $this
            ->withSession(['url.intended' => url('/listen/episodes')])
            ->get(route('auth.google.callback'))
            ->assertRedirect(url('/listen/episodes'));

        $this->assertAuthenticated();
    }

    public function testAllowedGoogleEmailReturnsToIntendedAdminUrl(): void
    {
        config(['playpipe.admin.allowed_emails' => ['admin@example.test']]);
        $this->fakeGoogleUser(email: 'admin@example.test');

        $this
            ->withSession(['url.intended' => url('/admin')])
            ->get(route('auth.google.callback'))
            ->assertRedirect(url('/admin'));

        $this->assertAuthenticated();
    }

    public function testUnsafeIntendedUrlFallsBackToAdminDashboard(): void
    {
        config(['playpipe.admin.allowed_emails' => ['admin@example.test']]);

        foreach ([
            'https://evil.example.test/listen',
            url('/auth/google/callback'),
            url('/logout'),
            url('/api/episodes'),
        ] as $intendedUrl) {
            Auth::logout();
            $this->fakeGoogleUser(email: 'admin@example.test');

            $this
                ->withSession(['url.intended' => $intendedUrl])
                ->get(route('auth.google.callback'))
                ->assertRedirect(url('/admin'));
        }
    }

    public function testDisallowedGoogleEmailCannotLogIn(): void
    {
        config(['playpipe.admin.allowed_emails' => ['admin@example.test']]);
        $this->fakeGoogleUser(email: 'other@example.test');

        $this->get(route('auth.google.callback'))->assertForbidden();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'other@example.test',
        ]);
    }

    public function testEmailMatchingIsCaseInsensitiveAndTrimsConfiguredWhitespace(): void
    {
        config(['playpipe.admin.allowed_emails' => ['  ADMIN@example.test  ']]);
        $this->fakeGoogleUser(email: 'admin@EXAMPLE.test');

        $this->get(route('auth.google.callback'))->assertRedirect(url('/admin'));

        $this->assertAuthenticated();
    }

    public function testEmptyAllowListDeniesGoogleLogin(): void
    {
        config(['playpipe.admin.allowed_emails' => []]);
        $this->fakeGoogleUser(email: 'admin@example.test');

        $this->get(route('auth.google.callback'))->assertForbidden();

        $this->assertGuest();
    }

    public function testAllowedLoggedInUserCanAccessFilamentPanel(): void
    {
        config(['playpipe.admin.allowed_emails' => ['admin@example.test']]);
        $user = User::factory()->create(['email' => 'admin@example.test']);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    public function testAdminDashboardShowsListenAppNavigationAndWidget(): void
    {
        config(['playpipe.admin.allowed_emails' => ['admin@example.test']]);
        config(['playpipe.upload.storage_disk' => 's3']);
        $episode = Episode::factory()->create([
            'title' => '最新エピソード',
            'published_at' => '2026-05-31 07:00:00',
        ]);

        $this
            ->actingAs(User::factory()->create(['email' => 'admin@example.test']))
            ->get('/admin')
            ->assertOk()
            ->assertSee('Listen App')
            ->assertSee(route('listen.home'), false);

        $component = Livewire::test(ListenAppWidget::class);
        $component->assertSee('Listen App');
        $component->assertSee($episode->title);
        $component->assertSee('1');
        $component->assertSee('s3');
        $component->assertSee('OPEN LISTEN');
        $component->assertSee(route('listen.home'));
    }

    public function testUserRemovedFromAllowListCannotAccessFilamentPanel(): void
    {
        config(['playpipe.admin.allowed_emails' => ['admin@example.test']]);
        $user = User::factory()->create(['email' => 'admin@example.test']);

        self::assertTrue($user->canAccessPanel(Filament::getPanel('admin')));

        config(['playpipe.admin.allowed_emails' => []]);

        self::assertFalse($user->canAccessPanel(Filament::getPanel('admin')));

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function testGoogleOauthRedirectStartsSocialiteFlow(): void
    {
        Socialite::fake('google');

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://socialite.fake/google/authorize');
    }

    public function testGoogleOauthTokensAreNotStored(): void
    {
        config(['playpipe.admin.allowed_emails' => ['admin@example.test']]);
        $this->fakeGoogleUser(email: 'admin@example.test');

        $this->get(route('auth.google.callback'))->assertRedirect(url('/admin'));

        self::assertFalse(Schema::hasColumn('users', 'google_token'));
        self::assertFalse(Schema::hasColumn('users', 'google_refresh_token'));
        self::assertFalse(Schema::hasColumn('users', 'oauth_token'));
        self::assertFalse(Schema::hasColumn('users', 'oauth_refresh_token'));
    }

    private function fakeGoogleUser(string $email): void
    {
        $socialiteUser = (new SocialiteUser())
            ->setRaw([
                'sub' => 'google-admin',
                'email' => $email,
                'name' => 'Admin User',
                'picture' => 'https://example.test/avatar.png',
                'access_token' => 'fake-access-token',
                'refresh_token' => 'fake-refresh-token',
            ])
            ->map([
                'id' => 'google-admin',
                'nickname' => null,
                'name' => 'Admin User',
                'email' => $email,
                'avatar' => 'https://example.test/avatar.png',
            ]);

        Socialite::fake('google', $socialiteUser);
    }
}
