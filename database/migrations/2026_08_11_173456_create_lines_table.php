<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lines', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // line_A, line_B, line_C, line_D
            $table->string('name');
            $table->string('color_hex', 7);
            $table->text('geo_json_path')->nullable();
            $table->foreignId('train_model_id')->constrained('train_models'); // Modèle 1:1 dédié
            $table->unsignedInteger('min_cars_per_train')->default(2);
            $table->unsignedInteger('max_cars_per_train')->default(4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lines');
    }
};
