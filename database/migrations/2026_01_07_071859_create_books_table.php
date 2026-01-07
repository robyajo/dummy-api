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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->string('isbn', 20)->unique()->nullable();
            $table->string('publisher', 255)->nullable();
            $table->date('published_date')->nullable();
            $table->integer('pages')->nullable();
            $table->string('language', 10)->default('id');
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->string('cover_image', 255)->nullable();
            $table->string('cover_image_url', 255)->nullable();
            $table->json('categories')->nullable();
            $table->json('authors')->nullable();
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('rating_count')->default(0);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
