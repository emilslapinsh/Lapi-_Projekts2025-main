<!DOCTYPE html>
<html lang="lv">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Sākums — Automašīnas apkopes un izdevumu sekošana</title>

        <?php // Pieslēdz projekta stilus un JS ?>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="min-h-screen bg-zinc-950 text-zinc-100">
        <?php // Fona “glow” efekts ?>
        <div class="pointer-events-none fixed inset-0">
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900"></div>
            <div class="absolute -top-20 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-red-600/20 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-72 w-72 rounded-full bg-zinc-700/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto min-h-screen max-w-6xl px-6">
            <?php // Augšējā josla ar sveicienu un pogām ?>
            <header class="flex items-center justify-between py-8">
                <?php // Kreisa puse: virsraksts un brīdinājums (ja ir steidzami notikumi) ?>
                <div class="flex items-start justify-between gap-3">
                    <h1 class="text-2xl font-bold tracking-wide text-zinc-100 uppercase">
                        Auto apkopes un izdevumu palīgrīks
                    </h1>

                    @if (($urgentUpcomingCount ?? 0) > 0)
                        <a
                            href="{{ route('calendar') }}"
                            class="mt-1 inline-flex h-9 w-9 items-center justify-center rounded-full bg-red-600 text-sm font-black text-white ring-1 ring-red-400/40 hover:bg-red-500 sm:hidden relative"
                            aria-label="Tuvākie notikumi"
                        >
                            !
                            <span
                                class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-zinc-950 px-1 text-[10px] font-bold text-white ring-1 ring-white/10"
                            >
                                {{ (int) $urgentUpcomingCount }}
                            </span>
                        </a>
                    @endif
                </div>

                <?php // Labā puse: profils, admin, izrakstīšanās ?>
                <div class="flex items-center gap-4">
                    <div class="hidden sm:block text-right">
                        <div class="flex items-center justify-end gap-2">
                            <div class="text-base font-medium text-zinc-100">Sveiki, {{ Auth::user()->username }}</div>

                            @if (($urgentUpcomingCount ?? 0) > 0)
                                <a
                                    href="{{ route('calendar') }}"
                                    title="Tuvākie notikumi"
                                    class="relative inline-flex h-8 w-8 items-center justify-center rounded-full bg-red-600 text-sm font-black text-white ring-1 ring-red-400/40 hover:bg-red-500"
                                >
                                    !
                                    <span
                                        class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-zinc-950 px-1 text-[10px] font-bold text-white ring-1 ring-white/10"
                                    >
                                        {{ (int) $urgentUpcomingCount }}
                                    </span>
                                </a>
                            @endif
                        </div>
                        <div class="text-sm text-zinc-400">Pārskats</div>
                    </div>

                    <?php // Profila poga ?>
                    <a
                        href="{{ route('profile') }}"
                        class="rounded-lg bg-zinc-800 px-5 py-2.5 text-base font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                    >
                        Profils
                    </a>

                    @if (Auth::user()->isAdmin())
                        <a
                            href="{{ route('admin') }}"
                            class="rounded-lg bg-zinc-800 px-5 py-2.5 text-base font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                        >
                            Admin
                        </a>
                    @endif

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button
                            type="submit"
                            class="rounded-lg bg-red-600 px-5 py-2.5 text-base font-semibold text-white hover:bg-red-500"
                        >
                            Iziet
                        </button>
                    </form>
                </div>
            </header>

            <?php // Galvenais saturs ?>
            <main class="pb-12">
                @if (($urgentUpcomingCount ?? 0) > 0)
                    <section class="mb-6 rounded-2xl bg-red-500/10 p-5 ring-1 ring-red-500/25">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-sm font-semibold text-red-200">Tuvākie notikumi</div>
                                <div class="mt-1 text-base text-red-50/90">
                                    Tev ir
                                    <span class="font-semibold">{{ (int) $urgentUpcomingCount }}</span>
                                    tuvākie notikumi tuvākajās 7 dienās.
                                    @if (! empty($nextUrgentTitle))
                                        <span class="text-red-100/80">Nākamais: {{ $nextUrgentTitle }}.</span>
                                    @endif
                                </div>
                            </div>

                            <a
                                href="{{ route('calendar') }}"
                                class="inline-flex items-center justify-center rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-500"
                            >
                                Atvērt kalendāru
                            </a>
                        </div>
                    </section>
                @endif

                <?php // Īss ievads ar ātrajiem linkiem ?>
                <section class="rounded-2xl bg-zinc-900/50 p-8 ring-1 ring-white/10">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-semibold tracking-tight">Jūsu vadības panelis</h2>
                            <p class="mt-2 text-base text-zinc-400">
                                Ātri piekļūstiet kartei, izdevumiem, degvielas datiem un apkopes kalendāram.
                            </p>
                        </div>

                        <div class="mt-4 flex gap-3 sm:mt-0">
                            <a
                                href="{{ url('/karte') }}"
                                class="inline-flex items-center justify-center rounded-lg bg-zinc-800 px-5 py-2.5 text-base font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                            >
                                Atvērt karti
                            </a>
                            <a
                                href="{{ url('/izdevumi') }}"
                                class="inline-flex items-center justify-center rounded-lg bg-red-600 px-5 py-2.5 text-base font-semibold text-white hover:bg-red-500"
                            >
                                Pievienot izdevumu
                            </a>
                        </div>
                    </div>
                </section>

                <?php // Galvenās sadaļas kā kartītes ?>
                <section class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <a
                        href="{{ url('/karte') }}"
                        class="group rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10 transition hover:bg-zinc-900 hover:ring-white/20"
                    >
                        <div class="flex items-center justify-between">
                            <div class="text-base font-semibold">Karte</div>
                            <div class="text-zinc-400 transition group-hover:text-zinc-200">→</div>
                        </div>
                        <p class="mt-3 text-base text-zinc-400">Apskatīt lokācijas un notikumus kartē.</p>
                    </a>

                    <a
                        href="{{ route('degviela.index') }}"
                        class="group rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10 transition hover:bg-zinc-900 hover:ring-white/20"
                    >
                        <div class="flex items-center justify-between">
                            <div class="text-base font-semibold">Degvielas patēriņš</div>
                            <div class="text-zinc-400 transition group-hover:text-zinc-200">→</div>
                        </div>
                        <p class="mt-3 text-base text-zinc-400">Sekot līdzi patēriņam un braucieniem.</p>
                    </a>

                    <a
                        href="{{ url('/izdevumi') }}"
                        class="group rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10 transition hover:bg-zinc-900 hover:ring-white/20"
                    >
                        <div class="flex items-center justify-between">
                            <div class="text-base font-semibold">Izdevumu pārvaldība</div>
                            <div class="text-zinc-400 transition group-hover:text-zinc-200">→</div>
                        </div>
                        <p class="mt-3 text-base text-zinc-400">Pievienot auto, izdevumus un koplietot auto.</p>
                    </a>

                    <a
                        href="{{ url('/calendar') }}"
                        class="group rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10 transition hover:bg-zinc-900 hover:ring-white/20"
                    >
                        <div class="flex items-center justify-between">
                            <div class="text-base font-semibold">Apkopes kalendārs</div>
                            <div class="text-zinc-400 transition group-hover:text-zinc-200">→</div>
                        </div>
                        <p class="mt-3 text-base text-zinc-400">Plānot apkopes un sekot termiņiem.</p>
                    </a>
                </section>
                <?php // Kopsavilkuma rādītāji (skaits, izdevumi, patēriņš, nākamais notikums) ?>
                <section class="mt-8 grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                        <div class="text-sm font-medium text-zinc-400">Auto skaits</div>
                        <div class="mt-3 text-3xl font-bold text-white">
                            {{ $carCount ?? 0 }}
                        </div>
                        <p class="mt-2 text-sm text-zinc-500">Pievienotie transportlīdzekļi sistēmā</p>
                    </div>

                    <div class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                        <div class="text-sm font-medium text-zinc-400">Šī mēneša izdevumi</div>
                        <div class="mt-3 text-3xl font-bold text-white">
                            €{{ number_format($monthlyExpenses ?? 0, 2) }}
                        </div>
                        <p class="mt-2 text-sm text-zinc-500">Kopējie reģistrētie izdevumi šajā mēnesī</p>
                    </div>

                    <div class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                        <div class="text-sm font-medium text-zinc-400">Vidējais patēriņš</div>
                        <div class="mt-3 text-3xl font-bold text-white">
                            {{ $averageFuelConsumption ?? '0.0' }} l/100km
                        </div>
                        <p class="mt-2 text-sm text-zinc-500">Balstīts uz pievienotajiem degvielas ierakstiem</p>
                    </div>

                    <div class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                        <div class="text-sm font-medium text-zinc-400">Nākamā apkope</div>
                        <div class="mt-3 text-xl font-bold text-white">
                            {{ $nextMaintenanceDate ?? 'Nav datu' }}
                        </div>
                        <p class="mt-2 text-sm text-zinc-500">Tuvākais ieplānotais apkopes datums</p>
                    </div>
                </section>

                <?php // Tuvākie notikumi un pēdējās aktivitātes ?>
                <section class="mt-8 grid gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white">Tuvākie notikumi</h3>
                            <a href="{{ url('/calendar') }}" class="text-sm text-red-400 hover:text-red-300">
                                Skatīt visu
                            </a>
                        </div>

                        <div class="mt-5 space-y-4">
                            @forelse ($upcomingEvents ?? [] as $event)
                                <div
                                    class="flex items-start justify-between rounded-xl bg-black/20 px-4 py-4 ring-1 ring-white/5"
                                >
                                    <div>
                                        <div class="font-medium text-white">
                                            {{ $event['title'] }}
                                        </div>
                                        <div class="mt-1 text-sm text-zinc-400">
                                            {{ $event['date'] }}
                                        </div>
                                    </div>

                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-medium {{
                                            ($event['status'] ?? '') === 'steidzami'
                                                ? 'bg-red-500/15 text-red-300'
                                                : 'bg-zinc-800 text-zinc-300'
                                        }}"
                                    >
                                        {{ $event['status'] ?? 'plānots' }}
                                    </span>
                                </div>
                            @empty
                                <div class="rounded-xl bg-black/20 px-4 py-4 text-sm text-zinc-400 ring-1 ring-white/5">
                                    Pašlaik nav neviena ieplānota notikuma.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white">Pēdējās aktivitātes</h3>
                            <a href="{{ url('/izdevumi') }}" class="text-sm text-red-400 hover:text-red-300">Atvērt</a>
                        </div>

                        <div class="mt-5 space-y-4">
                            @forelse ($recentActivities ?? [] as $activity)
                                <div class="rounded-xl bg-black/20 px-4 py-4 ring-1 ring-white/5">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="font-medium text-white">
                                                {{ $activity['title'] }}
                                            </div>
                                            <div class="mt-1 text-sm text-zinc-400">
                                                {{ $activity['subtitle'] }}
                                            </div>
                                        </div>

                                        <div class="whitespace-nowrap text-sm text-zinc-500">
                                            {{ $activity['time'] }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl bg-black/20 px-4 py-4 text-sm text-zinc-400 ring-1 ring-white/5">
                                    Vēl nav nevienas aktivitātes.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <?php // Ātrās darbības ?>
                <section class="mt-8 rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-white">Ātrās darbības</h3>
                            <p class="mt-1 text-sm text-zinc-400"></p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <a
                                href="{{ url('/izdevumi') }}"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500"
                            >
                                Pievienot izdevumu
                            </a>

                            <a
                                href="{{ route('degviela.index') }}"
                                class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                            >
                                Pievienot degvielu
                            </a>

                            <a
                                href="{{ url('/calendar') }}"
                                class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                            >
                                Ieplānot apkopi
                            </a>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
