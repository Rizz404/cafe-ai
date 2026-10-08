<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cafes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('custom_domain')->nullable()->unique();
            $table->text('description')->nullable();
            $table->json('translations')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('instagram')->nullable();
            $table->string('timezone')->default('Asia/Jakarta');
            $table->string('currency', 3)->default('IDR');
            $table->string('default_locale', 2)->default('id');
            $table->time('opening_time')->default('08:00');
            $table->time('closing_time')->default('22:00');
            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('public_status')->default('draft');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('cafe_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('staff'); // owner | staff
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['cafe_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cafe_users');
        Schema::dropIfExists('cafes');
    }
};
