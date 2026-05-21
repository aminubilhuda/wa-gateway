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
        Schema::table('auto_replies', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('response_message');
            $table->string('attachment_type')->nullable()->after('attachment_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auto_replies', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_type']);
        });
    }
};
