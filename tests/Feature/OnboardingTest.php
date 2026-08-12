<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Depot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    private Depot $depot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Création d'un dépôt de test réaliste
        $this->depot = Depot::factory()->create([
            'name' => 'Ateliers de la Poudrette (UTM-A)',
            'type' => 'workshop',
            'latitude' => 45.7626979,
            'longitude' => 4.921482,
            'max_capacity' => 25,
            'maintenance_slots' => 5,
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

    public function test_onboarding_creates_company_and_assigns_depot(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $this->actingAs($user);

        Livewire::test('pages::onboarding')
            ->set('company_name', 'Sytral Rail')
            ->set('primary_color', '#10b981')
            ->set('selected_depot_id', $this->depot->id)
            ->call('completeOnboarding')
            ->assertHasNoErrors()
            ->assertRedirect(route('game'));

        // Vérifications en BDD
        $this->assertDatabaseHas('companies', [
            'user_id' => $user->id,
            'name' => 'Sytral Rail',
        ]);

        $company = Company::where('name', 'Sytral Rail')->first();

        $this->assertNotNull($user->fresh()->onboarding_completed_at);
        $this->assertEquals($company->id, $this->depot->fresh()->company_id);
    }

    public function test_onboarding_validation_fails_if_company_name_is_too_short(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $this->actingAs($user);

        Livewire::test('pages::onboarding')
            ->set('company_name', 'AB')
            ->set('primary_color', '#10b981')
            ->set('selected_depot_id', $this->depot->id)
            ->call('completeOnboarding')
            ->assertHasErrors(['company_name' => 'min']);
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
