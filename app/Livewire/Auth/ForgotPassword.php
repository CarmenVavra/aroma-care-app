<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Passwort vergessen')]
class ForgotPassword extends Component
{
    public string $email = '';

    public ?string $status = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return ['email' => ['required', 'string', 'email']];
    }

    public function sendResetLink(): void
    {
        $this->validate();

        $status = Password::sendResetLink(['email' => mb_strtolower(trim($this->email))]);

        if ($status === Password::RESET_THROTTLED) {
            $this->addError('email', __($status));

            return;
        }

        // Same message whether or not the account exists (no user enumeration).
        $this->status = __(Password::RESET_LINK_SENT);
        $this->reset('email');
    }

    public function render(): View
    {
        return view('livewire.auth.forgot-password');
    }
}
