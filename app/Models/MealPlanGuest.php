<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealPlanGuest extends Model
{
    protected $fillable = [
        'report_date', 'room', 'room_type', 'guest_count', 'adult_count', 'child_count', 'guest_name', 'birthday',
        'arrival', 'departure', 'nights', 'travel_agent', 'rate_code',
        'nationality', 'meal_plan', 'breakfast_code', 'is_child', 'source_line', 'buffet_checked_in_at', 'buffet_check_in_status',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'arrival' => 'date',
            'departure' => 'date',
            'birthday' => 'date',
            'is_child' => 'boolean',
            'buffet_checked_in_at' => 'datetime',
        ];
    }
}
