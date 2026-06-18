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
        Schema::table('translations', function (Blueprint $table) {
            $table->text('search_blob')->nullable()->after('tags');
        });
    }

    /**
     * Reverse the migrations.
     */h wa
    public function down(): void
    {
        Schema::table('translations', function (Blueprint $table) {
            $table->dropColumn('search_blob');
        });
    }
};
