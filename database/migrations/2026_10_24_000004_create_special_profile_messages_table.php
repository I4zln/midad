<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_profile_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('message_key', 64);
            $table->string('title', 160);
            $table->text('body');
            $table->string('sender_name', 120);
            $table->string('sender_role', 120);
            $table->timestamp('shown_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'message_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_profile_messages');
    }
};
