<?php

namespace App\Services;

use App\Enums\Company\BankTransactionCategory;
use App\Enums\Trains\TrainCarStatus;
use App\Enums\Trains\TrainCarType;
use App\Enums\Trains\TrainStatus;
use App\Models\Company;
use App\Models\Depot;
use App\Models\Line;
use App\Models\Train;
use App\Models\TrainCar;
use App\Models\TrainModel;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class TrainPurchaseService
{
    public function __construct(
        protected BankService $bankService
    ) {}

    /**
     * @throws Throwable
     */
    public function purchaseTrain(Company $company, TrainModel $model, Depot $depot): Train
    {
        // 1. Contrôle des fonds
        $account = $company->bankAccount;
        if (! $account || $account->balance < $model->base_price) {
            throw new Exception(__('Fonds insuffisants sur votre compte bancaire.'));
        }

        // 2. Contrôle de compatibilité Dépôt <-> Lignes <-> Modèle
        if (! $this->isDepotCompatibleWithModel($depot, $model)) {
            throw new Exception(__("Incompatibilité : la rame :model n'est associée à aucune des lignes raccordées au dépôt :depot.", [
                'model' => $model->name,
                'depot' => $depot->name,
            ]));
        }

        return DB::transaction(function () use ($company, $model, $depot) {
            // 3. Débit bancaire
            $this->bankService->debitAccount(
                company: $company,
                amount: (float) $model->base_price,
                category: BankTransactionCategory::TRAIN_PURCHASE,
                description: "Achat de la rame {$model->name} pour le dépôt {$depot->name}"
            );

            // 4. Création de la rame (Train)
            $trainCount = Train::where('company_id', $company->id)
                    ->where('train_model_id', $model->id)
                    ->count() + 1;

            $train = Train::create([
                'company_id' => $company->id,
                'train_model_id' => $model->id,
                'current_depot_id' => $depot->id,
                'name' => "Rame {$model->name} #{$trainCount}",
                'status' => TrainStatus::IN_DEPOT,
                'total_kilometers' => 0,
            ]);

            // 5. Instanciation des caisses individuelles (TrainCar)
            $pattern = $model->composition_pattern ?? [TrainCarType::MOTOR, TrainCarType::TRAILER, TrainCarType::MOTOR];

            foreach ($pattern as $index => $carType) {
                $carNumber = $this->generateCarNumber($model, $carType, $company->id);

                TrainCar::create([
                    'company_id' => $company->id,
                    'train_model_id' => $model->id,
                    'train_id' => $train->id,
                    'current_depot_id' => $depot->id,
                    'number_code' => $carNumber,
                    'type' => $carType,
                    'position' => $index + 1,
                    'wear_percentage' => 0.00,
                    'status' => TrainCarStatus::IN_SERVICE,
                ]);
            }

            return $train;
        });
    }

    /**
     * Vérifie si le modèle de train correspond au train_model_id d'au moins une ligne reliée au dépôt.
     */
    public function isDepotCompatibleWithModel(Depot $depot, TrainModel $model): bool
    {
        $lines = $depot->relationLoaded('lines') ? $depot->lines : $depot->lines()->get();

        // Si aucune ligne n'est reliée au dépôt, on autorise l'achat
        if ($lines->isEmpty()) {
            return true;
        }

        // La rame est compatible si au moins une ligne du dépôt utilise ce train_model_id (ou si train_model_id est null)
        return $lines->contains(function (Line $line) use ($model) {
            return $line->train_model_id === null || (int) $line->train_model_id === (int) $model->id;
        });
    }

    private function generateCarNumber(TrainModel $model, TrainCarType $type, int $companyId): string
    {
        $prefix = $type === TrainCarType::MOTOR ? '5' : '6';
        $count = TrainCar::where('company_id', $companyId)
                ->where('train_model_id', $model->id)
                ->where('type', $type)
                ->count() + 1;

        return $prefix . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }
}
