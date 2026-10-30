<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Profile pictures and covers live in the database, so a host whose disk is wiped on every deploy (Render's free plan)
// keeps them. Stored as base64 text: portable across SQLite and PostgreSQL without binary bindings.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10);
            $table->string('mime', 40);
            $table->unsignedInteger('size');
            $table->longText('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
