<?php

use App\Http\Controllers\PermitFileController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\WahaWebhookController;
use App\Livewire\Audit\Index as AuditIndex;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Categories\Index as CategoriesIndex;
use App\Livewire\Contacts\Index as ContactsIndex;
use App\Livewire\Dashboard;
use App\Livewire\Notifications\Deliveries;
use App\Livewire\Notifications\Inbox;
use App\Livewire\Notifications\Rules;
use App\Livewire\Notifications\Templates;
use App\Livewire\Permits\Form;
use App\Livewire\Permits\Index as PermitsIndex;
use App\Livewire\Permits\Renew;
use App\Livewire\Permits\Show;
use App\Livewire\Profile;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Settings\Application;
use App\Livewire\Settings\Users;
use App\Livewire\Settings\Waha;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('masuk', Login::class)->name('login');
    Route::get('lupa-kata-sandi', ForgotPassword::class)->name('password.request');
    Route::get('atur-ulang-kata-sandi/{token}', ResetPassword::class)->name('password.reset');
});

Route::post('keluar', function () {
    \App\Models\ActivityLog::record('auth.logout', 'Keluar dari aplikasi');
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    Route::get('izin', PermitsIndex::class)->name('permits.index');
    Route::get('izin/tambah', Form::class)->middleware('can:manage-permits')->name('permits.create');
    Route::get('izin/{permit}', Show::class)->name('permits.show');
    Route::get('izin/{permit}/ubah', Form::class)->middleware('can:manage-permits')->name('permits.edit');
    Route::get('izin/{permit}/perpanjang', Renew::class)->middleware('can:manage-permits')->name('permits.renew');
    Route::get('izin/file/{permitFile}', PermitFileController::class)->middleware('signed')->name('permits.files.download');

    Route::get('kategori', CategoriesIndex::class)->middleware('can:manage-categories')->name('categories.index');
    Route::get('notifikasi/kontak', ContactsIndex::class)->middleware('can:manage-contacts')->name('contacts.index');
    Route::get('notifikasi/aturan', Rules::class)->middleware('can:manage-reminders')->name('notifications.rules');
    Route::get('notifikasi/template', Templates::class)->middleware('can:manage-reminders')->name('notifications.templates');
    Route::get('notifikasi/riwayat', Deliveries::class)->middleware('can:view-deliveries')->name('notifications.deliveries');
    Route::get('notifikasi', Inbox::class)->name('notifications.inbox');

    Route::get('laporan', ReportsIndex::class)->name('reports.index');
    Route::get('laporan/izin/export', [ReportExportController::class, 'permits'])->name('reports.permits.export');
    Route::get('laporan/reminder/export', [ReportExportController::class, 'deliveries'])->middleware('can:view-deliveries')->name('reports.deliveries.export');

    Route::get('pengaturan/waha', Waha::class)->middleware('can:manage-app')->name('settings.waha');
    Route::get('pengaturan/pengguna', Users::class)->middleware('can:manage-users')->name('settings.users');
    Route::get('pengaturan/aplikasi', Application::class)->middleware('can:manage-app')->name('settings.application');
    Route::get('audit', AuditIndex::class)->middleware('can:view-audit')->name('audit.index');
    Route::get('profil', Profile::class)->name('profile');
});

Route::post('api/webhooks/waha', WahaWebhookController::class)->name('webhooks.waha');
