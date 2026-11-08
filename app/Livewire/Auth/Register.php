<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Registrieren')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'email.unique' => 'Für diese E-Mail gibt es bereits ein Konto.',
            'email.lowercase' => 'Bitte gib die E-Mail in Kleinbuchstaben ein.',
            'password.min' => 'Das Passwort muss mindestens :min Zeichen haben.',
        ];
    }

    public function register(): void
    {
        $this->email = mb_strtolower(trim($this->email));
        $validated = $this->validate();

        $user = User::create([
            'name' => trim($validated['name']),
            'email' => $validated['email'],
            'password' => $validated['password'], // hashed by the model cast
        ]);

        event(new Registered($user));

        Auth::login($user);
        session()->regenerate();

        $this->redirectRoute('stock');
    }

    public function render(): View
    {
        return view('livewire.auth.register');
    }
}
