<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer la relation 1-N directe sur la table depots
        Schema::table('depots', function (Blueprint $table) {
            if (Schema::hasColumn('depots', 'line_id')) {
                $table->dropForeign(['line_id']);
                $table->dropColumn('line_id');
            }
        });

        // Créer la table pivot Many-to-Many
        Schema::create('depot_line', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('line_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['depot_id', 'line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depot_line');

        Schema::table('depots', function (Blueprint $table) {
            $table->foreignId('line_id')->nullable()->constrained();
        });
    }
};
