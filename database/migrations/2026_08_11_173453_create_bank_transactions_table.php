<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit']);
            $table->enum('category', [
                'ticket_sales',
                'train_purchase',
                'electricity_bill',
                'maintenance_cost',
                'loan_disbursement',
                'loan_repayment',
            ]);
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
