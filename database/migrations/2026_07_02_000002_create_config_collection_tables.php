<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin Settings & Configuration subsystem — Layer 2 (managed collections).
 *
 * Entity tables the operations console CRUDs directly.
 *
 * Vaytoven's payment_processors, fee_schedules, tax_rules and
 * cancellation_policies are all absent, and deliberately so: Listora takes no
 * payment on the website, holds no merchant or gateway credentials, and is
 * never a party to what an owner and a traveler settle between themselves.
 * There is no processor to route to, no commission to configure, and no
 * refund window to encode, so none of those tables exist to be breached.
 *
 * What is left is editorial: the vocabularies the listing wizard and browse
 * filters render from, and the message templates operations sends.
 *
 *  - email/sms templates are versioned: editing a template creates a new
 *    row with version+1; used versions are never mutated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 96);
            $table->string('name', 128);
            $table->string('subject', 255)->nullable();
            $table->text('body_markdown');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('active')->default(true);
            $table->json('variables')->nullable(); // documented merge tags
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['key', 'version'], 'email_templates_key_version_unique');
        });

        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 96);
            $table->string('name', 128);
            $table->text('body_text');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('active')->default(true);
            $table->json('variables')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['key', 'version'], 'sms_templates_key_version_unique');
        });

        // The three vocabularies the listing wizard and browse filters render
        // from. They lived in config/listora.php, which meant adding a region
        // was a deploy. Rows here so operations owns them.
        Schema::create('listing_regions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique('listing_regions_slug_unique');
            $table->string('label', 96);
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('listing_amenities', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique('listing_amenities_slug_unique');
            $table->string('label', 96);
            $table->string('icon', 64)->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('vacation_clubs', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique('vacation_clubs_slug_unique');
            $table->string('name', 128);
            $table->boolean('points_based')->default(true);
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacation_clubs');
        Schema::dropIfExists('listing_amenities');
        Schema::dropIfExists('listing_regions');
        Schema::dropIfExists('sms_templates');
        Schema::dropIfExists('email_templates');
    }
};
