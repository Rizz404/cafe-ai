<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seating_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->json('translations')->nullable();
            $table->string('area_type')->default('indoor'); // indoor | outdoor | bar | private
            $table->unsignedTinyInteger('min_guests')->default(1);
            $table->unsignedTinyInteger('max_guests')->default(4);
            $table->json('features')->nullable();
            $table->decimal('reservation_fee', 12, 2)->default(0); // deposit per table, 0 = free
            $table->decimal('minimum_spend', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['cafe_id', 'slug']);
        });

        Schema::create('seating_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seating_area_id')->constrained()->cascadeOnDelete();
            $table->string('image_path')->nullable();
            $table->string('image_url')->nullable();
            $table->json('tags')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        /**
         * Mock table-booking data: the one table the AI is NEVER allowed to
         * answer from memory. A POS / reservation-system adapter can replace
         * it later without touching the AI or knowledge-base layers.
         */
        Schema::create('table_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seating_area_id')->constrained()->cascadeOnDelete();
            $table->date('reservation_date');
            $table->string('time_slot', 5); // HH:MM, the start of the slot
            $table->unsignedSmallInteger('total_tables');
            $table->unsignedSmallInteger('reserved_tables')->default(0);
            $table->timestamps();
            $table->unique(['seating_area_id', 'reservation_date', 'time_slot'], 'table_inventory_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_inventory');
        Schema::dropIfExists('seating_images');
        Schema::dropIfExists('seating_areas');
    }
};
