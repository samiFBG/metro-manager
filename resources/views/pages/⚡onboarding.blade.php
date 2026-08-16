<?php

use App\Enums\Company\BankAccountType;
use App\Enums\Company\BankTransactionCategory;
use App\Enums\Company\BankTransactionType;
use App\Enums\Lines\LineAutomationLevel;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\Depot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Configuration initiale')] #[Layout('layouts::game')] class extends Component {
    use WithFileUploads;

    public int $step = 1;

    public string $company_name = '';
    public $logo = null;
    public ?string $cropped_logo = null;
    public ?int $selected_depot_id = null;
    public ?int $selected_bank_id = null;

    public function mount(): void
    {
        if (Auth::user()->onboarding_completed_at) {
            $this->redirect(route('game'), navigate: true);
            return;
        }

        $this->selected_depot_id = $this->depots->first()?->id;
        $this->selected_bank_id  = $this->banks->first()?->id;
    }

    #[Computed]
    public function depots(): \Illuminate\Database\Eloquent\Collection|array|\LaravelIdea\Helper\App\Models\_IH_Depot_C
    {
        return Depot::with(['lines' => function ($query) {
            $query->select(
                'lines.id',
                'lines.code',
                'lines.name',
                'lines.automation_level',
                'lines.min_cars_per_train',
                'lines.max_cars_per_train',
                'lines.train_model_id' // 👈 Clé étrangère indispensable pour eager load dedicatedTrainModel
            )->with('dedicatedTrainModel');
        }])
            ->whereNull('company_id')
            ->orWhere('id', $this->selected_depot_id)
            ->get();
    }

    #[Computed]
    public function banks(): \Illuminate\Database\Eloquent\Collection|array|\LaravelIdea\Helper\App\Models\_IH_Bank_C
    {
        return Bank::all();
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $rules = [
                'company_name' => ['required', 'string', 'min:3', 'max:50', 'unique:companies,name'],
            ];

            if ($this->cropped_logo) {
                $rules['cropped_logo'] = ['required', 'string'];
            } else {
                $rules['logo'] = ['required', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'];
            }

            $this->validate($rules, [
                'company_name.required' => __('Veuillez saisir un nom pour votre entreprise.'),
                'company_name.min'      => __('Le nom doit contenir au moins 3 caractères.'),
                'company_name.unique'   => __('Ce nom d\'entreprise est déjà pris.'),
                'logo.required'         => __('Veuillez importer un logo pour votre entreprise.'),
                'cropped_logo.required' => __('Veuillez valider le recadrage de votre logo.'),
                'logo.image'            => __('Le fichier doit être une image valide.'),
                'logo.mimes'            => __('Le logo doit être au format PNG, JPG, WEBP ou SVG.'),
                'logo.max'              => __('L\'image ne doit pas dépasser 2 Mo.'),
            ]);
        } elseif ($this->step === 2) {
            $this->validate([
                'selected_bank_id' => ['required', 'exists:banks,id'],
            ], [
                'selected_bank_id.required' => __('Veuillez choisir votre banque partenaire.'),
            ]);
        }

        $this->step++;
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function completeOnboarding(): void
    {
        $this->validate([
            'selected_depot_id' => ['required', 'exists:depots,id'],
        ], [
            'selected_depot_id.required' => __('Veuillez choisir votre dépôt de départ.'),
        ]);

        $user = Auth::user();

        DB::transaction(function () use ($user) {
            $logoPath = null;

            if ($this->cropped_logo) {
                $imageParts = explode(';base64,', $this->cropped_logo);
                $imageDecoded = base64_decode($imageParts[1]);
                $fileName = 'logos/' . Str::uuid() . '.png';
                Storage::disk('public')->put($fileName, $imageDecoded);
                $logoPath = $fileName;
            } elseif ($this->logo) {
                $logoPath = $this->logo->store('logos', 'public');
            }

            $company = Company::create([
                'user_id'  => $user->id,
                'name'     => $this->company_name,
                'logo_url' => $logoPath,
            ]);

            $bankAccount = BankAccount::create([
                'company_id'     => $company->id,
                'bank_id'        => $this->selected_bank_id,
                'account_number' => $this->generateIban(),
                'type'           => BankAccountType::CHECKING,
            ]);

            BankTransaction::create([
                'bank_account_id' => $bankAccount->id,
                'type'            => BankTransactionType::CREDIT,
                'category'        => BankTransactionCategory::CAPITAL_INJECTION,
                'amount'          => 5000000.00,
                'description'     => 'Capital initial de création de l\'entreprise',
                'created_at'      => now(),
            ]);

            Depot::where('id', $this->selected_depot_id)->update([
                'company_id' => $company->id,
            ]);

            $user->forceFill([
                'onboarding_completed_at' => now(),
            ])->save();
        });

        $this->redirect(route('game'), navigate: true);
    }

    private function generateIban(): string
    {
        do {
            $iban = 'FR76' . sprintf('%05d%05d%011d%02d', rand(10000, 99999), rand(10000, 99999), rand(10000000000, 99999999999), rand(10, 99));
        } while (BankAccount::where('account_number', $iban)->exists());

        return $iban;
    }
}; ?>

