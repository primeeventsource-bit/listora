<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 16)->unique();          // LST-4821
            $table->string('slug')->unique();

            // what is being advertised
            $table->string('kind');                             // home | points | weeks
            $table->string('mode');                             // rent | own
            $table->string('title');
            $table->text('headline')->nullable();
            $table->longText('description');

            // where
            $table->foreignId('resort_id')->nullable()->constrained()->nullOnDelete();
            $table->string('resort_name')->nullable();
            $table->string('club_name')->nullable();
            $table->string('city');
            $table->string('state', 64)->nullable();
            $table->string('country')->default('United States');
            $table->string('region');

            // unit facts
            $table->string('unit_type')->nullable();            // 2-Bedroom Oceanfront
            $table->unsignedTinyInteger('bedrooms')->default(0);
            $table->decimal('bathrooms', 3, 1)->default(1);
            $table->unsignedTinyInteger('sleeps')->default(2);

            // club points / weeks specifics
            $table->unsignedInteger('points')->nullable();
            $table->unsignedTinyInteger('week_number')->nullable();
            $table->string('season')->nullable();               // Platinum, Gold, Prime Summer
            $table->string('usage')->nullable();                // Annual, Biennial (even), Floating
            $table->date('available_from')->nullable();
            $table->date('available_to')->nullable();

            // money
            $table->decimal('price', 12, 2);
            $table->string('price_unit')->default('total');     // total | night | week | point
            $table->unsignedInteger('maintenance_fee')->nullable();
            $table->string('currency', 3)->default('USD');

            // marketplace metadata
            $table->string('plan')->default('essential');       // essential | featured | premier
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_verified')->default(true);
            $table->string('owner_name');
            $table->string('owner_since')->nullable();
            $table->string('response_time')->default('within a day');
            $table->json('amenities')->nullable();
            $table->json('photos')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('saves')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['kind', 'mode']);
            $table->index('region');
            $table->index('price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
