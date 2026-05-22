<!DOCTYPE html>
<?php // Sākumlapa — īss apraksts iespējām un saites uz pieslēgšanos vai paneli ?>
<html lang="lv">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Laipni lūdzam</title>
        @include('partials.vite-assets')
    </head>

    <body class="min-h-screen bg-zinc-950 text-zinc-100">
        <?php // Fons un glow slānis virs pamatkrāsas ?>
        <div class="pointer-events-none fixed inset-0">
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900"></div>
            <div class="absolute -top-20 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-red-600/20 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-72 w-72 rounded-full bg-zinc-700/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto min-h-screen max-w-6xl px-6">
            <header class="flex flex-col gap-4 py-8 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500"></div>
                    <h1 class="mt-2 text-3xl font-bold uppercase tracking-wide text-zinc-100 sm:text-4xl">
                        Pārvaldi auto izdevumus vienkārši
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-zinc-400">
                        Ātra uzskaite, saprotama analītika un tīrs kopsavilkums. Izdevumi atsevišķi no degvielas,
                        degviela ar patēriņa aprēķinu, apkopes notikumi kalendārā un vietu meklēšana kartē.
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-3">
                    @auth
                        <a
                            href="{{ route('home') }}"
                            class="rounded-lg bg-zinc-800 px-5 py-2.5 text-base font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                        >
                            Uz paneli
                        </a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button
                                type="submit"
                                class="rounded-lg bg-red-600 px-5 py-2.5 text-base font-semibold text-white hover:bg-red-500"
                            >
                                Iziet
                            </button>
                        </form>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="rounded-lg bg-zinc-800 px-5 py-2.5 text-base font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                        >
                            Pieslēgties
                        </a>
                        <a
                            href="{{ route('register') }}"
                            class="rounded-lg bg-red-600 px-5 py-2.5 text-base font-semibold text-white hover:bg-red-500"
                        >
                            Reģistrēties
                        </a>
                    @endauth
                </div>
            </header>

            <main class="pb-12">
                <?php // Trīs kolonnu funkciju kopsavilkums ?>
                <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                    <div class="grid gap-6 lg:grid-cols-3">
                        <div class="rounded-xl bg-zinc-950/40 p-5 ring-1 ring-white/5">
                            <div class="text-sm font-semibold text-zinc-200">Izdevumi</div>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-400">
                                Pievieno izdevumus, filtrē pēc veida un perioda, redzi kopsummas un vienkāršu analītiku. Degviela
                                te nav iekļauta – tā ir atsevišķā sadaļā.
                            </p>
                        </div>

                        <div class="rounded-xl bg-zinc-950/40 p-5 ring-1 ring-white/5">
                            <div class="text-sm font-semibold text-zinc-200">Degviela</div>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-400">
                                Uzpildes žurnāls ar patēriņu (pilna bāka metode) un grafiku. Ērti, ja gribi saprast izmaksas laika
                                gaitā.
                            </p>
                        </div>

                        <div class="rounded-xl bg-zinc-950/40 p-5 ring-1 ring-white/5">
                            <div class="text-sm font-semibold text-zinc-200">Kalendārs & karte</div>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-400">
                                Plāno apkopes notikumus kalendārā un atrodi noderīgas vietas kartē (servisi, uzpildes stacijas u. c.).
                            </p>
                        </div>
                    </div>
                </section>

                <?php // Kā sākt un ieguvumi divās kolonnās ?>
                <section class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                        <h2 class="text-lg font-semibold text-zinc-100">Kā sākt</h2>
                        <ol class="mt-4 space-y-2 text-sm text-zinc-400">
                            <li class="flex gap-2">
                                <span class="text-red-400">1.</span>
                                <span>Reģistrējies un pievieno auto.</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-red-400">2.</span>
                                <span>Pievieno izdevumus un uzpildes, lai veidotos vēsture un kopsavilkums.</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-red-400">3.</span>
                                <span>Ja vajag, koplieto auto ar citu lietotāju.</span>
                            </li>
                        </ol>
                    </div>

                    <div class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                        <h2 class="text-lg font-semibold text-zinc-100">Kāpēc tas ir ērti</h2>
                        <ul class="mt-4 space-y-2 text-sm text-zinc-400">
                            <li class="flex gap-2">
                                <span class="text-red-400">•</span>
                                <span>Vienoti datumu ievades lauki un skaidri paziņojumi par darbību rezultātiem.</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-red-400">•</span>
                                <span>
                                    Excel eksports – formatēta tabula ar kolonnām, ērti lasāma un saglabājama kā rezerves kopija.
                                </span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-red-400">•</span>
                                <span>Analītika bez “lieka trokšņa” – fokusā ir kopsummas un tendences.</span>
                            </li>
                        </ul>
                    </div>
                </section>

                @guest
                    <section class="mt-6 rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-zinc-100">Esi gatavs sākt?</h2>
                                <p class="mt-1 text-sm text-zinc-400">
                                    Izveido kontu vai pieslēdzies – tas aizņem mazāk nekā minūti.
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a
                                    href="{{ route('login') }}"
                                    class="rounded-xl bg-zinc-800 px-5 py-3 text-sm font-semibold ring-1 ring-white/10 hover:bg-zinc-700"
                                >
                                    Pieslēgties
                                </a>
                                <a
                                    href="{{ route('register') }}"
                                    class="rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-500"
                                >
                                    Reģistrēties
                                </a>
                            </div>
                        </div>
                    </section>
                @endguest
            </main>
        </div>
    </body>
</html>
