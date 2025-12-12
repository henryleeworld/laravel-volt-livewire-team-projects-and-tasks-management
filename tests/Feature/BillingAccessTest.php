<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Tests\TestCase;

class BillingAccessTest extends TestCase
{
    public function test_guests_cannot_access_billing_page()
    {
        $this->get(route('billing.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_users_can_access_billing_page()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->asAdmin()->create([
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee(__('Billing'));
    }

    public function test_non_admin_users_cannot_access_billing_page()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->asUser()->create([
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertForbidden();
    }

    public function test_viewer_users_cannot_access_billing_page()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->asViewer()->create([
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertForbidden();
    }

    public function test_billing_page_shows_current_plan()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->asAdmin()->create([
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee(__('Current Plan'))
            ->assertSee(__('Free'));
    }

    public function test_billing_page_shows_usage_statistics()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->asAdmin()->create([
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee(__('Usage'))
            ->assertSee(__('Tasks'));
    }
}
