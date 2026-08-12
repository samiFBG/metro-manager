<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('bank_id')->constrained('banks');
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('remaining_amount', 15, 2);
            $table->decimal('daily_repayment_amount', 12, 2);
            $table->enum('status', ['active', 'paid_off', 'defaulted'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
