<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Lupa kata sandi')]
class ForgotPassword extends Component
{
    public string $email = '';

    public ?string $status = null;

    public function send(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->status = 'Tautan atur ulang kata sandi sudah dikirim ke email Anda.';
        $this->reset('email');
    }
}
