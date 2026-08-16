<?php

namespace Database\Factories;

use App\Enums\Company\BankAccountType;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'bank_id' => Bank::factory(),
            'account_number' => 'FR76'.$this->faker->numerify('###################'),
            'type' => BankAccountType::CHECKING,
        ];
    }
}
