<div>
    <h1 class="font-display text-[28px] leading-tight">Passwort vergessen</h1>
    <p class="mt-1 text-sm text-muted dark:text-chalk/70">
        Gib deine E-Mail-Adresse ein. Wir schicken dir einen Link, mit dem du ein neues Passwort festlegen kannst.
    </p>

    @if ($status)
        <p class="mt-4 rounded-xl bg-[#DCE5FB] px-3.5 py-2.5 text-sm font-medium text-[#1E3A8A] dark:bg-cobalt-500/25 dark:text-cobalt-200" role="status">
            {{ $status }}
        </p>
    @endif

    <form wire:submit="sendResetLink" class="mt-6 space-y-4" novalidate>
        @csrf
        <x-field label="E-Mail" id="email" type="email" wire:model="email" autocomplete="email" required autofocus
            :error="$errors->first('email')" />

        <button type="submit" wire:loading.attr="disabled"
            class="h-[54px] w-full rounded-2xl bg-navy-900 text-base font-bold text-white hover:bg-navy-900/90 disabled:opacity-60 dark:bg-chalk dark:text-navy-900">
            Link senden
        </button>
    </form>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-violet-700 hover:underline dark:text-cobalt-200">Zurück zur Anmeldung</a>
    </p>
</div>
