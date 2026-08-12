<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('train_models', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // MPL 75, MPL 16, MCL 80, MPL 85
            $table->decimal('base_price', 15, 2);
            $table->unsignedInteger('capacity_per_car');
            $table->decimal('electricity_cost_per_km', 8, 2);
            $table->float('maintenance_cost_factor')->default(1.0);
            $table->unsignedInteger('max_speed')->default(80);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('train_models');
    }
};
