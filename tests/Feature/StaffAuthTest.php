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
}
