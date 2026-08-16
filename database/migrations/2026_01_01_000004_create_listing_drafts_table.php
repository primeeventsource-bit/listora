<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 16)->unique();
            $table->string('kind');
            $table->string('mode');
            $table->string('owner_name');
            $table->string('owner_email');
            $table->string('phone')->nullable();
            $table->string('resort_name')->nullable();
            $table->string('club_name')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 64)->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('sleeps')->nullable();
            $table->unsignedInteger('points')->nullable();
            $table->unsignedTinyInteger('week_number')->nullable();
            $table->string('season')->nullable();
            $table->unsignedInteger('price')->nullable();
            $table->string('price_unit')->nullable();
            $table->string('plan')->default('featured');
            $table->string('status')->default('pending_verification');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_drafts');
    }
};
