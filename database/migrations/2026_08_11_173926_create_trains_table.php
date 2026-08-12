<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('train_model_id')->constrained('train_models');
            $table->foreignId('current_line_id')->nullable()->constrained('lines')->nullOnDelete();
            $table->foreignId('current_depot_id')->nullable()->constrained('depots')->nullOnDelete();
            $table->unsignedInteger('cars_count')->default(2);
            $table->enum('status', [
                'in_depot',
                'in_siding',
                'in_service',
                'under_maintenance',
                'in_transit',
            ])->default('in_depot');
            $table->float('wear_percentage')->default(0.0);
            $table->decimal('total_kilometers', 12, 2)->default(0.00);
            $table->timestamp('delivery_arrival_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trains');
    }
};