<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8"
     x-data="{
        cropper: null,
        imageUrl: null,
        showCropperModal: false,
        initCropper(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.imageUrl = URL.createObjectURL(file);
            this.showCropperModal = true;

            this.$nextTick(() => {
                if (this.cropper) this.cropper.destroy();
                const image = this.$refs.cropperImage;
                this.cropper = new Cropper(image, {
                    aspectRatio: 1,
                    viewMode: 1,
                    autoCropArea: 1,
                    responsive: true,
                });
            });
        },
        saveCrop() {
            if (!this.cropper) return;
            const canvas = this.cropper.getCroppedCanvas({
                width: 400,
                height: 400,
            });
            const base64Image = canvas.toDataURL('image/png');
            $wire.set('cropped_logo', base64Image);
            this.showCropperModal = false;
        }
     }">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    {{-- En-tête avec barre de progression --}}
    <div class="border border-zinc-200/80 dark:border-zinc-800/80 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md rounded-2xl p-6 space-y-4 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <flux:badge color="amber" size="sm" icon="sparkles">{{ __('Configuration Initiale') }}</flux:badge>
                    <span class="text-xs font-mono font-medium text-zinc-500 dark:text-zinc-400">Étape {{ $step }} sur 3</span>
                </div>
                <flux:heading size="xl" level="1">{{ __('Fondation de votre Entreprise') }}</flux:heading>
            </div>

            <div class="flex items-center gap-3 bg-zinc-100/80 dark:bg-zinc-800/80 border border-zinc-200/60 dark:border-zinc-700/60 px-4 py-2 rounded-xl self-start sm:self-auto backdrop-blur-sm">
                <flux:icon.banknotes class="w-5 h-5 text-emerald-500" />
                <div>
                    <div class="text-[10px] text-zinc-500 uppercase font-medium">{{ __('Capital initial') }}</div>
                    <div class="text-sm font-bold font-mono text-emerald-600 dark:text-emerald-400">5 000 000 €</div>
                </div>
            </div>
        </div>

        <div class="space-y-1.5">
            <div class="flex justify-between text-xs text-zinc-500 font-medium">
                <span>{{ __('Avancement') }}</span>
                <span class="font-mono">{{ round(($step / 3) * 100) }}%</span>
            </div>
            <div class="w-full h-2.5 bg-zinc-200/50 dark:bg-zinc-800/50 rounded-full overflow-hidden p-0.5 border border-zinc-200/40 dark:border-zinc-700/40 backdrop-blur-sm">
                <div class="h-full bg-gradient-to-r from-amber-500 to-amber-400 rounded-full transition-all duration-500 ease-out shadow-sm" style="width: {{ ($step / 3) * 100 }}%"></div>
            </div>
        </div>
    </div>

    {{-- ÉTAPE 1 : Identité & Logo --}}
    @if($step === 1)
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Étape 1 : Identité de l\'Entreprise') }}</flux:heading>
                <flux:subheading>{{ __('Définissez le nom officiel et importez le logo de votre entreprise.') }}</flux:subheading>
            </div>

            <flux:input
                wire:model="company_name"
                :label="__('Nom de l\'entreprise')"
                :placeholder="__('Ex: Transports Lyonnais, MetroCorp...')"
                icon="building-office"
                required
            />

            <flux:field>
                <flux:label>{{ __('Logo de l\'entreprise') }}</flux:label>
                <div class="mt-2 flex items-center gap-4">
                    @if ($cropped_logo)
                        <div class="relative w-20 h-20 rounded-xl overflow-hidden border border-amber-500/50 bg-zinc-100 dark:bg-zinc-800 shrink-0">
                            <img src="{{ $cropped_logo }}" class="w-full h-full object-cover">
                            <button type="button" @click="showCropperModal = true" class="absolute inset-0 bg-black/40 text-white opacity-0 hover:opacity-100 transition-opacity flex items-center justify-center text-xs font-semibold">
                                Recadrer
                            </button>
                        </div>
                    @elseif ($logo && $logo->isPreviewable())
                        <div class="w-20 h-20 rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800 p-1 shrink-0">
                            <img src="{{ $logo->temporaryUrl() }}" class="w-full h-full object-cover rounded-lg">
                        </div>
                    @else
                        <div class="w-20 h-20 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 flex items-center justify-center shrink-0">
                            <flux:icon.photo class="w-6 h-6 text-zinc-400" />
                        </div>
                    @endif

                    <div class="flex-1 space-y-2">
                        <input
                            type="file"
                            @change="initCropper($event)"
                            accept="image/png,image/jpeg,image/webp"
                            class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-amber-500/10 file:text-amber-600 hover:file:bg-amber-500/20 cursor-pointer"
                        />
                        <p class="text-xs text-zinc-400">PNG, JPG ou WEBP. Vous pourrez recadrer l'image au format carré 1:1.</p>
                    </div>
                </div>
                <flux:error name="logo" />
                <flux:error name="cropped_logo" />
            </flux:field>

            <div class="flex justify-end pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button wire:click="nextStep" variant="primary" icon-trailing="arrow-right">
                    {{ __('Suivant : Banque') }}
                </flux:button>
            </div>
        </flux:card>

        <div x-show="showCropperModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm" x-cloak>
            <div class="bg-white dark:bg-zinc-900 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl border border-zinc-200 dark:border-zinc-800">
                <flux:heading size="lg">{{ __('Recadrer le logo') }}</flux:heading>

                <div class="max-h-[60vh] overflow-hidden rounded-xl bg-black">
                    <img x-ref="cropperImage" :src="imageUrl" class="max-w-full block">
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button @click="showCropperModal = false" variant="ghost">
                        {{ __('Annuler') }}
                    </flux:button>
                    <flux:button @click="saveCrop()" variant="primary" icon="check">
                        {{ __('Valider le découpage') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- ÉTAPE 2 : Banque Partenaire --}}
    @if($step === 2)
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Étape 2 : Banque Partenaire') }}</flux:heading>
                <flux:subheading>{{ __('Comparez les conditions financières et choisissez l\'établissement le plus adapté à votre stratégie.') }}</flux:subheading>
            </div>

            @if(count($this->banks) > 0)
                <div class="grid grid-cols-1 gap-4">
                    @foreach($this->banks as $bank)
                        <div
                            wire:click="$set('selected_bank_id', {{ $bank->id }})"
                            class="p-5 rounded-xl border cursor-pointer transition-all space-y-4 {{ $selected_bank_id === $bank->id ? 'border-amber-500 bg-amber-500/10 ring-2 ring-amber-500/30' : 'border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 hover:border-zinc-300 dark:hover:border-zinc-600' }}"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-base text-zinc-900 dark:text-white">{{ $bank->name }}</span>
                                        @if($selected_bank_id === $bank->id)
                                            <flux:badge color="amber" size="sm" icon="check">{{ __('Sélectionnée') }}</flux:badge>
                                        @endif
                                    </div>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $bank->description }}</p>
                                </div>

                                <div class="text-right shrink-0">
                                    <div class="text-xs text-zinc-400 uppercase font-medium">{{ __('Taux débiteur') }}</div>
                                    <div class="text-lg font-bold font-mono text-amber-600 dark:amber-400">{{ number_format($bank->interest_rate, 2, ',', ' ') }} %</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-3 border-t border-zinc-200 dark:border-zinc-700/80 text-xs">
                                <div class="bg-white/60 dark:bg-zinc-900/60 p-2.5 rounded-lg border border-zinc-200/50 dark:border-zinc-700/50">
                                    <span class="text-zinc-400 block mb-0.5">{{ __('Plafond d\'emprunt') }}</span>
                                    <span class="font-bold font-mono text-zinc-800 dark:text-zinc-200">
                                        {{ number_format($bank->max_loan_amount, 0, ',', ' ') }} €
                                    </span>
                                </div>

                                <div class="bg-white/60 dark:bg-zinc-900/60 p-2.5 rounded-lg border border-zinc-200/50 dark:border-zinc-700/50">
                                    <span class="text-zinc-400 block mb-0.5">{{ __('Durée max. de remboursement') }}</span>
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200">
                                        {{ $bank->repayment_duration_days }} jours
                                    </span>
                                </div>

                                <div class="bg-white/60 dark:bg-zinc-900/60 p-2.5 rounded-lg border border-zinc-200/50 dark:border-zinc-700/50 col-span-2 sm:col-span-1">
                                    <span class="text-zinc-400 block mb-0.5">{{ __('Découvert autorisé') }}</span>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">Inclus</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-6 text-center text-sm text-zinc-500 border border-dashed rounded-xl">
                    {{ __('Aucune banque trouvée dans la base de données.') }}
                </div>
            @endif
            <flux:error name="selected_bank_id" />

            <div class="flex justify-between items-center pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button wire:click="previousStep" variant="ghost" icon="arrow-left">
                    {{ __('Retour') }}
                </flux:button>
                <flux:button wire:click="nextStep" variant="primary" icon-trailing="arrow-right">
                    {{ __('Suivant : Dépôt') }}
                </flux:button>
            </div>
        </flux:card>
    @endif

    {{-- ÉTAPE 3 : Choix du Dépôt Principal & Compatibilité --}}
    @if($step === 3)
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Étape 3 : Dépôt de Départ & Spécifications') }}</flux:heading>
                <flux:subheading>{{ __('Sélectionnez votre centre d\'exploitation. Vérifiez l\'automatisation et le besoin en personnel des lignes raccordées.') }}</flux:subheading>
            </div>

            @if(count($this->depots) > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($this->depots as $depot)
                        <div
                            wire:click="$set('selected_depot_id', {{ $depot->id }})"
                            class="p-5 rounded-xl border cursor-pointer transition-all flex flex-col justify-between space-y-4 {{ $selected_depot_id === $depot->id ? 'border-amber-500 bg-amber-500/10 ring-2 ring-amber-500/30' : 'border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 hover:border-zinc-300' }}"
                        >
                            <div class="space-y-3">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="font-semibold text-zinc-900 dark:text-white">{{ $depot->name }}</span>
                                    <span class="text-xs font-mono font-medium text-zinc-500 dark:text-zinc-400">{{ $depot->max_capacity }} rames max</span>
                                </div>

                                {{-- Cartes compactes des lignes raccordées --}}
                                <div class="space-y-2">
                                    <div class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">{{ __('Lignes desservies') }}</div>

                                    @forelse($depot->lines as $line)
                                        <div class="p-2.5 rounded-lg bg-white/70 dark:bg-zinc-900/70 border border-zinc-200/60 dark:border-zinc-700/60 space-y-2">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <flux:badge size="sm" color="amber" class="font-bold">{{ $line->code }}</flux:badge>
                                                    <span class="text-xs font-medium text-zinc-800 dark:text-zinc-200">{{ $line->name }}</span>
                                                </div>
                                                @if($line->max_cars_per_train)
                                                    <span class="text-[10px] font-mono text-zinc-400" title="Gabarit maximum">
                                                        📏 {{ $line->max_cars_per_train }} caisses max
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Niveaux d'automatisation & besoin en conducteur --}}
                                            <div class="flex flex-wrap gap-1.5 pt-1.5 border-t border-zinc-100 dark:border-zinc-800 text-[10px]">
                                                @if($line->automation_level === LineAutomationLevel::AUTOMATED)
                                                    <span class="px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-semibold" title="Exploitation GoA4 sans aucun personnel à bord">
                                                        Auto
                                                    </span>
                                                    <span class="px-1.5 py-0.5 rounded bg-zinc-200/50 dark:bg-zinc-800 text-zinc-500 font-medium">
                                                        Sans conducteur
                                                    </span>
                                                    <span class="px-1.5 py-0.5 rounded bg-zinc-200/50 dark:bg-zinc-800 text-zinc-500 font-medium">{{$line->dedicatedTrainModel->name}}
                                                    </span>
                                                @elseif($line->automation_level === LineAutomationLevel::SEMI_AUTOMATED)
                                                    <span class="px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-600 dark:text-amber-400 font-semibold" title="Conduite automatisée mais présence humaine obligatoire">
                                                        Semi-auto
                                                    </span>
                                                    <span class="px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-700 dark:text-amber-300 font-medium">
                                                        Conducteur requis
                                                    </span>

                                                    <span class="px-1.5 py-0.5 rounded bg-zinc-200/50 dark:bg-zinc-800 text-zinc-500 font-medium">
                                                        {{$line->dedicatedTrainModel->name}}
                                                    </span>
                                                @else
                                                    <span class="px-1.5 py-0.5 rounded bg-blue-500/10 text-blue-600 dark:text-blue-400 font-semibold" title="Pilotage manuelle intégrale par agent de conduite">
                                                         Manuel
                                                    </span>
                                                    <span class="px-1.5 py-0.5 rounded bg-blue-500/10 text-blue-700 dark:text-blue-300 font-medium">
                                                        Conducteur requis
                                                    </span>

                                                    <span class="px-1.5 py-0.5 rounded bg-zinc-200/50 dark:bg-zinc-800 text-zinc-500 font-medium">
                                                        {{$line->dedicatedTrainModel->name}}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-xs text-zinc-400 italic">
                                            {{ __('Aucune ligne raccordée directement.') }}
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400 border-t border-zinc-200 dark:border-zinc-700/80 pt-3">
                                <span>{{ __('Statut') }}</span>
                                @if($selected_depot_id === $depot->id)
                                    <flux:badge color="amber" size="sm" icon="check">{{ __('Sélectionné') }}</flux:badge>
                                @else
                                    <span class="text-zinc-400">{{ __('Disponible') }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-6 text-center text-sm text-zinc-500 border border-dashed rounded-xl">
                    {{ __('Aucun dépôt disponible.') }}
                </div>
            @endif
            <flux:error name="selected_depot_id" />

            <div class="flex justify-between items-center pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button wire:click="previousStep" variant="ghost" icon="arrow-left">
                    {{ __('Retour') }}
                </flux:button>
                <flux:button wire:click="completeOnboarding" variant="primary" icon-trailing="sparkles">
                    {{ __('Créer l\'entreprise') }}
                </flux:button>
            </div>
        </flux:card>
    @endif

</div>
