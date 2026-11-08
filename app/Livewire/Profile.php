<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profil')]
class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** Password confirmation for deleting the account. */
    public string $delete_password = '';

    public function mount(): void
    {
        $this->name = $this->user()->name;
        $this->email = $this->user()->email;
    }

    public function updateProfile(): void
    {
        $this->email = mb_strtolower(trim($this->email));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
        ], [
            'email.unique' => 'Für diese E-Mail gibt es bereits ein Konto.',
        ]);

        $this->user()->update(['name' => trim($validated['name']), 'email' => $validated['email']]);

        $this->dispatch('notify', message: 'Profil gespeichert.');
    }

    public function updatePassword(): void
    {
        $validated = $this->validate([
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $this->user()->update(['password' => $validated['password']]); // hashed by the model cast

        // Sign out other devices that still use the old password.
        Auth::logoutOtherDevices($validated['password']);

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->dispatch('notify', message: 'Passwort geändert.');
    }

    /**
     * GDPR: users can delete their account including all products.
     */
    public function deleteAccount(): void
    {
        $this->validate(
            ['delete_password' => ['required', 'string', 'current_password:web']],
            ['delete_password.required' => 'Bitte bestätige mit deinem Passwort.', 'delete_password.current_password' => 'Das Passwort ist nicht korrekt.'],
        );

        $user = $this->user();

        Auth::guard('web')->logout();

        DB::transaction(function () use ($user) {
            $user->products()->withTrashed()->forceDelete();
            $user->delete();
        });

        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('login');
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('login');
    }

    public function render(): View
    {
        return view('livewire.profile');
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
