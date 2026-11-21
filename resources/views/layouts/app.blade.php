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

        <script>
            (function () {
                if (localStorage.getItem('siperiz.sidebar') === 'closed') {
                    document.documentElement.dataset.sidebar = 'closed';
                }
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="mesh min-h-screen font-sans text-ink antialiased">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-full focus:bg-primary focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-on-primary">
            Lewati ke konten utama
        </a>

        <div x-data="{ mobileOpen: false }" x-on:keydown.escape.window="mobileOpen = false" class="flex min-h-screen flex-col">
            <x-navigation />

            <div class="app-shell flex min-w-0 flex-1 flex-col">
                <x-topbar />

                <main id="main-content" class="mx-auto w-full max-w-[1800px] flex-1 px-4 pb-12 pt-3 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>

                <footer class="mx-auto w-full max-w-[1800px] px-4 pb-6 sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-1 border-t border-line pt-4 text-[11px] text-muted sm:flex-row sm:items-center sm:justify-between">
                        <p>SIPERIZ &copy; {{ now()->year }} &middot; Pemantau masa berlaku izin perusahaan</p>
                        <p>Dibuat oleh <span class="font-semibold text-ink-2">Yogi Prayoga</span></p>
                    </div>
                </footer>
            </div>
        </div>
    </body>
</html>
