<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plan_guests', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->index();
            $table->string('room', 20);
            $table->string('room_type', 20);
            $table->unsignedSmallInteger('guest_count')->nullable();
            $table->string('guest_name');
            $table->date('arrival');
            $table->date('departure');
            $table->unsignedSmallInteger('nights')->nullable();
            $table->string('travel_agent')->nullable();
            $table->string('rate_code')->nullable();
            $table->string('nationality')->nullable();
            $table->string('meal_plan')->nullable();
            $table->string('breakfast_code')->nullable();
            $table->boolean('is_child')->default(false);
            $table->text('source_line')->nullable();
            $table->timestamps();

            $table->index(['report_date', 'room']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plan_guests');
    }
};
