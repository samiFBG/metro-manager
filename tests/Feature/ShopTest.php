<?php

namespace Tests\Feature;

use App\Enums\Company\BankAccountType;
use App\Enums\Company\BankTransactionCategory;
use App\Enums\Company\BankTransactionType;
use App\Enums\Trains\TrainCarType;
use App\Enums\Trains\TrainStatus;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\Depot;
use App\Models\Train;
use App\Models\TrainModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;
    private BankAccount $bankAccount;
    private Depot $depot;
    private TrainModel $trainModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create([
            'onboarding_completed_at' => now(),
        ]);

        $this->company = Company::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Sytral Rail',
        ]);

        $bank = Bank::factory()->create();

        // Création du compte (sans colonne balance)
        $this->bankAccount = BankAccount::create([
            'company_id' => $this->company->id,
            'bank_id' => $bank->id,
            'account_number' => 'FR7612345678901234567890123',
            'type' => BankAccountType::CHECKING,
        ]);

        // Transaction initiale créant le solde de 5 M€
        BankTransaction::create([
            'bank_account_id' => $this->bankAccount->id,
            'type' => BankTransactionType::CREDIT,
            'category' => BankTransactionCategory::CAPITAL_INJECTION,
            'amount' => 5000000.00,
            'description' => 'Capital initial',
        ]);

        $this->depot = Depot::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Ateliers de la Poudrette',
            'max_capacity' => 15,
        ]);

        $this->trainModel = TrainModel::factory()->create([
            'name' => 'MPL 75',
            'base_price' => 3500000.00,
            'capacity_per_car' => 126,
            'composition_pattern' => [TrainCarType::MOTOR, TrainCarType::TRAILER, TrainCarType::MOTOR],
            'car_width' => 2.89,
        ]);
    }

    public function test_guest_cannot_access_shop_page(): void
    {
        $response = $this->get(route('shop'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_shop_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('shop'));

        $response->assertOk();
    }

    public function test_user_can_purchase_train_and_debit_bank_account(): void
    {
        $this->actingAs($this->user);

        Livewire::test('pages::shop')
            ->set('selected_depot_id', $this->depot->id)
            ->call('purchase', $this->trainModel->id)
            ->assertHasNoErrors();

        // 1. Vérification création de la rame
        $this->assertDatabaseHas('trains', [
            'company_id' => $this->company->id,
            'train_model_id' => $this->trainModel->id,
            'current_depot_id' => $this->depot->id,
            'status' => TrainStatus::IN_DEPOT,
        ]);

        $train = Train::where('company_id', $this->company->id)->first();
        $this->assertNotNull($train);

        // 2. Vérification des caisses M-R-M
        $this->assertCount(3, $train->cars);

        // 3. Vérification de la transaction de débit enregistrée
        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $this->bankAccount->id,
            'type' => BankTransactionType::DEBIT,
            'category' => BankTransactionCategory::TRAIN_PURCHASE,
            'amount' => 3500000.00,
        ]);

        // 4. Vérification du solde calculé restant (5 M€ - 3.5 M€ = 1.5 M€)
        $this->assertEquals(1500000.00, $this->bankAccount->fresh()->balance);
    }

    public function test_purchase_fails_if_insufficient_funds(): void
    {
        $expensiveModel = TrainModel::factory()->create([
            'name' => 'Train Futuriste',
            'base_price' => 10000000.00,
        ]);

        $this->actingAs($this->user);

        Livewire::test('pages::shop')
            ->set('selected_depot_id', $this->depot->id)
            ->call('purchase', $expensiveModel->id);

        $this->assertDatabaseMissing('trains', [
            'train_model_id' => $expensiveModel->id,
        ]);

        $this->assertEquals(5000000.00, $this->bankAccount->fresh()->balance);
    }

    public function test_purchase_fails_if_no_depot_selected(): void
    {
        $this->actingAs($this->user);

        Livewire::test('pages::shop')
            ->set('selected_depot_id', null)
            ->call('purchase', $this->trainModel->id);

        $this->assertDatabaseMissing('trains', [
            'company_id' => $this->company->id,
        ]);
    }
}
