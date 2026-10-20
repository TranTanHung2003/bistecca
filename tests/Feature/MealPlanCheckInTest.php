<?php

namespace Tests\Feature;

use App\Models\MealPlanGuest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealPlanCheckInTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_be_checked_in_for_buffet(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'fb']));

        $guest = MealPlanGuest::create([
            'report_date' => '2026-09-24',
            'room' => '101',
            'room_type' => 'STD',
            'guest_name' => 'Nguyễn Văn An',
            'arrival' => '2026-09-23',
            'departure' => '2026-09-25',
            'meal_plan' => 'Breakfast Include',
        ]);

        $response = $this->postJson(route('meal-plans.check-in', $guest));

        $response->assertOk()->assertJson(['checked_in' => true]);
        $this->assertNotNull($guest->fresh()->buffet_checked_in_at);
        $this->assertSame('checked_in', $guest->fresh()->buffet_check_in_status);
    }

    public function test_staff_can_manually_add_a_late_buffet_guest(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'fo']));

        $response = $this->post(route('meal-plans.manual-store'), [
            'report_date' => '2026-10-20',
            'room' => '508',
            'guest_name' => 'Nguyen Van An',
            'nationality' => 'Vietnamese',
        ]);

        $response->assertRedirect(route('meal-plans.index', ['date' => '2026-10-20']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('meal_plan_guests', [
            'room' => '508',
            'guest_name' => 'Nguyen Van An',
            'nationality' => 'Vietnamese',
            'room_type' => 'Manual',
            'meal_plan' => 'Breakfast Include',
            'buffet_check_in_status' => null,
        ]);

        $guest = MealPlanGuest::where('room', '508')->firstOrFail();
        $this->assertSame('2026-10-20', $guest->report_date->toDateString());
        $this->assertSame('2026-10-20', $guest->arrival->toDateString());
        $this->assertSame('2026-10-20', $guest->departure->toDateString());
        $this->assertNull($guest->buffet_checked_in_at);
        $this->assertNull($guest->buffet_check_in_status);

        $this->get(route('meal-plans.index', ['date' => '2026-10-20']))
            ->assertOk()
            ->assertSee('Nguyen Van An')
            ->assertSee('508')
            ->assertSee('Vietnamese');
    }

    public function test_it_cannot_manually_add_a_late_buffet_guest(): void
    {
        $it = User::factory()->create(['role' => 'it']);

        $this->actingAs($it)
            ->get(route('meal-plans.index'))
            ->assertOk()
            ->assertDontSee('id="open-manual-entry"', false)
            ->assertDontSee('id="manual-entry-dialog"', false);

        $this->actingAs($it)
            ->post(route('meal-plans.manual-store'), [
                'report_date' => '2026-10-20',
                'room' => '509',
                'guest_name' => 'IT Guest',
                'nationality' => 'Vietnamese',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('meal_plan_guests', [
            'room' => '509',
            'guest_name' => 'IT Guest',
        ]);
    }

    public function test_fb_cannot_manually_add_a_late_buffet_guest(): void
    {
        $fb = User::factory()->create(['role' => 'fb']);

        $this->actingAs($fb)
            ->get(route('meal-plans.index'))
            ->assertOk()
            ->assertDontSee('id="open-manual-entry"', false)
            ->assertDontSee('id="manual-entry-dialog"', false);

        $this->actingAs($fb)
            ->post(route('meal-plans.manual-store'), [
                'report_date' => '2026-10-20',
                'room' => '510',
                'guest_name' => 'FB Guest',
                'nationality' => 'Vietnamese',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('meal_plan_guests', [
            'room' => '510',
            'guest_name' => 'FB Guest',
        ]);
    }

    public function test_manual_buffet_guest_requires_date_room_name_and_nationality(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'fo']));

        $this->post(route('meal-plans.manual-store'), [])
            ->assertSessionHasErrors(['report_date', 'room', 'guest_name', 'nationality']);
    }

    public function test_no_breakfast_can_be_recorded_as_a_check_in_status(): void
    {
        $this->actingAs(User::factory()->create());

        $guest = MealPlanGuest::create([
            'report_date' => '2026-09-24',
            'room' => '102',
            'room_type' => 'STD',
            'guest_name' => 'Tran Thi B',
            'arrival' => '2026-09-23',
            'departure' => '2026-09-25',
            'meal_plan' => 'No breakfast',
        ]);

        $response = $this->postJson(route('meal-plans.check-in', $guest), ['status' => 'no_breakfast']);

        $response->assertOk()->assertJson(['checked_in' => true, 'status' => 'no_breakfast']);
        $this->assertSame('no_breakfast', $guest->fresh()->buffet_check_in_status);
    }

    public function test_birthdays_matching_the_selected_report_day_are_highlighted(): void
    {
        $this->actingAs(User::factory()->create());

        $birthdayGuest = MealPlanGuest::create([
            'report_date' => '2026-09-24',
            'room' => '101',
            'room_type' => 'STD',
            'guest_name' => 'Birthday Guest',
            'birthday' => '1990-09-24',
            'arrival' => '2026-09-23',
            'departure' => '2026-09-25',
            'meal_plan' => 'Breakfast Include',
        ]);
        MealPlanGuest::create([
            'report_date' => '2026-09-24',
            'room' => '102',
            'room_type' => 'STD',
            'guest_name' => 'Regular Guest',
            'birthday' => '1990-09-25',
            'arrival' => '2026-09-23',
            'departure' => '2026-09-25',
            'meal_plan' => 'Breakfast Include',
        ]);

        $this->get(route('meal-plans.index', ['date' => '2026-09-24']))
            ->assertOk()
            ->assertSee('Sinh nhật hôm nay')
            ->assertSee('Birthday Guest')
            ->assertSee('24/09/1990')
            ->assertSee('Ngày sinh: 24/09/1990')
            ->assertSee('href="#guest-'.$birthdayGuest->id.'"', false)
            ->assertSee('id="guest-'.$birthdayGuest->id.'"', false)
            ->assertSee('data-birthday="true"', false)
            ->assertDontSee('data-birthday="true" data-guest-row="2"', false);
    }
}
