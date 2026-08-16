<?php

namespace App\Services;

use App\Enums\Company\BankTransactionCategory;
use App\Enums\Company\BankTransactionType;
use App\Models\Company;
use App\Models\BankTransaction;
use Exception;

class BankService
{
    /**
     * Effectue un débit sur le compte courant principal de l'entreprise.
     * @throws Exception
     */
    public function debitAccount(Company $company, float $amount, BankTransactionCategory $category, string $description): BankTransaction
    {
        $account = $company->bankAccount;

        if (!$account) {
            throw new Exception(__("Aucun compte bancaire trouvé pour cette entreprise."));
        }

        if ($account->balance < $amount) {
            throw new Exception(__("Fonds insuffisants sur votre compte bancaire pour réaliser cet achat."));
        }

        return BankTransaction::create([
            'bank_account_id' => $account->id,
            'type' => BankTransactionType::DEBIT,
            'category' => $category,
            'amount' => $amount,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
