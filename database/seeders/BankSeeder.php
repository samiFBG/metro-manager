<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Nettoie la table avant l'insertion
        Bank::query()->delete();

        Bank::insert([
            [
                'name' => 'Crédit Mutuel Métropolitain',
                'description' => 'Banque locale idéale pour les jeunes entreprises. Taux bas mais plafond limité.',
                'interest_rate' => 2.50,
                'max_loan_amount' => 5000000.00,
                'repayment_duration_days' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Banque Publique d\'Investissement (BPI)',
                'description' => 'Soutien institutionnel aux grands projets d\'infrastructure et à l\'automatisation.',
                'interest_rate' => 3.80,
                'max_loan_amount' => 15000000.00,
                'repayment_duration_days' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rhône Capital Finance',
                'description' => 'Fonds privé prêtant de fortes sommes rapidement, mais avec des intérêts plus élevés.',
                'interest_rate' => 6.20,
                'max_loan_amount' => 40000000.00,
                'repayment_duration_days' => 90,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
