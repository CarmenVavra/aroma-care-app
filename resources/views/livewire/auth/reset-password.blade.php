<div>
    <h1 class="font-display text-[28px] leading-tight">Neues Passwort</h1>
    <p class="mt-1 text-sm text-muted dark:text-chalk/70">Lege ein neues Passwort für dein Konto fest.</p>

    <form wire:submit="resetPassword" class="mt-6 space-y-4" novalidate>
        @csrf
        <x-field label="E-Mail" id="email" type="email" wire:model="email" autocomplete="email" required
            :error="$errors->first('email')" />

        <x-field label="Neues Passwort" id="password" type="password" wire:model="password" autocomplete="new-password" required
            autofocus hint="Mindestens 8 Zeichen." :error="$errors->first('password')" />

        <x-field label="Passwort wiederholen" id="password_confirmation" type="password" wire:model="password_confirmation"
            autocomplete="new-password" required />

        <button type="submit" wire:loading.attr="disabled"
            class="h-[54px] w-full rounded-2xl bg-navy-900 text-base font-bold text-white hover:bg-navy-900/90 dark:hover:bg-chalk/85 disabled:opacity-60 dark:bg-chalk dark:text-navy-900">
            Passwort speichern
        </button>
    </form>
</div>
