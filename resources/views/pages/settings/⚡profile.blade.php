<?php

use App\Concerns\ProfileValidationRules;
/* @chisel-email-verification */
use Illuminate\Contracts\Auth\MustVerifyEmail;
/* @end-chisel-email-verification */
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Supprime toutes les rames et caisses achetées et recrédite le compte en supprimant les transactions de débit.
     */
    public function resetPurchases(): void
    {
        $user = Auth::user();
        $company = $user?->company;

        if (! $company) {
            return;
        }

        DB::transaction(function () use ($company) {
            // 1. Suppression des caisses individuelles
            $company->trainCars()->delete();

            // 2. Suppression des rames
            $company->trains()->delete();

            // 3. Suppression des transactions de débit liées aux achats (récupération dynamique du solde)
            if ($company->bankAccount) {
                $company->bankAccount->transactions()
                    ->where('type', 'debit')
                    ->whereIn('category', ['train_purchase', 'rolling_stock_purchase'])
                    ->delete();
            }
        });

        // Actualisation du badge de trésorerie dans le header
        $this->dispatch('bank-account-updated');

        Flux::toast(variant: 'success', text: __('Vos achats de rames ont été réinitialisés et recrédités.'));
    }

    /**
     * Supprime l'entreprise de l'utilisateur, nettoie ses données et réinitialise l'onboarding.
     */
    public function resetCompany(): void
    {
        $user = Auth::user();

        DB::transaction(function () use ($user) {
            if ($user->company) {
                $user->company->delete(); // Déclenche le booted() observer
            }

            $user->forceFill([
                'onboarding_completed_at' => null,
            ])->save();
        });

        $this->dispatch('bank-account-updated');

        Flux::toast(variant: 'success', text: __('Votre entreprise a été réinitialisée avec succès.'));

        $this->redirect(route('onboarding'), navigate: true);
    }

    /* @chisel-email-verification */
    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
    /* @end-chisel-email-verification */
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                {{-- @chisel-email-verification --}}
                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
                {{-- @end-chisel-email-verification --}}
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </div>
        </form>

        @if (Auth::user()->company)
            {{-- Zone Réinitialisation des Achats --}}
            <div class="pt-6 mt-6 border-t border-zinc-200 dark:border-zinc-800 space-y-4">
                <div>
                    <flux:heading size="lg" class="text-amber-600 dark:text-amber-400">{{ __('Réinitialiser les achats de rames') }}</flux:heading>
                    <flux:subheading>{{ __('Supprime toutes vos rames et caisses actuelles et recrédite les montants d\'achat sur votre compte bancaire.') }}</flux:subheading>
                </div>

                <flux:modal.trigger name="confirm-purchases-reset">
                    <flux:button variant="filled">
                        {{ __('Réinitialiser le matériel roulant') }}
                    </flux:button>
                </flux:modal.trigger>

                <flux:modal name="confirm-purchases-reset" class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ __('Réinitialiser la flotte de rames ?') }}</flux:heading>
                        <flux:subheading class="mt-2">
                            {{ __('Toutes vos rames et caisses seront supprimées du dépôt. Les débits bancaires correspondants seront supprimés, restituant le solde d\'origine.') }}
                        </flux:subheading>
                    </div>

                    <div class="flex justify-end gap-3">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('Annuler') }}</flux:button>
                        </flux:modal.close>

                        <flux:button variant="danger" wire:click="resetPurchases">
                            {{ __('Confirmer la réinitialisation') }}
                        </flux:button>
                    </div>
                </flux:modal>
            </div>

            {{-- Zone Réinitialisation de l'Entreprise --}}
            <div class="pt-6 mt-6 border-t border-zinc-200 dark:border-zinc-800 space-y-4">
                <div>
                    <flux:heading size="lg" class="text-red-600 dark:text-red-400">{{ __('Réinitialiser mon entreprise') }}</flux:heading>
                    <flux:subheading>{{ __('Supprime définitivement votre entreprise (comptes bancaires, dépôts, trains) et relance la configuration initiale.') }}</flux:subheading>
                </div>

                <flux:modal.trigger name="confirm-company-reset">
                    <flux:button variant="danger">
                        {{ __('Supprimer l\'entreprise') }}
                    </flux:button>
                </flux:modal.trigger>

                <flux:modal name="confirm-company-reset" class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ __('Êtes-vous absolument sûr ?') }}</flux:heading>
                        <flux:subheading class="mt-2">
                            {{ __('Cette action est irréversible. Votre entreprise, tous vos comptes bancaires, transactions, rames et configurations seront définitivement supprimés.') }}
                        </flux:subheading>
                    </div>

                    <div class="flex justify-end gap-3">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('Annuler') }}</flux:button>
                        </flux:modal.close>

                        <flux:button variant="danger" wire:click="resetCompany">
                            {{ __('Confirmer la suppression') }}
                        </flux:button>
                    </div>
                </flux:modal>
            </div>
        @endif

        {{-- @chisel-email-verification --}}
        @if ($this->showDeleteUser)
            {{-- @end-chisel-email-verification --}}
            <livewire:pages::settings.delete-user-form />
            {{-- @chisel-email-verification --}}
        @endif
        {{-- @end-chisel-email-verification --}}
    </x-pages::settings.layout>
</section>
