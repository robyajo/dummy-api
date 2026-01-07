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
        Schema::table('cache', function (Blueprint $table) {
            $table->string('key', 500)->change();
        });

        Schema::table('cache_locks', function (Blueprint $table) {
            $table->string('key', 500)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cache', function (Blueprint $table) {
            $table->string('key')->change();
        });

        Schema::table('cache_locks', function (Blueprint $table) {
            $table->string('key')->change();
        });
    }
};
