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
            $table->boolean('is_active')->default(true)->after('status');
            $table->string('target_type')->default('all')->after('is_active'); // all, manual, group, random, excel
            $table->text('target_value')->nullable()->after('target_type'); // comma separated numbers or label name or number count
            $table->string('attachment_path')->nullable()->after('message_template');
            $table->string('attachment_type')->nullable()->after('attachment_path'); // image, video, audio, file
            $table->integer('interval_value')->nullable()->after('scheduled_day_of_month'); // every X minutes/hours
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'target_type', 'target_value', 'attachment_path', 'attachment_type', 'interval_value']);
        });
    }
};
