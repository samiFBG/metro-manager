<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Exécution des seeders dans le bon ordre
        $this->call([
            BankSeeder::class,
            TrainModelSeeder::class,
            MetroSeeder::class,   // Doit passer avant DepotSeeder pour créer les lignes A et B
            DepotSeeder::class,  // Associe UTMA aux lignes A et B
        ]);
    }
}
