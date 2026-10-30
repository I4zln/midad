<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// «نسيت كلمة المرور» when email cannot be sent: the request waits in the admin panel until a reset link is handed over.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_help_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
            $table->index(['handled_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_help_requests');
    }
};
