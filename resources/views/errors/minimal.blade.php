@php
    $status = $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
        ? $exception->getStatusCode()
        : 500;

    [$heading, $description] = match ($status) {
        403 => ['Akses ditolak', 'Akun Anda tidak memiliki izin untuk membuka halaman ini.'],
        404 => ['Halaman tidak ditemukan', 'Alamat yang Anda buka tidak tersedia atau sudah dipindahkan.'],
        419 => ['Sesi kedaluwarsa', 'Sesi Anda sudah berakhir. Muat ulang halaman lalu coba lagi.'],
        429 => ['Terlalu banyak permintaan', 'Tunggu sebentar sebelum mencoba lagi.'],
        default => ['Terjadi kesalahan', 'Sistem belum dapat memproses permintaan ini. Coba lagi beberapa saat lagi.'],
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#edf5fa">

        <title>{{ $heading }} &middot; SIPERIZ</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <script>
            (function () {
                const stored = localStorage.getItem('theme');
                const dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="mesh min-h-screen font-sans text-ink antialiased">
        <div class="flex min-h-screen flex-col">
            <header class="flex items-center justify-between gap-4 px-4 py-5 sm:px-8">
                <a href="{{ url('/') }}" class="rounded-2xl" aria-label="SIPERIZ, ke beranda">
                    <x-brand :with-text="true" class="h-9 w-9" />
                </a>

                <button
                    type="button"
                    x-data="{ dark: document.documentElement.classList.contains('dark') }"
                    x-on:click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('theme', dark ? 'dark' : 'light')"
                    :aria-label="dark ? 'Aktifkan tema terang' : 'Aktifkan tema gelap'"
                    class="icon-button"
                >
                    <x-icon name="sun" x-show="!dark" />
                    <x-icon name="moon" x-show="dark" x-cloak />
                </button>
            </header>

            <main class="flex flex-1 items-center justify-center px-4 pb-10 pt-2 sm:px-6">
                <div class="card w-full max-w-[460px] p-6 text-center sm:p-8">
                    <span class="pill bg-danger-soft text-danger">Error {{ $status }}</span>
                    <h1 class="mt-4 text-[24px] font-bold tracking-[-0.03em] text-ink">{{ $heading }}</h1>
                    <p class="mx-auto mt-2 max-w-sm text-[12.5px] leading-5 text-muted">{{ $description }}</p>

                    <a href="{{ url('/') }}" class="btn-primary mt-6 w-full">
                        <x-icon name="arrow-left" :size="16" />
                        Kembali ke SIPERIZ
                    </a>

                    <p class="mt-4 text-[11px] text-muted">Jika masalah berlanjut, hubungi admin aplikasi.</p>
                </div>
            </main>

            <footer class="px-4 pb-6 text-center text-[11px] text-muted sm:px-8">
                <p>SIPERIZ &copy; {{ now()->year }} &middot; Pemantau masa berlaku izin perusahaan</p>
                <p class="mt-1">Akses terbatas untuk pengguna terdaftar.</p>
            </footer>
        </div>
    </body>
</html>
