<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_replies', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('auto_replies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('auto_replies', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
