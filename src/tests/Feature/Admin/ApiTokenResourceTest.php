<?php

namespace Tests\Feature\Admin;

use App\ApiTokens\ApiTokenService;
use App\Filament\Resources\ApiTokens\ApiTokenResource;
use App\Filament\Resources\ApiTokens\Pages\ListApiTokens;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @internal
 */
class ApiTokenResourceTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthorizedAdminCanAccessApiTokensPage(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create(['email' => 'api-user@example.test']);
        $token = $user->createToken('playpipe-api', [ApiTokenService::ABILITY_EPISODES_WRITE])->accessToken;

        $this->get(ApiTokenResource::getUrl('index'))->assertOk();

        $component = Livewire::test(ListApiTokens::class);
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->assertCanSeeTableRecords([$token]);
        $component->assertSee('api-user@example.test');
        $component->assertSee('playpipe-api');
    }

    public function testNonAdminUserCannotAccessApiTokensPage(): void
    {
        config(['playpipe.admin.allowed_emails' => ['admin@example.test']]);

        $this->actingAs(User::factory()->create(['email' => 'user@example.test']));

        $this->get(ApiTokenResource::getUrl('index'))->assertForbidden();
    }

    public function testCreateApiTokenActionCreatesTokenAndShowsPlainTextTokenOnce(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create(['email' => 'api-user@example.test']);

        $component = Livewire::test(ListApiTokens::class);
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->assertActionExists('createApiToken');
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->callAction('createApiToken', [
            'user_id' => $user->id,
            'token_name' => 'playpipe-api',
            'abilities' => [ApiTokenService::ABILITY_EPISODES_WRITE],
        ]);

        $token = PersonalAccessToken::query()->firstOrFail();

        self::assertSame('playpipe-api', $token->name);
        self::assertSame([ApiTokenService::ABILITY_EPISODES_WRITE], $token->abilities);
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->assertActionMounted('showCreatedApiToken');
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->assertMountedActionModalSee('Copy this token now. It will not be shown again.');
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->assertMountedActionModalSee('api-user@example.test');
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->assertMountedActionModalSee('playpipe-api');
    }

    public function testTokenListDoesNotDisplayPlainTextTokenOrTokenHash(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create(['email' => 'api-user@example.test']);
        $createdToken = $user->createToken('playpipe-api', [ApiTokenService::ABILITY_EPISODES_WRITE]);

        $component = Livewire::test(ListApiTokens::class);

        $component->assertSee('playpipe-api');
        $component->assertDontSee($createdToken->plainTextToken);
        $component->assertDontSee($createdToken->accessToken->token);
    }

    public function testRevokeTokenActionDeletesSelectedToken(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create(['email' => 'api-user@example.test']);
        $token = $user->createToken('playpipe-api', [ApiTokenService::ABILITY_EPISODES_WRITE])->accessToken;

        $component = Livewire::test(ListApiTokens::class);
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->assertTableActionExists('revoke', record: $token);
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->callTableAction('revoke', $token);

        self::assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->id,
        ]);
    }

    public function testRevokeAllApiTokensActionDeletesAllTokensForUser(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create(['email' => 'api-user@example.test']);
        $otherUser = User::factory()->create(['email' => 'other-api-user@example.test']);
        $user->createToken('playpipe-api', [ApiTokenService::ABILITY_EPISODES_WRITE]);
        $user->createToken('playpipe-api-secondary', [ApiTokenService::ABILITY_EPISODES_READ]);
        $otherUser->createToken('playpipe-api', [ApiTokenService::ABILITY_FEEDBACK_SYNC]);

        $component = Livewire::test(ListApiTokens::class);
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->assertActionExists('revokeAllApiTokens');
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->callAction('revokeAllApiTokens', [
            'user_id' => $user->id,
        ]);

        self::assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->count());
        self::assertSame(1, DB::table('personal_access_tokens')->where('tokenable_id', $otherUser->id)->count());
    }

    /**
     * 管理者としてログインする。
     */
    private function actingAsAdmin(): void
    {
        config(['playpipe.admin.allowed_emails' => ['admin@example.test']]);

        $this->actingAs(User::factory()->create(['email' => 'admin@example.test']));
    }
}
