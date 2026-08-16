<?php

use Livewire\Component;

new class extends Component {
    public ?int $tutorialStep = null;
    public bool $isOnboarded = false;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user) {
            $this->tutorialStep = $user->tutorial_step;
            $this->isOnboarded = ! is_null($user->onboarding_completed_at);
        }
    }

    public function nextTutorialStep(): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        if ($user->tutorial_step >= 3) {
            $user->update(['tutorial_step' => null]);
            $this->tutorialStep = null;
        } else {
            $user->increment('tutorial_step');
            $this->tutorialStep = $user->fresh()->tutorial_step;
        }
    }

    public function skipTutorial(): void
    {
        auth()->user()?->update(['tutorial_step' => null]);
        $this->tutorialStep = null;
    }
};
?>

<div>
    @if(! is_null($tutorialStep) && $isOnboarded )
        <div
            class="fixed bottom-6 right-6 z-50 w-96 rounded-xl border border-slate-700 bg-slate-900 p-4 text-white shadow-2xl">
            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <span class="text-xs font-semibold uppercase tracking-wider text-indigo-400">
                    Tutoriel — Étape {{ $tutorialStep }} / 3
                </span>
                <button wire:click="skipTutorial" type="button"
                        class="text-xs text-slate-400 hover:text-slate-200 underline">
                    Passer le tuto
                </button>
            </div>

            <div class="py-3 text-sm text-slate-300 space-y-2">
                @switch($tutorialStep)
                    @case(1)
                        <p>Bienvenue dans Metro Manager ! Commencez par consulter la liste de vos dépôts pour vérifier
                            vos installations.</p>
                        <div class="pt-1">
                            <a href="#" wire:navigate
                               class="inline-flex items-center gap-1 text-xs font-medium text-indigo-400 hover:text-indigo-300 underline">
                                📍 Accéder aux Dépôts &rarr;
                            </a>
                        </div>
                        @break

                    @case(2)
                        <p>Dans la liste de vos dépôts, cliquez sur <strong>« Éditer »</strong> pour leur assigner les
                            lignes de métro à gérer.</p>
                        <div class="pt-1">
                            <a href="#" wire:navigate
                               class="inline-flex items-center gap-1 text-xs font-medium text-indigo-400 hover:text-indigo-300 underline">
                                🚇 Voir les Lignes &rarr;
                            </a>
                        </div>
                        @break

                    @case(3)
                        <p>Achetez vos premières rames de métro et affectez-les aux lignes pour lancer le service
                            commercial !</p>
                        <div class="pt-1">
                            <a href="#" wire:navigate
                               class="inline-flex items-center gap-1 text-xs font-medium text-indigo-400 hover:text-indigo-300 underline">
                                🛒 Catalogue des Rames &rarr;
                            </a>
                        </div>
                        @break
                @endswitch
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                <button wire:click="nextTutorialStep" type="button"
                        class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-500 transition">
                    {{ $tutorialStep >= 3 ? 'Terminer' : 'Suivant' }}
                </button>
            </div>
        </div>
    @endif
</div>
