<?php

use App\Models\Company;
use App\Models\Depot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;


new class extends Component {
    public string $company_name = '';
    public string $primary_color = '#d97706';
    public ?int $selected_depot_id = null;

    // Presets de couleurs
    public array $colorPresets = [
        '#d97706', // Amber (Sytral)
        '#dc2626', // Rouge (Ligne A)
        '#2563eb', // Bleu (Ligne B)
        '#059669', // Vert (Ligne D)
        '#7c3aed', // Violet
        '#18181b', // Sombre
    ];

    public function mount(): void
    {
        if (Auth::user()->onboarding_completed_at) {
            $this->redirect(route('game'), navigate: true);
            return;
        }

        $this->selected_depot_id = Depot::first()?->id;
    }

    #[Computed]
    public function depots()
    {
        return Depot::with('line')->whereNull('company_id')->orWhere('id', $this->selected_depot_id)->get();
    }

    public function completeOnboarding(): void
    {
        $this->validate([
            'company_name'      => ['required', 'string', 'min:3', 'max:50', 'unique:companies,name'],
            'primary_color'     => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'selected_depot_id' => ['required', 'exists:depots,id'],
        ], [
            'company_name.required'      => __('Veuillez saisir un nom pour votre entreprise.'),
            'company_name.unique'        => __('Ce nom d\'entreprise est déjà pris.'),
            'selected_depot_id.required' => __('Veuillez choisir votre dépôt de départ.'),
        ]);

        $user = Auth::user();

        DB::transaction(function () use ($user) {
            $company = Company::create([
                'user_id' => $user->id,
                'name'    => $this->company_name,
                'color'   => $this->primary_color,
                'budget'  => 5000000,
            ]);

            $depot = Depot::find($this->selected_depot_id);
            $depot->update(['company_id' => $company->id]);

            $user->forceFill([
                'onboarding_completed_at' => now(),
            ])->save();
        });

        $this->redirect(route('game'), navigate: true);
    }
}; ?>

<div class="flex flex-col justify-between min-h-[calc(100vh-4rem)] h-full w-full max-w-7xl mx-auto p-4 md:p-8">
    <form wire:submit="completeOnboarding" class="flex flex-col justify-between h-full flex-1 space-y-8">

        {{-- En-tête Pleine Page --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-neutral-800 pb-6">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <flux:badge color="amber" icon="sparkles" size="sm">{{ __('Configuration Initiale') }}</flux:badge>
                </div>
                <flux:heading size="2xl" level="1">{{ __('Fondation de votre Réseau') }}</flux:heading>
                <flux:subheading size="lg">{{ __('Configurez l\'identité de votre entreprise ferroviaire avant de prendre les commandes.') }}</flux:subheading>
            </div>

            {{-- Statut du budget --}}
            <div class="flex items-center gap-3 bg-emerald-950/40 border border-emerald-500/20 px-5 py-3 rounded-2xl">
                <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400">
                    <flux:icon.banknotes class="w-6 h-6" />
                </div>
                <div>
                    <div class="text-xs text-neutral-400">{{ __('Capital de lancement') }}</div>
                    <div class="text-lg font-bold font-mono text-emerald-400">5 000 000 €</div>
                </div>
            </div>
        </div>

        {{-- Grille principale prenant tout l'espace central --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 my-auto py-2">

            {{-- Colonne Gauche : Identité & Couleurs --}}
            <div class="lg:col-span-5 space-y-6">
                <flux:card class="space-y-6 bg-neutral-900/60 border-neutral-800">
                    <flux:heading size="lg">{{ __('Identité du Réseau') }}</flux:heading>

                    {{-- Nom de l'entreprise --}}
                    <flux:input
                        wire:model="company_name"
                        :label="__('Nom du réseau / entreprise')"
                        :placeholder="__('Ex: TCL Lyon, Transports Lyonnais...')"
                        icon="building-office"
                        required
                    />

                    {{-- Livrée / Couleurs --}}
                    <flux:field>
                        <flux:label>{{ __('Couleur officielle de la livrée') }}</flux:label>
                        <div class="flex flex-wrap items-center gap-3 mt-2">
                            @foreach($colorPresets as $preset)
                                <button
                                    type="button"
                                    wire:click="$set('primary_color', '{{ $preset }}')"
                                    class="w-9 h-9 rounded-full border-2 transition-all {{ $primary_color === $preset ? 'border-white scale-110 ring-2 ring-amber-500/50' : 'border-transparent opacity-70 hover:opacity-100' }}"
                                    style="background-color: {{ $preset }}"
                                ></button>
                            @endforeach

                            <div class="h-6 w-px bg-neutral-800 mx-1"></div>

                            {{-- Custom Picker --}}
                            <div class="flex items-center gap-2 bg-neutral-800/80 px-3 py-1.5 rounded-xl border border-neutral-700">
                                <input type="color" wire:model.live="primary_color" class="w-6 h-6 rounded cursor-pointer border-0 bg-transparent" />
                                <span class="text-xs font-mono text-neutral-300 uppercase">{{ $primary_color }}</span>
                            </div>
                        </div>
                        <flux:error name="primary_color" />
                    </flux:field>
                </flux:card>
            </div>

            {{-- Colonne Droite : Sélection des Dépôts --}}
            <div class="lg:col-span-7">
                <flux:card class="space-y-4 bg-neutral-900/60 border-neutral-800 h-full flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <flux:heading size="lg">{{ __('Choix du Dépôt Principal') }}</flux:heading>
                            <span class="text-xs text-neutral-400">{{ __('Centre d\'exploitation initial') }}</span>
                        </div>
                        <flux:subheading class="mb-4">{{ __('Sélectionnez le dépôt qui hébergera vos premières rames de métro.') }}</flux:subheading>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($this->depots as $depot)
                                <div
                                    wire:click="$set('selected_depot_id', {{ $depot->id }})"
                                    class="p-5 rounded-2xl border cursor-pointer transition-all flex flex-col justify-between space-y-4 {{ $selected_depot_id === $depot->id ? 'border-amber-500 bg-amber-500/10 ring-2 ring-amber-500/30' : 'border-neutral-800 bg-neutral-900/80 hover:border-neutral-700' }}"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="font-semibold text-base text-white">{{ $depot->name }}</div>
                                        @if($depot->line)
                                            <flux:badge size="sm" color="amber">{{ $depot->line->code }}</flux:badge>
                                        @endif
                                    </div>

                                    <div class="flex items-center justify-between text-xs text-neutral-400 border-t border-neutral-800/80 pt-3">
                                        <span>{{ __('Capacité d\'accueil') }}</span>
                                        <span class="font-bold text-neutral-200">{{ $depot->max_capacity }} rames</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <flux:error name="selected_depot_id" />
                    </div>
                </flux:card>
            </div>

        </div>

        {{-- Barre d'action inférieure (Action Bar / Bottom Bar) --}}
        <div class="flex items-center justify-between border-t border-neutral-800 pt-6 mt-auto">


            <flux:button type="submit" variant="primary" icon-trailing="arrow-right" class="w-full sm:w-auto px-8 py-3 text-base">
                {{ __('Inaugurer le réseau') }}
            </flux:button>
        </div>

    </form>
</div>
