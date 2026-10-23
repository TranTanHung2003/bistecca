<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_plan_guests', function (Blueprint $table) {
            $table->unsignedSmallInteger('adult_count')->nullable()->after('guest_count');
            $table->unsignedSmallInteger('child_count')->nullable()->after('adult_count');
        });
    }

    public function down(): void
    {
        Schema::table('meal_plan_guests', function (Blueprint $table) {
            $table->dropColumn(['adult_count', 'child_count']);
        });
    }
};
