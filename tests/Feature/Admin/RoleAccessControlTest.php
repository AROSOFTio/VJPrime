<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_manager_can_access_content_but_not_user_management_or_reports(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_CONTENT_MANAGER,
        ]);

        $this->actingAs($manager)
            ->get(route('admin.movies.index'))
            ->assertOk();

        $this->actingAs($manager)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('admin.reports.index'))
            ->assertForbidden();
    }

    public function test_finance_manager_can_access_reports_but_not_content_or_user_management(): void
    {
        $financeManager = User::factory()->create([
            'role' => User::ROLE_FINANCE_MANAGER,
        ]);

        $this->actingAs($financeManager)
            ->get(route('admin.reports.index'))
            ->assertOk();
        $this->actingAs($financeManager)
            ->get(route('admin.reports.revenue'))
            ->assertOk();
        $this->actingAs($financeManager)
            ->get(route('admin.reports.content'))
            ->assertOk();
        $this->actingAs($financeManager)
            ->get(route('admin.reports.users'))
            ->assertOk();

        $this->actingAs($financeManager)
            ->get(route('admin.movies.index'))
            ->assertForbidden();

        $this->actingAs($financeManager)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_contributor_cannot_access_reports_or_user_management(): void
    {
        $contributor = User::factory()->create([
            'role' => User::ROLE_CONTRIBUTOR,
        ]);

        $this->actingAs($contributor)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($contributor)
            ->get(route('admin.reports.index'))
            ->assertForbidden();
    }

    public function test_regular_user_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
