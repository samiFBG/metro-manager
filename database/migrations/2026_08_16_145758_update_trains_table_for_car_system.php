<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trains', function (Blueprint $table) {
            $table->string('name')->after('current_depot_id'); // Ex: "Rame 601"
            $table->dropColumn(['cars_count', 'wear_percentage']);
        });
    }

    public function down(): void
    {
        Schema::table('trains', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->unsignedInteger('cars_count')->default(3);
            $table->decimal('wear_percentage', 5, 2)->default(0.00);
        });
    }
};
