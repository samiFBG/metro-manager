<?php

namespace Tests\Feature;

use App\Enums\Company\BankTransactionCategory;
use App\Enums\Company\DepotType;
use App\Models\Bank;
use App\Models\Company;
use App\Models\Depot;
use App\Models\Line;
use App\Models\TrainModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    private Depot $depot;
    private Bank $bank;
    private Line $line;
    private TrainModel $trainModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // 1. Création du modèle de train dédié (ex: MPL 75)
        $this->trainModel = TrainModel::factory()->create([
            'name' => 'MPL 75',
            'composition_pattern' => ['M', 'R', 'M'],
            'car_width' => 2.89,
        ]);

        // 2. Création de la ligne de test rattachée au modèle de train
        $this->line = Line::factory()->semiAutomated()->create([
            'code' => 'A',
            'name' => 'Ligne A',
            'train_model_id' => $this->trainModel->id,
        ]);

        // 3. Création du dépôt
        $this->depot = Depot::factory()->create([
            'name' => 'Ateliers de la Poudrette (UTM-A)',
            'type' => DepotType::WORKSHOP,
            'latitude' => 45.7626979,
            'longitude' => 4.921482,
            'max_capacity' => 25,
            'maintenance_slots' => 5,
        ]);

        // 4. Association dépôt <-> ligne
        $this->depot->lines()->attach($this->line->id);

        // 5. Création de la banque
        $this->bank = Bank::factory()->create([
            'name' => 'BoursoBank',
            'interest_rate' => 2.50,
            'max_loan_amount' => 5000000,
            'repayment_duration_days' => 365,
        ]);
    }

    public function test_guest_cannot_access_onboarding_page(): void
    {
        $response = $this->get(route('onboarding'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_onboarding_page(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('onboarding'));

        $response->assertOk();
    }

    public function test_user_who_completed_onboarding_is_redirected_to_game(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test('pages::onboarding')
            ->assertRedirect(route('game'));
    }

    public function test_onboarding_creates_company_bank_account_and_assigns_depot(): void
    {
        Storage::fake('public');
        $logo = UploadedFile::fake()->image('logo.png');

        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $this->actingAs($user);

        Livewire::test('pages::onboarding')
            // Étape 1 : Identité & Logo
            ->set('company_name', 'Sytral Rail')
            ->set('logo', $logo)
            ->call('nextStep')
            ->assertHasNoErrors()
            ->assertSet('step', 2)

            // Étape 2 : Banque
            ->set('selected_bank_id', $this->bank->id)
            ->call('nextStep')
            ->assertHasNoErrors()
            ->assertSet('step', 3)

            // Étape 3 : Dépôt & Finalisation
            ->set('selected_depot_id', $this->depot->id)
            ->call('completeOnboarding')
            ->assertHasNoErrors()
            ->assertRedirect(route('game'));

        // Assertions BDD
        $this->assertDatabaseHas('companies', [
            'user_id' => $user->id,
            'name' => 'Sytral Rail',
        ]);

        $company = Company::where('name', 'Sytral Rail')->first();

        $this->assertNotNull($company->logo_url);
        Storage::disk('public')->assertExists($company->logo_url);

        $this->assertDatabaseHas('bank_accounts', [
            'company_id' => $company->id,
            'bank_id' => $this->bank->id,
        ]);

        $this->assertDatabaseHas('bank_transactions', [
            'amount' => 5000000.00,
            'category' => BankTransactionCategory::CAPITAL_INJECTION,
        ]);

        $this->assertNotNull($user->fresh()->onboarding_completed_at);
        $this->assertEquals($company->id, $this->depot->fresh()->company_id);
    }

    public function test_onboarding_supports_cropped_logo_base64_upload(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $this->actingAs($user);

        Livewire::test('pages::onboarding')
            ->set('company_name', 'TCL Express')
            ->set('cropped_logo', $base64Image)
            ->call('nextStep')
            ->assertHasNoErrors()
            ->assertSet('step', 2)
            ->set('selected_bank_id', $this->bank->id)
            ->call('nextStep')
            ->set('selected_depot_id', $this->depot->id)
            ->call('completeOnboarding')
            ->assertHasNoErrors();

        $company = Company::where('name', 'TCL Express')->first();
        $this->assertNotNull($company->logo_url);
        Storage::disk('public')->assertExists($company->logo_url);
    }

    public function test_step_3_displays_depot_with_line_automation_and_dedicated_train_model(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $this->actingAs($user);

        Livewire::test('pages::onboarding')
            ->set('step', 3)
            ->assertSee($this->depot->name)
            ->assertSee($this->line->code)
            ->assertSee('Semi-auto')
            ->assertSee('Conducteur requis')
            ->assertSee('MPL 75'); // Vérifie le rendu du modèle dédié
    }

    public function test_step_1_validation_fails_if_company_name_is_too_short(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $this->actingAs($user);

        Livewire::test('pages::onboarding')
            ->set('company_name', 'AB')
            ->set('logo', UploadedFile::fake()->image('logo.png'))
            ->call('nextStep')
            ->assertHasErrors(['company_name' => 'min'])
            ->assertSet('step', 1);
    }

    public function test_step_1_validation_fails_if_logo_is_missing(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $this->actingAs($user);

        Livewire::test('pages::onboarding')
            ->set('company_name', 'Sytral Rail')
            ->set('logo', null)
            ->set('cropped_logo', null)
            ->call('nextStep')
            ->assertHasErrors(['logo' => 'required'])
            ->assertSet('step', 1);
    }

    public function test_step_2_validation_fails_if_no_bank_selected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $this->actingAs($user);

        Livewire::test('pages::onboarding')
            ->set('company_name', 'Sytral Rail')
            ->set('logo', UploadedFile::fake()->image('logo.png'))
            ->call('nextStep')
            ->set('selected_bank_id', null)
            ->call('nextStep')
            ->assertHasErrors(['selected_bank_id' => 'required'])
            ->assertSet('step', 2);
    }

    public function test_user_can_navigate_backwards_in_wizard(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $this->actingAs($user);

        Livewire::test('pages::onboarding')
            ->set('company_name', 'Sytral Rail')
            ->set('logo', UploadedFile::fake()->image('logo.png'))
            ->call('nextStep')
            ->assertSet('step', 2)
            ->call('previousStep')
            ->assertSet('step', 1);
    }

    public function test_user_without_onboarding_cannot_access_game_route(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('game'));

        $response->assertRedirect(route('onboarding'));
    }
}
