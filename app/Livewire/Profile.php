<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profil dan kata sandi')]
class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $currentPassword = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
    }

    public function updateProfile(): void
    {
        $this->email = mb_strtolower(trim($this->email));
        $user = auth()->user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'email.unique' => 'Email ini sudah dipakai pengguna lain.',
        ]);

        $old = $user->only(['name', 'email', 'phone']);

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
        ])->save();

        ActivityLog::record('user.updated', 'Memperbarui profil sendiri', $user, $old, $user->only(['name', 'email', 'phone']));

        session()->flash('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ], [
            'currentPassword.required' => 'Masukkan kata sandi saat ini.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $user = auth()->user();

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'Kata sandi saat ini tidak cocok.');

            return;
        }

        $user->forceFill(['password' => $this->password])->save();

        ActivityLog::record('user.password_reset', 'Mengubah kata sandi sendiri', $user);

        $this->reset('currentPassword', 'password', 'password_confirmation');

        session()->flash('success', 'Kata sandi berhasil diubah. Gunakan kata sandi baru pada sesi berikutnya.');
    }

    public function render(): View
    {
        return view('livewire.profile', [
            'user' => auth()->user(),
        ]);
    }
}
