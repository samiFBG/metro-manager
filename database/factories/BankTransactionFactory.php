<?php

namespace Database\Factories;

use App\Enums\Company\BankTransactionCategory;
use App\Enums\Company\BankTransactionType;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankTransactionFactory extends Factory
{
    protected $model = BankTransaction::class;

    public function definition(): array
    {
        return [
            'bank_account_id' => BankAccount::factory(),
            'type' => BankTransactionType::CREDIT,
            'category' => BankTransactionCategory::CAPITAL_INJECTION,
            'amount' => 5000000.00,
            'description' => $this->faker->sentence(),
        ];
    }
}
