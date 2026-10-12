<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_plan_guests', function (Blueprint $table): void {
            $table->text('travel_agent')->nullable()->change();
            $table->text('source_line')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('meal_plan_guests', function (Blueprint $table): void {
            $table->string('travel_agent')->nullable()->change();
            $table->text('source_line')->nullable()->change();
        });
    }
};
