<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_trying_to_open_meal_plan(): void
    {
        $response = $this->get('/meal-plans');

        $response->assertRedirect('/login');
    }

    public function test_staff_can_login_and_access_checkin_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'staff@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/meal-plans');
        $this->assertAuthenticatedAs($user);
        $this->get('/meal-plans')->assertOk();
    }

    public function test_fb_can_check_in_but_cannot_import_meal_plans(): void
    {
        $fb = User::factory()->create(['role' => 'fb']);

        $this->actingAs($fb)
            ->get('/meal-plans')
            ->assertOk()
            ->assertDontSee('Nhập XML');

        $this->post('/meal-plans')
            ->assertForbidden();
    }

    public function test_fo_can_see_meal_plan_import_form(): void
    {
        $fo = User::factory()->create(['role' => 'fo']);

        $this->actingAs($fo)
            ->get('/meal-plans')
            ->assertOk()
            ->assertSee('Nhập XML');
    }

    public function test_accountant_can_view_statistics_but_cannot_upload_or_add_guests(): void
    {
        $accountant = User::factory()->create(['role' => 'acc']);

        $this->actingAs($accountant)
            ->get('/meal-plans')
            ->assertOk()
            ->assertSee('Tổng khách')
            ->assertDontSee('Nhập XML')
            ->assertDontSee('id="open-manual-entry"', false);

        $this->post('/meal-plans')
            ->assertForbidden();

        $this->post(route('meal-plans.manual-store'))
            ->assertForbidden();
    }
}
