<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The cafe's menu. Prices live here and nowhere else: the AI Barista may
     * only quote a price it has just read through a tool call.
     */
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->string('category'); // coffee | non_coffee | tea | food | snack | dessert
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->json('translations')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('image_url')->nullable();
            $table->json('tags')->nullable(); // vegan, vegetarian, gluten_free, signature, spicy, ...
            $table->json('allergens')->nullable(); // milk, gluten, egg, nuts, soy, ...
            $table->string('serving')->nullable(); // hot | iced | hot_iced | shareable ...
            $table->unsignedSmallInteger('calories')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_sold_out')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['cafe_id', 'slug']);
            $table->index(['cafe_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
