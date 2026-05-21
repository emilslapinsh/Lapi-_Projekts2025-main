<!DOCTYPE html>
<html lang="lv">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Izdevumu pārvaldība</title>
        <?php // Pieslēdz projekta stilus un JS ?>
        @include('partials.vite-assets')
        <?php // Flatpickr stili datumu izvēlei ?>
        @include('partials.flatpickr-lv-head')
    </head>

    <body class="min-h-screen bg-zinc-950 text-zinc-100">
        <?php // Fona gradients un gaismas efekti ?>
        <div class="pointer-events-none fixed inset-0">
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900"></div>
            <div class="absolute -top-20 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-red-600/20 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-72 w-72 rounded-full bg-zinc-700/10 blur-3xl"></div>
        </div>

        <?php // Palīgmainīgie tabiem, filtriem un analītikas stabiņiem ?>
        @php
            $tab = $tab ?? 'izdevumi';
            $maxBar = ! empty($insights['monthly_bars']) ? max(1.0, (float) collect($insights['monthly_bars'])->max('total')) : 1.0;
            $preserve = array_filter(request()->only(['tab', 'car_id', 'type', 'period', 'date_from', 'date_to', 'sort']), fn ($v) => $v !== null && $v !== '');
            $queryForTab = array_filter(request()->only(['car_id', 'type', 'period', 'date_from', 'date_to', 'sort']), fn ($v) => $v !== null && $v !== '');
        @endphp

        <div class="relative mx-auto min-h-screen max-w-6xl px-6">
            <?php // Galvene ar aprakstu un navigāciju ?>
            <header class="flex flex-col gap-4 py-8 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-wide text-zinc-100 uppercase">Izdevumu pārvaldība</h1>
                    <p class="mt-1 max-w-2xl text-sm text-zinc-400">
                        Izdevumu žurnāls, kopsavilkums un analītika. Degvielu reģistrē atsevišķi sadaļā
                        <a
                            href="{{ $selectedCar ? route('degviela.index', ['car_id' => $selectedCar->id]) : route('degviela.index') }}"
                            class="font-semibold text-red-400 hover:text-red-300"
                        >
                            Degvielas patēriņš
                        </a>
                        .
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-3">
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
                </div>
            </header>

            <main class="pb-12">
                <?php // Paziņojums par veiksmīgu darbību ?>
                @if (session('success'))
                    <div class="mb-6 rounded-2xl bg-emerald-500/10 p-4 ring-1 ring-emerald-500/20">
                        <div class="text-sm font-semibold text-emerald-200">Veiksmīgi</div>
                        <div class="mt-1 text-sm text-emerald-100/80">{{ session('success') }}</div>
                    </div>
                @endif

                <?php // Paziņojums par kļūdu ?>
                @if (session('error'))
                    <div class="mb-6 rounded-2xl bg-red-500/10 p-4 ring-1 ring-red-500/20">
                        <div class="text-sm font-semibold text-red-200">Uzmanību</div>
                        <div class="mt-1 text-sm text-red-100/80">{{ session('error') }}</div>
                    </div>
                @endif

                <?php // Validācijas kļūdas ?>
                @if ($errors->any())
                    <div class="mb-6 rounded-2xl bg-red-500/10 p-4 ring-1 ring-red-500/20">
                        <div class="text-sm font-semibold text-red-200">Kļūda</div>
                        <ul class="mt-2 space-y-1 text-sm text-red-100/80">
                            @foreach ($errors->all() as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <?php // Gaidošie koplietošanas pieprasījumi ?>
                @if (isset($pendingCars) && $pendingCars->count() > 0)
                    <section class="mb-6 rounded-2xl bg-amber-500/10 p-6 ring-1 ring-amber-500/25">
                        <h2 class="text-lg font-semibold text-amber-100">Koplietošanas pieprasījumi</h2>
                        <p class="mt-1 text-sm text-amber-100/70">
                            Tev ir uzaicinājums koplietot auto. Apstiprinot, redzēsi šī auto izdevumus savā kontā.
                        </p>
                        <ul class="mt-4 space-y-3">
                            @foreach ($pendingCars as $pc)
                                <li
                                    class="flex flex-col gap-3 rounded-xl bg-zinc-950/40 p-4 ring-1 ring-white/10 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="text-sm text-zinc-200">
                                        <span class="font-semibold">{{ $pc->brand }} {{ $pc->model }}</span>
                                        <span class="text-zinc-500">({{ $pc->year }})</span>
                                    </div>
                                    <form method="POST" action="{{ route('cars.confirm', $pc) }}" class="shrink-0">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
                                        >
                                            Apstiprināt koplietošanu
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <?php // Tabu josla (izdevumi / auto) ?>
                <nav class="mb-6 flex gap-2 rounded-2xl bg-zinc-900/50 p-2 ring-1 ring-white/10">
                    <a
                        href="{{ route('izdevumi.index', array_merge($queryForTab, ['tab' => 'izdevumi'])) }}"
                        class="flex-1 rounded-xl px-4 py-3 text-center text-sm font-semibold transition {{ $tab === 'izdevumi' ? 'bg-red-600 text-white' : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100' }}"
                    >
                        Izdevumi un analītika
                    </a>
                    <a
                        href="{{ route('izdevumi.index', array_merge($queryForTab, ['tab' => 'auto'])) }}"
                        class="flex-1 rounded-xl px-4 py-3 text-center text-sm font-semibold transition {{ $tab === 'auto' ? 'bg-red-600 text-white' : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100' }}"
                    >
                        Auto un koplietošana
                    </a>
                </nav>

                <?php // Izdevumu tabs (saraksts, filtri, analītika) ?>
                <div class="{{ $tab === 'izdevumi' ? '' : 'hidden' }} space-y-6">
                    @if ($cars->isEmpty())
                        <?php // Ja nav pievienots neviens auto, rāda pamācību ?>
                        <section class="rounded-2xl bg-zinc-900/50 p-8 text-center ring-1 ring-white/10">
                            <p class="text-sm text-zinc-400">
                                Vēl nav pievienots neviens auto. Sadaļā
                                <a
                                    href="{{ route('izdevumi.index', ['tab' => 'auto']) }}"
                                    class="font-semibold text-red-400 hover:text-red-300"
                                >
                                    Auto un koplietošana
                                </a>
                                pievieno transportlīdzekli, lai sāktu reģistrēt izdevumus.
                            </p>
                        </section>
                    @else
                        <?php // Auto izvēle izdevumu tabā ?>
                        <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                            <h2 class="text-lg font-semibold text-zinc-100">Izvēlētais auto</h2>
                            <form
                                method="GET"
                                action="{{ route('izdevumi.index') }}"
                                class="mt-4 flex flex-wrap items-end gap-4"
                            >
                                <?php // Saglabā pārējos filtrus, kad maina auto ?>
                                <input type="hidden" name="tab" value="izdevumi" />
                                @foreach (['type', 'period', 'date_from', 'date_to', 'sort'] as $qk)
                                    @if (request()->filled($qk))
                                        <input type="hidden" name="{{ $qk }}" value="{{ request($qk) }}" />
                                    @endif
                                @endforeach

                                <div class="min-w-[240px] flex-1">
                                    <label class="text-sm font-semibold text-zinc-200">Auto</label>
                                    <select
                                        name="car_id"
                                        onchange="this.form.submit()"
                                        class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                    >
                                        @foreach ($cars as $car)
                                            <option
                                                value="{{ $car->id }}"
                                                @selected($selectedCar && (int) $selectedCar->id === (int) $car->id)
                                            >
                                                {{ $car->brand }} {{ $car->model }} ({{ $car->year }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>
                        </section>

                        <?php // Kopsavilkuma kartītes (kopā, mēnesis, €/km, pēdējais nobraukums) ?>
                        <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                            <h2 class="text-lg font-semibold text-zinc-100">Kopsavilkums</h2>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div class="rounded-xl bg-zinc-950/50 p-4 ring-1 ring-white/5">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                        Kopā (visi ieraksti)
                                    </div>
                                    <div class="mt-2 text-2xl font-bold tabular-nums text-zinc-100">
                                        {{ number_format($stats['total'], 2) }} €
                                    </div>
                                </div>
                                <div class="rounded-xl bg-zinc-950/50 p-4 ring-1 ring-white/5">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                        Šis mēnesis
                                    </div>
                                    <div class="mt-2 text-2xl font-bold tabular-nums text-zinc-100">
                                        {{ number_format($stats['month'], 2) }} €
                                    </div>
                                </div>
                                <div class="rounded-xl bg-zinc-950/50 p-4 ring-1 ring-white/5">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                        € / km (aptuveni)
                                    </div>
                                    <div class="mt-2 text-2xl font-bold tabular-nums text-zinc-100">
                                        {{ $stats['per_km'] !== null ? number_format($stats['per_km'], 4) . ' €' : '-' }}
                                    </div>
                                </div>
                                <div class="rounded-xl bg-zinc-950/50 p-4 ring-1 ring-white/5">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                        Pēdējais nobraukums
                                    </div>
                                    <div class="mt-2 text-2xl font-bold tabular-nums text-zinc-100">
                                        {{ $stats['last_mileage'] !== null ? number_format($stats['last_mileage']) . ' km' : '-' }}
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <a
                                    href="{{ route('expenses.export', ['car_id' => $selectedCar->id]) }}"
                                    class="inline-flex rounded-xl bg-zinc-800 px-4 py-2.5 text-sm font-semibold ring-1 ring-white/10 hover:bg-zinc-700"
                                >
                                    Eksportēt
                                </a>
                                <a
                                    href="{{ route('degviela.index', ['car_id' => $selectedCar->id]) }}"
                                    class="inline-flex rounded-xl bg-zinc-800 px-4 py-2.5 text-sm font-semibold ring-1 ring-white/10 hover:bg-zinc-700"
                                >
                                    Uz degvielas žurnālu
                                </a>
                            </div>
                        </section>

                        <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                            <h2 class="text-lg font-semibold text-zinc-100">Analītika</h2>
                            <p class="mt-1 text-sm text-zinc-400">Pēdējie 6 mēneši</p>

                            <div class="mt-4 space-y-3">
                                @foreach ($insights['monthly_bars'] as $bar)
                                    @php
                                        $w = $maxBar > 0 ? round(100 * ($bar['total'] / $maxBar), 2) : 0;
                                    @endphp

                                    <div>
                                        <div class="flex justify-between text-xs text-zinc-400">
                                            <span>{{ $bar['label'] }}</span>
                                            <span class="tabular-nums font-semibold text-zinc-200">
                                                {{ number_format($bar['total'], 2) }} €
                                            </span>
                                        </div>
                                        <div
                                            class="mt-1 h-2 overflow-hidden rounded-full bg-zinc-950/80 ring-1 ring-white/5"
                                        >
                                            <div
                                                class="h-full rounded-full bg-red-600/80 transition-all"
                                                style="width: {{ $w }}%"
                                            ></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-6 grid gap-4 border-t border-white/10 pt-6 lg:grid-cols-2">
                                <div class="rounded-xl bg-zinc-950/40 p-4 ring-1 ring-white/5">
                                    <div class="text-sm font-semibold text-zinc-200">Filtrētā atlase</div>
                                    <p class="mt-2 text-sm text-zinc-400">
                                        <span class="tabular-nums font-semibold text-zinc-100">
                                            {{ $insights['count_filtered'] }}
                                        </span>
                                        ieraksti, kopsumma
                                        <span class="tabular-nums font-semibold text-zinc-100">
                                            {{ number_format($filteredSubtotal, 2) }} €
                                        </span>
                                        .
                                    </p>
                                    @if ($insights['biggest_filtered'])
                                        @php
                                            $bg = $insights['biggest_filtered'];
                                        @endphp

                                        <p class="mt-2 text-sm text-zinc-400">
                                            Lielākais izdevums atlases ietvaros:
                                            <span class="font-semibold text-zinc-200">
                                                {{ number_format($bg->amount, 2) }} €
                                            </span>
                                            ({{ $bg->type }}, {{ $bg->date->format('Y-m-d') }}).
                                        </p>
                                    @endif
                                </div>
                                <div class="rounded-xl bg-zinc-950/40 p-4 ring-1 ring-white/5">
                                    <div class="text-sm font-semibold text-zinc-200">Sadalījums pēc veida</div>
                                    @if ($filterTypeTotals->isEmpty())
                                        <p class="mt-2 text-sm text-zinc-500">Nav datu šim filtram.</p>
                                    @else
                                        <ul class="mt-2 space-y-2 text-sm">
                                            @foreach ($filterTypeTotals as $row)
                                                <li class="flex justify-between gap-2 text-zinc-400">
                                                    <span>{{ $row->type }}</span>
                                                    <span class="tabular-nums font-semibold text-zinc-200">
                                                        {{ number_format((float) $row->total, 2) }} €
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        </section>

                        <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                            <h2 class="text-lg font-semibold text-zinc-100">Filtrs un kārtošana</h2>
                            <form
                                method="GET"
                                action="{{ route('izdevumi.index') }}"
                                class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                <input type="hidden" name="tab" value="izdevumi" />
                                <input type="hidden" name="car_id" value="{{ $selectedCar->id }}" />

                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Veids</label>
                                    <select
                                        name="type"
                                        class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                    >
                                        <option value="" @selected($filterType === '')>Visi</option>
                                        @foreach ($distinctTypes as $dt)
                                            <option
                                                value="{{ $dt }}"
                                                @selected((string) $filterType === (string) $dt)
                                            >
                                                {{ $dt }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Periods</label>
                                    <select
                                        name="period"
                                        class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                    >
                                        <option value="all" @selected($filterPeriod === 'all')>Viss laiks</option>
                                        <option value="this_month" @selected($filterPeriod === 'this_month')>
                                            Šis mēnesis
                                        </option>
                                        <option value="this_year" @selected($filterPeriod === 'this_year')>
                                            Šis gads
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Kārtot pēc</label>
                                    <select
                                        name="sort"
                                        class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                    >
                                        <option value="date_desc" @selected($sort === 'date_desc')>
                                            Datums - jaunākie pirmie
                                        </option>
                                        <option value="date_asc" @selected($sort === 'date_asc')>
                                            Datums - vecākie pirmie
                                        </option>
                                        <option value="amount_desc" @selected($sort === 'amount_desc')>
                                            Summa - lielākās pirmās
                                        </option>
                                        <option value="amount_asc" @selected($sort === 'amount_asc')>
                                            Summa - mazākās pirmās
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Datums no</label>
                                    <input
                                        type="text"
                                        name="date_from"
                                        value="{{ $filterDateFrom }}"
                                        autocomplete="off"
                                        inputmode="none"
                                        placeholder="dd.mm.gggg"
                                        class="js-flatpickr mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                    />
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Datums līdz</label>
                                    <input
                                        type="text"
                                        name="date_to"
                                        value="{{ $filterDateTo }}"
                                        autocomplete="off"
                                        inputmode="none"
                                        placeholder="dd.mm.gggg"
                                        class="js-flatpickr mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                    />
                                </div>
                                <div class="flex items-end gap-2">
                                    <button
                                        type="submit"
                                        class="w-full rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white hover:bg-red-500 sm:w-auto"
                                    >
                                        Piemērot
                                    </button>
                                    <a
                                        href="{{ route('izdevumi.index', ['tab' => 'izdevumi', 'car_id' => $selectedCar->id]) }}"
                                        class="inline-flex w-full items-center justify-center rounded-xl bg-zinc-800 px-4 py-3 text-sm font-semibold ring-1 ring-white/10 hover:bg-zinc-700 sm:w-auto"
                                    >
                                        Notīrīt
                                    </a>
                                </div>
                            </form>
                        </section>

                        <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                            <h2 class="text-lg font-semibold text-zinc-100">Pievienot izdevumu</h2>
                            <p class="mt-1 text-sm text-zinc-400">Izvēlies veidu un aizpildi laukus.</p>

                            <form method="POST" action="{{ route('expenses.store') }}" class="mt-6 space-y-4">
                                @csrf
                                <input type="hidden" name="car_id" value="{{ $selectedCar->id }}" />
                                <input type="hidden" name="tab" value="izdevumi" />
                                @if (request()->filled('type'))
                                    <input type="hidden" name="filter_type" value="{{ request('type') }}" />
                                @endif

                                @foreach (['period', 'date_from', 'date_to', 'sort'] as $qk)
                                    @if (request()->filled($qk))
                                        <input type="hidden" name="{{ $qk }}" value="{{ request($qk) }}" />
                                    @endif
                                @endforeach

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="text-sm font-semibold text-zinc-200">Veids</label>
                                        <select
                                            name="type"
                                            id="add_expense_type"
                                            required
                                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                        >
                                            @foreach ($expenseTypes as $t)
                                                <option value="{{ $t }}" @selected(old('type') === $t)>
                                                    {{ $t }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p
                                            id="add_type_hint"
                                            class="mt-2 min-h-[2.5rem] text-xs leading-relaxed text-zinc-500"
                                        ></p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-zinc-200">Datums</label>
                                        <input
                                            type="text"
                                            name="date"
                                            required
                                            value="{{ old('date', now()->toDateString()) }}"
                                            autocomplete="off"
                                            inputmode="none"
                                            placeholder="Izvēlies datumu…"
                                            class="js-flatpickr mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                        />
                                    </div>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="text-sm font-semibold text-zinc-200">Summa (€)</label>
                                        <input
                                            type="number"
                                            name="amount"
                                            step="0.01"
                                            min="0"
                                            required
                                            value="{{ old('amount') }}"
                                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-zinc-200">Nobraukums (km)</label>
                                        <input
                                            type="number"
                                            name="mileage"
                                            min="0"
                                            value="{{ old('mileage') }}"
                                            placeholder="nav obligāts"
                                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Apraksts</label>
                                    <input
                                        type="text"
                                        name="description"
                                        maxlength="255"
                                        value="{{ old('description') }}"
                                        class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    class="rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-500"
                                >
                                    Saglabāt izdevumu
                                </button>
                            </form>
                        </section>

                        <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <h2 class="text-lg font-semibold text-zinc-100">Izdevumu tabula</h2>
                                @if ($expensesPaginated)
                                    <div class="text-sm text-zinc-400">{{ $expensesPaginated->total() }} ieraksti</div>
                                @endif
                            </div>

                            <div class="mt-4 overflow-x-auto">
                                <table class="w-full min-w-[720px] text-sm">
                                    <thead
                                        class="text-left text-xs font-semibold uppercase tracking-wide text-zinc-500"
                                    >
                                        <tr class="border-b border-white/10">
                                            <th class="py-2 pr-3">Datums</th>
                                            <th class="py-2 pr-3">Tips</th>
                                            <th class="py-2 pr-3">Summa</th>
                                            <th class="py-2 pr-3">Nobraukums</th>
                                            <th class="py-2 pr-3">Apraksts</th>
                                            <th class="py-2 pr-3">Pievienoja</th>
                                            <th class="py-2 text-right">Darbības</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($expensesPaginated ?? collect() as $e)
                                            <tr class="border-b border-white/5">
                                                <td class="py-3 whitespace-nowrap">{{ $e->date->format('Y-m-d') }}</td>
                                                <td class="py-3 whitespace-nowrap">{{ $e->type }}</td>
                                                <td class="py-3 whitespace-nowrap tabular-nums">
                                                    {{ number_format($e->amount, 2) }} €
                                                </td>
                                                <td class="py-3 whitespace-nowrap">
                                                    {{ $e->mileage ? $e->mileage . ' km' : '-' }}
                                                </td>
                                                <td
                                                    class="py-3 max-w-[200px] truncate text-zinc-300"
                                                    title="{{ $e->description }}"
                                                >
                                                    {{ $e->description ?? '-' }}
                                                </td>
                                                <td class="py-3 whitespace-nowrap text-zinc-400">
                                                    {{ $e->user->username ?? '-' }}
                                                </td>
                                                <td class="py-3 text-right whitespace-nowrap">
                                                    <div class="flex flex-wrap justify-end gap-2">
                                                        <a
                                                            href="#"
                                                            class="js-edit-expense rounded-lg bg-zinc-800 px-3 py-2 text-xs font-semibold ring-1 ring-white/10 hover:bg-zinc-700"
                                                            data-expense-id="{{ $e->id }}"
                                                            data-expense-type="{{ $e->type }}"
                                                            data-expense-date="{{ $e->date->format('Y-m-d') }}"
                                                            data-expense-amount="{{ $e->amount }}"
                                                            data-expense-mileage="{{ $e->mileage ?? '' }}"
                                                            data-expense-description="{{ $e->description ?? '' }}"
                                                        >
                                                            Labot
                                                        </a>
                                                        <form
                                                            method="POST"
                                                            action="{{ route('expenses.destroy', $e->id) }}"
                                                            class="inline"
                                                            onsubmit="return confirm('Dzēst šo izdevumu?');"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            @foreach ($preserve as $k => $v)
                                                                <input
                                                                    type="hidden"
                                                                    name="{{ $k }}"
                                                                    value="{{ $v }}"
                                                                />
                                                            @endforeach

                                                            <button
                                                                type="submit"
                                                                class="rounded-lg bg-zinc-800 px-3 py-2 text-xs font-semibold ring-1 ring-white/10 hover:bg-zinc-700"
                                                            >
                                                                Dzēst
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="py-8 text-center text-zinc-400">
                                                    Nav izdevumu, kas atbilst filtram.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($expensesPaginated && $expensesPaginated->hasPages())
                                <div class="mt-4 text-sm text-zinc-400">
                                    {{ $expensesPaginated->links() }}
                                </div>
                            @endif
                        </section>
                    @endif
                </div>

                <?php // Auto tabs (pievienošana, koplietošana, dzēšana) ?>
                <div class="{{ $tab === 'auto' ? '' : 'hidden' }} space-y-6">
                    <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                        <h2 class="text-lg font-semibold text-zinc-100">Pievienot auto</h2>
                        <p class="mt-1 text-sm text-zinc-400">Jaunais auto tiek automātiski saistīts ar tavu kontu.</p>

                        <form method="POST" action="{{ route('izdevumi.store') }}" class="mt-6 space-y-4">
                            @csrf
                            <input type="hidden" name="tab" value="auto" />
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Marka</label>
                                    <input
                                        name="brand"
                                        required
                                        placeholder="Piem.: Audi"
                                        value="{{ old('brand') }}"
                                        class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                    />
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Modelis</label>
                                    <input
                                        name="model"
                                        required
                                        placeholder="Piem.: A6"
                                        value="{{ old('model') }}"
                                        class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                    />
                                </div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Gads</label>
                                    <input
                                        type="number"
                                        name="year"
                                        required
                                        min="1900"
                                        max="2099"
                                        placeholder="1900–2099"
                                        value="{{ old('year') }}"
                                        class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                    />
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Nobraukums (km)</label>
                                    <input
                                        type="number"
                                        name="mileage"
                                        required
                                        min="0"
                                        placeholder="Piem.: 200000"
                                        value="{{ old('mileage') }}"
                                        class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                    />
                                </div>
                            </div>
                            <button
                                type="submit"
                                class="w-full rounded-xl bg-zinc-800 px-5 py-3 text-base font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                            >
                                Pievienot auto
                            </button>
                        </form>
                    </section>

                    <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                        <h2 class="text-lg font-semibold text-zinc-100">Koplietot auto</h2>
                        <p class="mt-1 text-sm text-zinc-400">
                            Nosūti uzaicinājumu citam reģistrētam lietotājam. Viņš redzēs pieprasījumu augšā un varēs to
                            apstiprināt.
                        </p>

                        @if ($cars->isEmpty())
                            <div class="mt-4 text-sm text-zinc-500">Vispirms pievieno auto.</div>
                        @else
                            <form method="POST" action="#" id="shareForm" class="mt-6 space-y-4">
                                @csrf
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Auto</label>
                                    <select
                                        name="car_id"
                                        id="share_car_id"
                                        required
                                        class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                    >
                                        @foreach ($cars as $car)
                                            <option value="{{ $car->id }}">
                                                {{ $car->brand }} {{ $car->model }} ({{ $car->year }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Lietotāja e-pasts</label>
                                    <input
                                        type="email"
                                        name="user_email"
                                        id="share_user_email"
                                        required
                                        placeholder="piemers@epasts.lv"
                                        class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    class="w-full rounded-xl bg-zinc-800 px-5 py-3 text-base font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                                >
                                    Nosūtīt koplietošanas pieprasījumu
                                </button>

                                <div id="shareResult" class="hidden rounded-xl p-4 ring-1">
                                    <div class="text-sm font-semibold">Ziņojums</div>
                                    <div id="shareResultText" class="mt-1 text-sm"></div>
                                </div>
                            </form>
                        @endif
                    </section>

                    @if ($cars->isNotEmpty())
                        <section class="rounded-2xl border border-red-500/25 bg-red-500/5 p-6 ring-1 ring-red-500/20">
                            <h2 class="text-lg font-semibold text-red-200">Dzēst auto</h2>
                            <p class="mt-1 text-sm text-zinc-400">
                                Neatgriezeniski dzēš auto ierakstu, visus izdevumus, degvielas žurnālu un koplietošanas
                                saites ar citiem lietotājiem. Ja auto koplieto vairāki apstiprināti lietotāji, tas
                                pazudīs visiem.
                            </p>
                            <ul class="mt-4 space-y-3">
                                @foreach ($cars as $car)
                                    <li
                                        class="flex flex-col gap-3 rounded-xl bg-zinc-950/40 p-4 ring-1 ring-white/10 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div class="text-sm text-zinc-200">
                                            <span class="font-semibold">{{ $car->brand }} {{ $car->model }}</span>
                                            <span class="text-zinc-500">({{ $car->year }})</span>
                                        </div>
                                        <form
                                            method="POST"
                                            action="{{ route('cars.destroy', $car) }}"
                                            class="shrink-0"
                                            onsubmit="
                                                return confirm(
                                                    'Dzēst auto {{ $car->brand }} {{ $car->model }} ({{ $car->year }})? Tiks dzēsti arī visi izdevumi un degvielas ieraksti.'
                                                );
                                            "
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="tab" value="auto" />
                                            <button
                                                type="submit"
                                                class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 sm:w-auto"
                                            >
                                                Dzēst šo auto
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </div>
            </main>
        </div>

        <?php // Modālais logs izdevuma labošanai ?>
        <div id="expenseEditModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
            <div id="expenseEditBackdrop" class="absolute inset-0 bg-black/60"></div>

            <div class="relative mx-auto flex min-h-screen max-w-2xl items-center px-4 py-8 sm:px-6">
                <div class="max-h-[90vh] w-full overflow-y-auto rounded-2xl bg-zinc-950 p-6 ring-1 ring-white/10">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-zinc-100">Labot izdevumu</h2>
                            <p class="mt-1 text-sm text-zinc-400">Izmaiņas tiks saglabātas un tu atgriezīsies sarakstā</p>
                        </div>
                        <button
                            type="button"
                            id="closeExpenseEditBtn"
                            class="rounded-lg bg-zinc-800 px-3 py-2 text-sm font-semibold ring-1 ring-white/10 hover:bg-zinc-700"
                        >
                            Aizvērt
                        </button>
                    </div>

                    <form id="expenseEditForm" method="POST" action="#" class="mt-6 space-y-4">
                        @csrf
                        @method('PUT')

                        <?php // Saglabā filtrus pēc atjaunināšanas ?>
                        @if (request()->filled('type'))
                            <input type="hidden" name="filter_type" value="{{ request('type') }}" />
                        @endif
                        @foreach (['tab', 'period', 'date_from', 'date_to', 'sort'] as $k)
                            @if (request()->filled($k))
                                <input type="hidden" name="{{ $k }}" value="{{ request($k) }}" />
                            @endif
                        @endforeach

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-semibold text-zinc-200">Tips</label>
                                <select
                                    name="type"
                                    id="edit_expense_type"
                                    required
                                    class="mt-2 w-full rounded-xl bg-zinc-900/60 px-4 py-3 text-base ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                >
                                    @foreach ($expenseTypes as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endforeach
                                </select>
                                <p
                                    id="edit_expense_type_hint"
                                    class="mt-2 min-h-[2.5rem] text-xs leading-relaxed text-zinc-500"
                                ></p>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-zinc-200">Datums</label>
                                <input
                                    type="text"
                                    name="date"
                                    id="edit_expense_date"
                                    required
                                    autocomplete="off"
                                    inputmode="none"
                                    placeholder="Izvēlies datumu…"
                                    class="js-flatpickr mt-2 w-full rounded-xl bg-zinc-900/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-semibold text-zinc-200">Summa (€)</label>
                                <input
                                    type="number"
                                    name="amount"
                                    id="edit_expense_amount"
                                    step="0.01"
                                    min="0"
                                    required
                                    class="mt-2 w-full rounded-xl bg-zinc-900/60 px-4 py-3 text-base ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                />
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-zinc-200">Nobraukums (km)</label>
                                <input
                                    type="number"
                                    name="mileage"
                                    id="edit_expense_mileage"
                                    min="0"
                                    placeholder="nav obligāts"
                                    class="mt-2 w-full rounded-xl bg-zinc-900/60 px-4 py-3 text-base ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-zinc-200">Apraksts</label>
                            <input
                                type="text"
                                name="description"
                                id="edit_expense_description"
                                maxlength="255"
                                class="mt-2 w-full rounded-xl bg-zinc-900/60 px-4 py-3 text-base ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                            />
                        </div>

                        <div class="flex flex-wrap justify-end gap-3 pt-2">
                            <button
                                type="button"
                                id="cancelExpenseEditBtn"
                                class="rounded-xl bg-zinc-800 px-5 py-3 text-sm font-semibold ring-1 ring-white/10 hover:bg-zinc-700"
                            >
                                Atcelt
                            </button>
                            <button
                                type="submit"
                                class="rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-500"
                            >
                                Saglabāt
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            (function () {
                const hints = @json($typeHints ?? []);
                const sel = document.getElementById('add_expense_type');
                const hint = document.getElementById('add_type_hint');
                if (sel && hint) {
                    function sync() {
                        hint.textContent = hints[sel.value] || '';
                    }
                    sel.addEventListener('change', sync);
                    sync();
                }
            })();

            (function () {
                // Izdevuma labošanas modālais logs
                const modal = document.getElementById('expenseEditModal');
                const backdrop = document.getElementById('expenseEditBackdrop');
                const closeBtn = document.getElementById('closeExpenseEditBtn');
                const cancelBtn = document.getElementById('cancelExpenseEditBtn');
                const form = document.getElementById('expenseEditForm');

                const typeSel = document.getElementById('edit_expense_type');
                const typeHintEl = document.getElementById('edit_expense_type_hint');
                const dateEl = document.getElementById('edit_expense_date');
                const amountEl = document.getElementById('edit_expense_amount');
                const mileageEl = document.getElementById('edit_expense_mileage');
                const descEl = document.getElementById('edit_expense_description');

                if (!modal || !form) return;

                function closeModal() {
                    modal.classList.add('hidden');
                }

                function openModalFromButton(btn) {
                    const id = btn.getAttribute('data-expense-id') || '';
                    const type = btn.getAttribute('data-expense-type') || '';
                    const date = btn.getAttribute('data-expense-date') || '';
                    const amount = btn.getAttribute('data-expense-amount') || '';
                    const mileage = btn.getAttribute('data-expense-mileage') || '';
                    const description = btn.getAttribute('data-expense-description') || '';

                    // Iestata action uz atjaunināšanas maršrutu
                    form.action = @json(url('/expenses/expenses')) + '/' + encodeURIComponent(id);

                    // Ieliek vērtības laukos
                    if (typeSel) typeSel.value = type;
                    if (dateEl) dateEl.value = date;
                    if (amountEl) amountEl.value = amount;
                    if (mileageEl) mileageEl.value = mileage;
                    if (descEl) descEl.value = description;

                    // Atjaunina paskaidrojumu zem tipa
                    if (typeHintEl && typeSel) {
                        const hints = @json($typeHints ?? []);
                        typeHintEl.textContent = hints[typeSel.value] || '';
                    }

                    modal.classList.remove('hidden');
                }

                // Klikšķis uz Labot pogas atver modāli
                document.querySelectorAll('.js-edit-expense').forEach(function (a) {
                    a.addEventListener('click', function (e) {
                        e.preventDefault();
                        openModalFromButton(a);
                    });
                });

                // Aizvēršanas pogas un fons
                if (backdrop) backdrop.addEventListener('click', closeModal);
                if (closeBtn) closeBtn.addEventListener('click', closeModal);
                if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

                // ESC aizver modāli
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
                });

                // Paskaidrojums zem tipa, ja lietotājs to maina
                if (typeSel && typeHintEl) {
                    const hints = @json($typeHints ?? []);
                    typeSel.addEventListener('change', function () {
                        typeHintEl.textContent = hints[typeSel.value] || '';
                    });
                }
            })();

            (function () {
                const shareForm = document.getElementById('shareForm');
                if (!shareForm) return;

                shareForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const carId = document.getElementById('share_car_id').value;
                    const userEmail = document.getElementById('share_user_email').value;

                    const box = document.getElementById('shareResult');
                    const text = document.getElementById('shareResultText');

                    box.classList.add('hidden');
                    text.textContent = '';

                    fetch('{{ url('/cars') }}/' + encodeURIComponent(carId) + '/share', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ user_email: userEmail }),
                    })
                        .then(function (r) {
                            return r.json().then(function (data) {
                                return { ok: r.ok, status: r.status, data: data };
                            });
                        })
                        .then(function (res) {
                            box.classList.remove('hidden');
                            const msg =
                                res.data && res.data.message
                                    ? res.data.message
                                    : res.ok
                                      ? 'Gatavs.'
                                      : 'Kļūda (' + res.status + ').';
                            text.textContent = msg;

                            const success = res.ok && res.data && res.data.success === true;
                            if (success) {
                                box.classList.remove('bg-red-500/10', 'ring-red-500/25');
                                box.classList.add('bg-emerald-500/10', 'ring-emerald-500/25');
                                text.classList.remove('text-red-200');
                                text.classList.add('text-emerald-100/90');
                            } else {
                                box.classList.remove('bg-emerald-500/10', 'ring-emerald-500/25');
                                box.classList.add('bg-red-500/10', 'ring-red-500/25');
                                text.classList.remove('text-emerald-100/90');
                                text.classList.add('text-red-200');
                            }
                        })
                        .catch(function () {
                            box.classList.remove('hidden');
                            box.classList.add('bg-red-500/10', 'ring-red-500/25');
                            text.classList.add('text-red-200');
                            text.textContent =
                                'Neizdevās nosūtīt pieprasījumu. Pārbaudi savienojumu un mēģini vēlreiz.';
                        });
                });
            })();
        </script>
        @include('partials.flatpickr-lv-scripts')
    </body>
</html>
