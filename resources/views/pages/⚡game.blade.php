<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Metro Manager')]
#[Layout('layouts::game')]
class extends Component
{
    public float $soldes = 0;

    public function mount(): void
    {
        $company = auth()->user()?->company;

        $this->soldes = $company?->total_balance ?? 0;
    }
};
?>

<div>
    <!-- En-tête / Solde -->
    <div class="text-2xl font-bold font-mono">
        {{ number_format($soldes, 2, ',', ' ') }} €
    </div>
</div>
