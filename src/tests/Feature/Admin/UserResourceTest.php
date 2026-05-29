<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @internal
 */
class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthorizedAdminCanAccessUsersPage(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create(['email' => 'api-user@example.test']);

        $this->get(UserResource::getUrl('index'))->assertOk();

        $component = Livewire::test(ListUsers::class);
        // @phpstan-ignore-next-line Filament の Livewire test macro を使用する。
        $component->assertCanSeeTableRecords([$user]);
        $component->assertSee('api-user@example.test');
    }

    public function testNonAdminUserCannotAccessUsersPage(): void
    {
        config(['playpipe.admin.allowed_emails' => ['admin@example.test']]);

        $this->actingAs(User::factory()->create(['email' => 'user@example.test']));

        $this->get(UserResource::getUrl('index'))->assertForbidden();
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
