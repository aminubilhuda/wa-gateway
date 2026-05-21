<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->foreignId('auto_reply_id')->constrained('auto_replies')->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['phone_number', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_sessions');
    }
};
