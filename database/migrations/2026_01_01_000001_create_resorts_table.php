<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resorts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('club')->nullable();      // e.g. "Coral Cay Club"
            $table->string('city');
            $table->string('state', 64)->nullable();
            $table->string('country')->default('United States');
            $table->string('region');                // Caribbean, Hawaii, Mountain West...
            $table->text('summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resorts');
    }
};
