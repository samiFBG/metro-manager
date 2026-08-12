<?php

namespace Database\Seeders;

use App\Models\TrainModel;
use Illuminate\Database\Seeder;

class TrainModelSeeder extends Seeder
{
    public function run(): void
    {
        // Optionnel : vide la table pour éviter les doublons lors du seeding
        TrainModel::query()->delete();

        TrainModel::insert([
            [
                'name' => 'MPL 75',
                'base_price' => 3500000.00,
                'capacity_per_car' => 126,
                'electricity_cost_per_km' => 12.50,
                'maintenance_cost_factor' => 1.5,
                'max_speed' => 90,
                'operating_speed' => 75, // Exploitation TCL sur la Ligne A
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'MPL 16',
                'base_price' => 6200000.00,
                'capacity_per_car' => 150,
                'electricity_cost_per_km' => 8.50,
                'maintenance_cost_factor' => 1.0,
                'max_speed' => 90,
                'operating_speed' => 75, // Exploitation TCL sur la Ligne B
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'MCL 80',
                'base_price' => 4500000.00,
                'capacity_per_car' => 126,
                'electricity_cost_per_km' => 15.00,
                'maintenance_cost_factor' => 2.0,
                'max_speed' => 80,
                'operating_speed' => 35, // Bridé à 35 km/h sur crémaillère (Ligne C)
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'MPL 85',
                'base_price' => 5000000.00,
                'capacity_per_car' => 130,
                'electricity_cost_per_km' => 11.00,
                'maintenance_cost_factor' => 1.2,
                'max_speed' => 75,
                'operating_speed' => 75, // Exploitation MAGGALY sur la Ligne D
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
