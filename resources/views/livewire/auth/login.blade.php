<div>
    <h1 class="font-display text-[28px] leading-tight">Anmelden</h1>
    <p class="mt-1 text-sm text-muted dark:text-chalk/70">Willkommen zurück bei deinem Bestand.</p>

    @if (session('status'))
        <p class="mt-4 rounded-xl bg-[#DCE5FB] px-3.5 py-2.5 text-sm font-medium text-[#1E3A8A] dark:bg-cobalt-500/25 dark:text-cobalt-200" role="status">
            {{ session('status') }}
        </p>
    @endif

    <form wire:submit="login" class="mt-6 space-y-4" novalidate>
        @csrf
        <x-field label="E-Mail" id="email" type="email" wire:model="email" autocomplete="email" required autofocus
            :error="$errors->first('email')" />

        <x-field label="Passwort" id="password" type="password" wire:model="password" autocomplete="current-password" required
            :error="$errors->first('password')" />

        <div class="flex items-center justify-between gap-3">
            <label class="flex min-h-11 items-center gap-2 text-sm">
                <input type="checkbox" wire:model="remember" class="size-[18px] rounded accent-navy-900">
                Angemeldet bleiben
            </label>
            <a href="{{ route('password.request') }}" wire:navigate
                class="text-sm font-semibold text-violet-700 underline-offset-2 hover:underline dark:text-cobalt-200">Passwort vergessen?</a>
        </div>

        <button type="submit" wire:loading.attr="disabled"
            class="h-[54px] w-full rounded-2xl bg-navy-900 text-base font-bold text-white hover:bg-navy-900/90 dark:hover:bg-chalk/85 disabled:opacity-60 dark:bg-chalk dark:text-navy-900">
            Anmelden
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-muted dark:text-chalk/70">
        Noch kein Konto?
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-violet-700 hover:underline dark:text-cobalt-200">Registrieren</a>
    </p>
</div>
