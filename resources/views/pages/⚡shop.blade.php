<?php

use App\Enums\Trains\TrainCarType;
use App\Models\Depot;
use App\Models\TrainModel;
use App\Services\TrainPurchaseService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Catalogue de Rames')] #[Layout('layouts::game')] class extends Component {
    public ?int $selected_depot_id = null;

    public function mount(): void
    {
        $this->selected_depot_id = $this->depots->first()?->id;
    }

    #[Computed]
    public function depots()
    {
        return Auth::user()?->company?->depots()->with('lines')->get() ?? collect();
    }

    #[Computed]
    public function currentDepot(): ?Depot
    {
        return $this->depots->firstWhere('id', $this->selected_depot_id);
    }

    #[Computed]
    public function models()
    {
        return TrainModel::all();
    }

    /**
     * Teste la compatibilité d'un modèle avec le dépôt actif
     */
    public function isCompatible(TrainModel $model): bool
    {
        $depot = $this->depots->firstWhere('id', $this->selected_depot_id);

        if (! $depot || $depot->lines->isEmpty()) {
            return true;
        }

        return $depot->lines->contains(function ($line) use ($model) {
            return $line->train_model_id === null || (int) $line->train_model_id === (int) $model->id;
        });
    }

    public function purchase(int $modelId, TrainPurchaseService $purchaseService): void
    {
        $user = Auth::user();
        $company = $user?->company;

        if (! $company || ! $this->selected_depot_id) {
            Flux::toast(text: __('Veuillez sélectionner un dépôt valide.'), variant: 'danger');
            return;
        }

        $depot = Depot::where('company_id', $company->id)->find($this->selected_depot_id);
        $model = TrainModel::findOrFail($modelId);

        if (! $this->isCompatible($model)) {
            Flux::toast(
                text: __("Incompatibilité technique : :model ne peut pas circuler sur les lignes de ce dépôt.", ['model' => $model->name]),
                variant: 'danger'
            );
            return;
        }

        $currentBalance = (float) ($company->bankAccount?->balance ?? $company->total_balance ?? 0);

        if ($currentBalance < (float) $model->base_price) {
            Flux::toast(text: __('Fonds insuffisants sur votre compte bancaire.'), variant: 'danger');
            return;
        }

        try {
            $train = $purchaseService->purchaseTrain($company, $model, $depot);
            $this->dispatch('bank-account-updated');

            Flux::toast(
                text: __("La :name a été livrée avec succès au dépôt :depot !", [
                    'name' => $train->name,
                    'depot' => $depot->name,
                ]),
                variant: 'success'
            );
        } catch (\Throwable $e) {
            if (app()->environment('testing')) {
                throw $e;
            }

            Flux::toast(text: $e->getMessage(), variant: 'danger');
        }
    }
}; ?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">
    {{-- En-tête --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border border-zinc-200/80 dark:border-zinc-800/80 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md rounded-2xl p-6 shadow-sm">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <flux:badge color="indigo" size="sm" icon="shopping-cart">{{ __('Boutique') }}</flux:badge>
            </div>
            <flux:heading size="xl" level="1">{{ __('Catalogue du Matériel Roulant') }}</flux:heading>
            <flux:subheading>{{ __('Commandez vos nouvelles rames et choisissez le dépôt d\'attachement.') }}</flux:subheading>
        </div>

        <div class="w-full md:w-72">
            <flux:select wire:model.live="selected_depot_id" :label="__('Dépôt de livraison')" placeholder="-- Choisir un dépôt --">
                @foreach($this->depots as $depot)
                    <flux:select.option :value="$depot->id">{{ $depot->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Grille des Modèles --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($this->models as $model)
            @php $compatible = $this->isCompatible($model); @endphp
            <flux:card class="flex flex-col justify-between space-y-6 {{ ! $compatible ? 'opacity-60 bg-zinc-50 dark:bg-zinc-900/30' : '' }}">
                <div class="space-y-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <flux:heading size="lg">{{ $model->name }}</flux:heading>
                            <span class="text-xs text-zinc-500 font-mono">Gabarit : {{ $model->car_width }}m</span>
                        </div>
                        <flux:badge color="{{ $compatible ? 'amber' : 'red' }}" size="sm">
                            {{ count($model->composition_pattern ?? []) }} caisses
                        </flux:badge>
                    </div>

                    @if(! $compatible)
                        <div class="text-xs text-red-600 dark:text-red-400 font-medium bg-red-500/10 p-2 rounded-lg border border-red-500/20">
                            {{ __('Incompatible avec les lignes relier au dépôt sélectionné.') }}
                        </div>
                    @endif

                    <div class="space-y-2 text-xs divide-y divide-zinc-100 dark:divide-zinc-800">
                        <div class="flex justify-between py-1.5">
                            <span class="text-zinc-500">{{ __('Composition théorique') }}</span>
                            <span class="font-mono font-bold text-amber-600 dark:text-amber-400">
                                {{ collect($model->composition_pattern ?? [])->map(fn ($type) => $type instanceof TrainCarType ? $type->value : $type)->implode(' - ') }}
                            </span>
                        </div>
                        <div class="flex justify-between py-1.5">
                            <span class="text-zinc-500">{{ __('Vitesse d\'exploitation') }}</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $model->operating_speed }} km/h</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between gap-4">
                    <div>
                        <div class="text-[10px] text-zinc-400 uppercase font-medium">{{ __('Prix unitaire') }}</div>
                        <div class="text-lg font-bold font-mono text-emerald-600 dark:text-emerald-400">
                            {{ number_format($model->base_price, 2, ',', ' ') }} €
                        </div>
                    </div>

                    <flux:button
                        wire:click="purchase({{ $model->id }})"
                        variant="primary"
                        icon="shopping-cart"
                        :disabled="!$selected_depot_id || !$compatible">
                        {{ __('Acheter') }}
                    </flux:button>
                </div>
            </flux:card>
        @endforeach
    </div>
</div>
