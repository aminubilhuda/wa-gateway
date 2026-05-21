<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('schedule_type')->default('once')->after('status'); // once, daily, weekly, monthly
            $table->string('scheduled_time')->nullable()->after('schedule_type'); // e.g. "08:00"
            $table->integer('scheduled_day_of_week')->nullable()->after('scheduled_time'); // 1-7 (1 = Monday)
            $table->integer('scheduled_day_of_month')->nullable()->after('scheduled_day_of_week'); // 1-31
            $table->timestamp('last_run_at')->nullable()->after('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['schedule_type', 'scheduled_time', 'scheduled_day_of_week', 'scheduled_day_of_month', 'last_run_at']);
        });
    }
};
