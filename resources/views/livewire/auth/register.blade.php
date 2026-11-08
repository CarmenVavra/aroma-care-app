<div>
    <h1 class="font-display text-[28px] leading-tight">Konto erstellen</h1>
    <p class="mt-1 text-sm text-muted dark:text-chalk/70">Verwalte deine Öle, Wachse und Rezepte.</p>

    <form wire:submit="register" class="mt-6 space-y-4" novalidate>
        @csrf
        <x-field label="Name" id="name" type="text" wire:model="name" autocomplete="given-name" required autofocus
            :error="$errors->first('name')" />

        <x-field label="E-Mail" id="email" type="email" wire:model="email" autocomplete="email" required
            :error="$errors->first('email')" />

        <x-field label="Passwort" id="password" type="password" wire:model="password" autocomplete="new-password" required
            hint="Mindestens 8 Zeichen." :error="$errors->first('password')" />

        <x-field label="Passwort wiederholen" id="password_confirmation" type="password" wire:model="password_confirmation"
            autocomplete="new-password" required />

        <button type="submit" wire:loading.attr="disabled"
            class="h-[54px] w-full rounded-2xl bg-navy-900 text-base font-bold text-white hover:bg-navy-900/90 disabled:opacity-60 dark:bg-chalk dark:text-navy-900">
            Registrieren
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-muted dark:text-chalk/70">
        Schon registriert?
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-violet-700 hover:underline dark:text-cobalt-200">Anmelden</a>
    </p>
</div>
