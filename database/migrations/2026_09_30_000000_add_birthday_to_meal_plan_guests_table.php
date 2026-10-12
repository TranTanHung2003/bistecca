<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_plan_guests', function (Blueprint $table) {
            $table->date('birthday')->nullable()->after('guest_name');
        });
    }

    public function down(): void
    {
        Schema::table('meal_plan_guests', function (Blueprint $table) {
            $table->dropColumn('birthday');
        });
    }
};
