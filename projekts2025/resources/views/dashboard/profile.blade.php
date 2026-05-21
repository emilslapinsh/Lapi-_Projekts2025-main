<!DOCTYPE html>
<?php // Sesijas profils - lasāmi identifikācijas dati bez rediģēšanas formas ?>
<html lang="lv">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Profils</title>
        @include('partials.vite-assets')
    </head>

    <body class="min-h-screen bg-zinc-950 text-zinc-100">
        <div class="pointer-events-none fixed inset-0">
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900"></div>
            <div class="absolute -top-20 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-red-600/20 blur-3xl"></div>
        </div>

        <div class="relative mx-auto min-h-screen max-w-3xl px-6">
            <header class="flex items-center justify-between py-8">
                <h1 class="text-2xl font-bold uppercase tracking-wide">Lietotāja profils</h1>

                <a
                    href="{{ route('home') }}"
                    class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-semibold ring-1 ring-white/10 hover:bg-zinc-700"
                >
                    Atpakaļ
                </a>
            </header>

            <main>
                <div class="rounded-2xl bg-zinc-900/50 p-8 ring-1 ring-white/10 space-y-6">
                    <div>
                        <div class="text-xs text-zinc-500 uppercase tracking-wide">Lietotājvārds</div>
                        <div class="mt-1 text-lg font-semibold text-zinc-100">
                            {{ Auth::user()->username }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-zinc-500 uppercase tracking-wide">E-pasts</div>
                        <div class="mt-1 text-lg font-semibold text-zinc-100">
                            {{ Auth::user()->email }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-zinc-500 uppercase tracking-wide">Reģistrācijas datums</div>
                        <div class="mt-1 text-lg font-semibold text-zinc-100">
                            {{ Auth::user()->created_at->format('d.m.Y H:i') }}
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
