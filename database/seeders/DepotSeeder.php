<?php

namespace Database\Seeders;

use App\Models\Depot;
use App\Models\Line;
use Illuminate\Database\Seeder;

class DepotSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Récupération des modèles de lignes
        $lineA = Line::where('code', 'A')->first();
        $lineB = Line::where('code', 'B')->first();
        $lineC = Line::where('code', 'C')->first();
        $lineD = Line::where('code', 'D')->first();

        // 2. Nettoyage des anciens dépôts séparés A/B s'ils existent
        Depot::whereIn('name', [
            'Ateliers de la Poudrette (UTM-A)',
            'Ateliers de la Poudrette (UTM-B)'
        ])->delete();

        // 3. Définition des dépôts (sans la colonne 'line_id')
        $depotsData = [
            [
                'name'              => 'Ateliers de la Poudrette (UTM-A / UTM-B)',
                'type'              => 'workshop',
                'latitude'          => 45.7626979,
                'longitude'         => 4.921482,
                'max_capacity'      => 47,
                'maintenance_slots' => 9,
                'lines'             => array_filter([$lineA?->id, $lineB?->id]),
            ],
            [
                'name'              => "Ateliers d'Hénon / Cuire (UTM-C)",
                'type'              => 'workshop',
                'latitude'          => 45.7817626,
                'longitude'         => 4.8278927,
                'max_capacity'      => 8,
                'maintenance_slots' => 2,
                'lines'             => array_filter([$lineC?->id]),
            ],
            [
                'name'              => 'Ateliers du Thioley (UTM-D)',
                'type'              => 'workshop',
                'latitude'          => 45.7141525,
                'longitude'         => 4.8924262,
                'max_capacity'      => 20,
                'maintenance_slots' => 4,
                'lines'             => array_filter([$lineD?->id]),
            ],
        ];

        // 4. Insertion et synchronisation avec la table pivot depot_line
        foreach ($depotsData as $data) {
            $lineIds = $data['lines'];
            unset($data['lines']);

            $depot = Depot::updateOrCreate(
                ['name' => $data['name']],
                $data
            );

            // Synchronisation de la relation Many-to-Many
            $depot->lines()->sync($lineIds);
        }
    }
}
