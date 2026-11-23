<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coffee_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_hash', 64);
            $table->uuid('request_key');
            $table->string('item_name');
            $table->decimal('price', 12, 2);
            $table->string('currency', 8);
            $table->string('status', 20)->default('queued');
            $table->timestamp('starts_at');
            $table->timestamp('finishes_at');
            $table->timestamps();

            $table->unique(['cafe_id', 'session_hash', 'request_key']);
            $table->index(['cafe_id', 'finishes_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coffee_orders');
    }
};
