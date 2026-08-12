<?php

namespace Database\Seeders;

use App\Models\Line;
use App\Models\Station;
use App\Models\TrainModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MetroSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('data/metro_lyon_import.json');

        if (! File::exists($jsonPath)) {
            $this->command->error("Le fichier '{$jsonPath}' est introuvable ! Placer le dans database/data/.");

            return;
        }

        $linesData = json_decode(File::get($jsonPath), true);

        if (empty($linesData)) {
            $this->command->error('Le fichier JSON est vide ou mal formé.');

            return;
        }

        // Correspondance exacte entre les lignes TCL et tes modèles en BDD
        $lineTrainModels = [
            'A' => 'MPL 75',
            'B' => 'MPL 16',
            'C' => 'MCL 80',
            'D' => 'MPL 85',
        ];

        DB::transaction(function () use ($linesData, $lineTrainModels) {
            foreach ($linesData as $data) {
                $code = $data['code'];

                // 1. Filtrage du modèle de train correspondant dans ta table train_models
                $targetModelName = $lineTrainModels[$code] ?? 'MPL 75';
                $trainModel = TrainModel::where('name', $targetModelName)->first();

                if (! $trainModel) {
                    // Fallback si le nom exact n'est pas trouvé
                    $trainModel = TrainModel::first();
                    $this->command->warn("  ⚠ Modèle '{$targetModelName}' non trouvé pour la ligne {$code}, utilisation de '{$trainModel->name}'");
                }

                // 2. Recherche et résolution du fichier GeoJSON
                $rawGeojsonPath = $data['geo_json_path'];
                $possiblePaths = [
                    base_path($rawGeojsonPath),
                    database_path('data/'.$rawGeojsonPath),
                    base_path('geojson/'.basename($rawGeojsonPath)),
                ];

                $geojsonContent = null;
                foreach ($possiblePaths as $path) {
                    if (File::exists($path)) {
                        $geojsonContent = json_decode(File::get($path), true);
                        break;
                    }
                }

                if (! $geojsonContent) {
                    $this->command->warn("  ⚠ GeoJSON introuvable pour la ligne {$code}");
                }

                // 3. Insertion / Mise à jour de la Ligne
                $line = Line::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $data['name'],
                        'color_hex' => $data['color_hex'],
                        'geo_json_path' => $geojsonContent,
                        'train_model_id' => $trainModel->id,
                        'min_cars_per_train' => $data['min_cars_per_train'] ?? 2,
                        'max_cars_per_train' => $data['max_cars_per_train'] ?? 4,
                    ]
                );

                $this->command->info("Ligne '{$line->code}' enregistrée (Modèle : {$trainModel->name}).");

                // 4. Synchronisation des Stations
                foreach ($data['stations'] as $stationData) {
                    Station::updateOrCreate(
                        [
                            'line_id' => $line->id,
                            'order' => $stationData['order'],
                        ],
                        [
                            'name' => $stationData['name'],
                            'latitude' => $stationData['latitude'],
                            'longitude' => $stationData['longitude'],
                            'passenger_flow_weight' => $stationData['passenger_flow_weight'] ?? 1.0,
                        ]
                    );
                }

                $this->command->line('  └─ '.count($data['stations']).' stations liées.');
            }
        });

        $this->command->info('Importation BDD terminée avec succès !');
    }
}
