<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    #[On('bank-account-updated')]
    public function refreshBalance(): void
    {
        // Re-rendu automatique à la réception de l'événement
    }

    public function with(): array
    {
        $company = Auth::user()?->company;

        return [
            'balance' => $company?->total_balance
                ?? $company?->bankAccount?->balance
                    ?? 0,
        ];
    }
}; ?>

<div class="flex items-center gap-2 bg-emerald-500/10 dark:bg-emerald-500/15 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 px-3 py-1.5 rounded-xl">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-6h6m-9 9h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z" />
    </svg>

    <div class="flex flex-col text-right">
        <span class="text-[9px] font-semibold uppercase tracking-wider text-emerald-600/70 dark:text-emerald-400/70 leading-none">
            {{ __('Trésorerie') }}
        </span>
        <span class="text-xs font-bold font-mono leading-tight">
            {{ number_format($balance, 2, ',', ' ') }} €
        </span>
    </div>
</div>
