<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-950 flex flex-col w-full">
@php
    $user = auth()->user();
    $company = $user?->company;

    // Récupération du solde direct sur le modèle Company
    $balance = $company?->total_balance
        ?? $company?->bankAccount?->balance
        ?? 0;
@endphp

{{-- En-tête supérieur avec le composant Flux --}}
<flux:header container class="border-b border-zinc-200/80 dark:border-zinc-800/80 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md w-full">
    <div class="flex items-center justify-between gap-4 w-full">

        {{-- Gauche : Logo & Identité du Réseau --}}
        <div class="flex items-center gap-4">
            @if($company)
                <div class="flex items-center gap-3">
                    {{-- Affichage du Logo --}}
                    @if($company->logo_url)
                        <div class="w-8 h-8 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shrink-0 flex items-center justify-center p-0.5">
                            <img src="{{ Storage::url($company->logo_url) }}" alt="{{ $company->name }}" class="w-full h-full object-cover object-center rounded-md">
                        </div>
                    @else
                        <div class="w-8 h-8 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center shrink-0">
                            <flux:icon.building-office class="w-4 h-4 text-zinc-400" />
                        </div>
                    @endif

                    <div>
                        <span class="text-sm font-bold text-zinc-900 dark:text-white leading-tight">
                            {{ $company->name }}
                        </span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Droite : Trésorerie & Menu Utilisateur --}}
        <div class="flex items-center gap-3 sm:gap-4">

            {{-- Badge Trésorerie --}}
            <div class="flex items-center gap-2 bg-emerald-500/10 dark:bg-emerald-500/15 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 px-3 py-1.5 rounded-xl">
              <livewire:treasory-badge/>
            </div>

            <div class="h-5 w-px bg-zinc-200 dark:bg-zinc-800"></div>

            {{-- Menu Profil --}}
            <flux:dropdown position="bottom" align="end">
                <flux:profile
                    :initials="$user->initials()"
                    icon-trailing="chevron-down"
                    class="cursor-pointer"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="$user->name"
                                    :initials="$user->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ $user->name }}</flux:heading>
                                    <flux:text class="truncate">{{ $user->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>

        </div>
    </div>
</flux:header>

{{-- Contenu principal --}}
<flux:main class="flex-1 w-full max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
    {{ $slot }}
</flux:main>

{{-- Tutoriel interactif flottant --}}
<livewire:game-tutorial />

@persist('toast')
<flux:toast.group>
    <flux:toast />
</flux:toast.group>
@endpersist

@fluxScripts
</body>
</html>
