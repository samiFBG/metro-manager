<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('train_cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('train_model_id')->constrained()->cascadeOnDelete();

            // NULL si la voiture est en réserve au dépôt
            $table->foreignId('train_id')->nullable()->constrained('trains')->nullOnDelete();
            $table->foreignId('current_depot_id')->nullable()->constrained('depots')->nullOnDelete();

            $table->string('number_code'); // Ex: "501", "601"
            $table->string('type'); // M = Motrice, R = Remorque
            $table->unsignedTinyInteger('position')->nullable(); // Position dans la rame (1, 2, 3...)
            $table->decimal('wear_percentage', 5, 2)->default(0.00);

            $table->string('status')->default('available');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('train_cars');
    }
};
