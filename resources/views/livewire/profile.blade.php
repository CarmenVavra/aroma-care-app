<div x-data="{ confirmDeleteAccount: false }" class="mx-auto max-w-2xl px-5 pt-8 pb-10 md:px-8">
    <header class="flex items-center gap-4">
        <span class="grid size-14 place-items-center rounded-full bg-navy-900 text-xl font-bold text-white dark:bg-chalk dark:text-navy-900"
            aria-hidden="true">{{ auth()->user()->initial }}</span>
        <div>
            <p class="text-sm text-muted dark:text-chalk/70">{{ auth()->user()->email }}</p>
            <h1 class="font-display text-4xl leading-tight lg:text-[42px]">Profil</h1>
        </div>
    </header>

    @php($card = 'mt-6 rounded-(--radius-card) border border-mist-300 bg-mist-50 p-5 shadow-soft dark:border-white/10 dark:bg-night-800')
    @php($button = 'h-12 rounded-2xl bg-navy-900 px-6 text-sm font-bold text-white hover:bg-navy-900/90 disabled:opacity-60 dark:bg-chalk dark:text-navy-900')
    @php($heading = 'mb-4 text-xs font-bold tracking-[.08em] text-violet-700 uppercase dark:text-cobalt-200')

    <section class="{{ $card }}" aria-labelledby="profile-data">
        <h2 id="profile-data" class="{{ $heading }}">Persönliche Daten</h2>
        <form wire:submit="updateProfile" class="space-y-4" novalidate>
            @csrf
            <x-field label="Name" id="profile-name" type="text" wire:model="name" autocomplete="given-name" required
                :error="$errors->first('name')" />
            <x-field label="E-Mail" id="profile-email" type="email" wire:model="email" autocomplete="email" required
                :error="$errors->first('email')" />
            <button type="submit" wire:loading.attr="disabled" wire:target="updateProfile" class="{{ $button }}">Speichern</button>
        </form>
    </section>

    <section class="{{ $card }}" aria-labelledby="profile-password">
        <h2 id="profile-password" class="{{ $heading }}">Passwort ändern</h2>
        <form wire:submit="updatePassword" class="space-y-4" novalidate>
            @csrf
            <x-field label="Aktuelles Passwort" id="current_password" type="password" wire:model="current_password"
                autocomplete="current-password" required :error="$errors->first('current_password')" />
            <x-field label="Neues Passwort" id="new_password" type="password" wire:model="password" autocomplete="new-password"
                required hint="Mindestens 8 Zeichen." :error="$errors->first('password')" />
            <x-field label="Neues Passwort wiederholen" id="new_password_confirmation" type="password"
                wire:model="password_confirmation" autocomplete="new-password" required />
            <button type="submit" wire:loading.attr="disabled" wire:target="updatePassword" class="{{ $button }}">Passwort ändern</button>
        </form>
    </section>

    <section class="{{ $card }}" aria-labelledby="profile-appearance">
        <h2 id="profile-appearance" class="{{ $heading }}">Darstellung</h2>
        <p class="mb-4 text-sm text-muted dark:text-chalk/75">
            „Automatisch“ folgt der Einstellung deines Geräts. Die Auswahl wird nur in diesem Browser gespeichert.
        </p>
        <x-theme-switch />
    </section>

    <section x-data="installApp" class="{{ $card }}" aria-labelledby="profile-install">
        <h2 id="profile-install" class="{{ $heading }}">App installieren</h2>

        <p x-show="installed" class="flex items-center gap-2 text-sm font-semibold">
            <x-app-icon name="check" class="size-4 text-cobalt-500 dark:text-cobalt-200" /> AromaCare ist auf diesem Gerät installiert.
        </p>

        <div x-show="!installed">
            <p class="text-sm text-muted dark:text-chalk/75">
                Lege AromaCare wie eine App auf deinen Startbildschirm – mit eigenem Symbol und ohne Browserleiste.
            </p>

            <button type="button" x-show="canPrompt" x-cloak @click="install()" class="{{ $button }} mt-4">
                Jetzt installieren
            </button>

            {{-- iPhone/iPad: Safari has no install prompt, only the share menu. --}}
            <ol x-show="!canPrompt && ios" x-cloak class="mt-4 list-decimal space-y-1.5 pl-5 text-sm leading-relaxed">
                <li>Seite in <strong>Safari</strong> öffnen.</li>
                <li>Unten auf <strong>Teilen</strong> tippen (Quadrat mit Pfeil nach oben).</li>
                <li><strong>„Zum Home-Bildschirm“</strong> wählen und mit <strong>Hinzufügen</strong> bestätigen.</li>
            </ol>

            {{-- Android without prompt (e.g. dismissed before, Firefox) and desktop browsers. --}}
            <ol x-show="!canPrompt && !ios" x-cloak class="mt-4 list-decimal space-y-1.5 pl-5 text-sm leading-relaxed">
                <li>Browser-Menü öffnen (<strong>⋮</strong> oben rechts).</li>
                <li><strong>„App installieren“</strong> bzw. <strong>„Zum Startbildschirm hinzufügen“</strong> wählen.</li>
                <li>Mit <strong>Installieren</strong> bestätigen.</li>
            </ol>
        </div>
    </section>

    <section class="{{ $card }}" aria-labelledby="profile-session">
        <h2 id="profile-session" class="{{ $heading }}">Sitzung</h2>
        <button type="button" wire:click="logout"
            class="h-12 rounded-2xl border-[1.5px] border-navy-900 px-6 text-sm font-bold hover:bg-navy-900/5 dark:border-chalk">
            Abmelden
        </button>
    </section>

    <section class="{{ $card }}" aria-labelledby="profile-delete">
        <h2 id="profile-delete" class="{{ $heading }}">Konto löschen</h2>
        <p class="text-sm text-muted dark:text-chalk/75">
            Dein Konto und alle deine Produkte werden dauerhaft gelöscht. Das kann nicht rückgängig gemacht werden.
        </p>
        <button type="button" @click="confirmDeleteAccount = true"
            class="mt-4 h-12 rounded-2xl border-[1.5px] border-violet-700 px-6 text-sm font-bold text-violet-700 hover:bg-violet-700/5 dark:border-violet-400 dark:text-violet-400">
            Konto löschen …
        </button>
    </section>

    <x-legal-links class="mt-8" />

    {{-- Delete account: custom dialog with password confirmation --}}
    <div x-cloak x-show="confirmDeleteAccount" x-transition.opacity class="fixed inset-0 z-[80] grid place-items-center bg-night-900/60 p-4"
        @keydown.escape.window="confirmDeleteAccount = false">
        <form x-show="confirmDeleteAccount" x-trap="confirmDeleteAccount" x-scroll-lock="confirmDeleteAccount" wire:submit="deleteAccount" role="alertdialog"
            aria-modal="true" aria-labelledby="delete-account-title" @click.outside="confirmDeleteAccount = false"
            class="w-full max-w-sm space-y-4 rounded-(--radius-card) bg-mist-50 p-6 shadow-soft dark:bg-night-800">
            @csrf
            <h2 id="delete-account-title" class="font-display text-[22px]">Konto wirklich löschen?</h2>
            <p class="text-sm text-muted dark:text-chalk/75">Bitte bestätige mit deinem Passwort.</p>
            <x-field label="Passwort" id="delete_password" type="password" wire:model="delete_password"
                autocomplete="current-password" :error="$errors->first('delete_password')" />
            <div class="flex gap-3">
                <button type="button" @click="confirmDeleteAccount = false"
                    class="h-12 flex-1 rounded-2xl border-[1.5px] border-navy-900 font-semibold dark:border-chalk">Abbrechen</button>
                <button type="submit" class="h-12 flex-1 rounded-2xl bg-violet-700 font-semibold text-white hover:bg-violet-700/90">Löschen</button>
            </div>
        </form>
    </div>
</div>
