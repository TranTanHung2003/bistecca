<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_plan_guests', function (Blueprint $table) {
            $table->string('buffet_check_in_status', 30)->nullable()->after('buffet_checked_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('meal_plan_guests', function (Blueprint $table) {
            $table->dropColumn('buffet_check_in_status');
        });
    }
};
