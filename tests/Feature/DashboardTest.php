<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The dashboard is the landing page for every authenticated role.
     * If this breaks, nobody can do anything, so it's worth a smoke test.
     */
    public function test_admin_can_view_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/')
            ->assertOk()
            ->assertSee('Dashboard');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_deactivated_user_is_logged_out(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => false]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}