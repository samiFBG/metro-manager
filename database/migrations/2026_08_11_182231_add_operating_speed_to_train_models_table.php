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
        Schema::table('train_models', function (Blueprint $table) {
            $table->unsignedInteger('operating_speed')->default(75)->after('max_speed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('train_models', function (Blueprint $table) {
            $table->dropColumn('operating_speed');
        });
    }
};
