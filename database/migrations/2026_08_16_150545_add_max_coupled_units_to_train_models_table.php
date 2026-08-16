<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('train_models', function (Blueprint $table) {
            // 1 = US uniquement (ex: MPL75), 2 = UM2 autorisée (ex: MPL16)
            $table->unsignedTinyInteger('max_coupled_units')->default(1)->after('car_width');
        });
    }

    public function down(): void
    {
        Schema::table('train_models', function (Blueprint $table) {
            $table->dropColumn('max_coupled_units');
        });
    }
};
