<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('train_models', function (Blueprint $table) {
            $table->json('composition_pattern')->nullable()->after('operating_speed');
            $table->decimal('car_width', 4, 2)->default(2.89)->after('composition_pattern');
        });
    }

    public function down(): void
    {
        Schema::table('train_models', function (Blueprint $table) {
            $table->dropColumn(['composition_pattern', 'car_width']);
        });
    }
};
