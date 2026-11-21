<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#edf5fa">

        <title>{{ isset($title) ? $title.' · SIPERIZ' : 'SIPERIZ' }}</title>

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
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="rounded-2xl" aria-label="SIPERIZ, ke beranda">
                    <x-brand :with-text="true" class="h-9 w-9" />
                </a>

                <div class="flex items-center gap-2">
                    <span class="pill bg-success-soft text-success max-sm:hidden">
                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                        Sistem internal
                    </span>
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
                </div>
            </header>

            <main class="flex flex-1 items-center justify-center px-4 pb-10 pt-2 sm:px-6">
                {{ $slot }}
            </main>

            <footer class="px-4 pb-6 text-center text-[11px] text-muted sm:px-8">
                <p>SIPERIZ &copy; {{ now()->year }} &middot; Pemantau masa berlaku izin perusahaan</p>
                <p class="mt-1">Dibuat oleh Yogi Prayoga &middot; Akses terbatas untuk pengguna terdaftar.</p>
            </footer>
        </div>
    </body>
</html>
