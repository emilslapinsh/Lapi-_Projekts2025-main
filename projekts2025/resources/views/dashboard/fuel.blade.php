<!DOCTYPE html>
<html lang="lv">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Degvielas patēriņš</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.flatpickr-lv-head')
    </head>

    <body class="min-h-screen bg-zinc-950 text-zinc-100">
        <?php // Fona gradients un gaismas efekti ?>
        <div class="pointer-events-none fixed inset-0">
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900"></div>
            <div class="absolute -top-20 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-red-600/20 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-72 w-72 rounded-full bg-zinc-700/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto min-h-screen max-w-6xl px-6">
            <?php // Galvene ar pogām uz paneli un izrakstīšanos ?>
            <header class="flex flex-col gap-4 py-8 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-wide text-zinc-100 uppercase">Degvielas patēriņš</h1>
                    <p class="mt-1 max-w-xl text-sm text-zinc-400">
                        Žurnāls katrai uzpildei. Patēriņš
                        <span class="text-zinc-200">L/100 km</span>
                        tiek aprēķināts tikai starp divām pēc kārtas
                        <span class="text-zinc-200">pilnām bākām</span>
                        — tā ir standarta metode, lai litri atbilstu nobrauktajiem kilometriem.
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

                <?php // Brīdinājums par gaidošām auto saitēm ?>
                @if ($pendingCars->isNotEmpty())
                    <div class="mb-6 rounded-2xl bg-amber-500/10 p-4 ring-1 ring-amber-500/25">
                        <div class="text-sm font-semibold text-amber-100">Gaida apstiprinājumu</div>
                        <p class="mt-1 text-sm text-amber-100/80">
                            Tev ir {{ $pendingCars->count() }} auto saites, kas vēl nav apstiprinātas. Šajā lapā
                            redzami tikai
                            <span class="font-semibold text-amber-50">apstiprinātie</span>
                            auto.
                        </p>
                    </div>
                @endif

                <?php // Auto izvēle un galvenie rādītāji ?>
                <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1 lg:max-w-sm">
                            <label class="text-sm font-semibold text-zinc-200">Izvēlies auto</label>
                            <form method="GET" action="{{ route('degviela.index') }}">
                                <select
                                    name="car_id"
                                    onchange="this.form.submit()"
                                    class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                >
                                    @forelse ($cars as $car)
                                        <option
                                            value="{{ $car->id }}"
                                            {{ optional($selectedCar)->id === $car->id ? 'selected' : '' }}
                                        >
                                            {{ $car->brand }} {{ $car->model }} ({{ $car->year }})
                                        </option>
                                    @empty
                                        <option value="">Nav pieejamu auto</option>
                                    @endforelse
                                </select>
                            </form>

                            @if (! $selectedCar)
                                <p class="mt-3 text-sm text-zinc-400">
                                    Pievieno auto sadaļā „Izdevumi” un lūdz īpašnieku to apstiprināt.
                                </p>
                            @endif
                        </div>

                        @if ($selectedCar)
                            <?php // Kopsavilkuma kartītes (vidēji, pēdējais, €/100km, cena, brīdinājumi) ?>
                            <div class="grid min-w-0 flex-1 grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:gap-4">
                                <div
                                    class="rounded-2xl bg-zinc-950/50 p-4 ring-1 ring-white/10"
                                    title="Vidējais patēriņš visos pilno bāku intervālos."
                                >
                                    <div class="text-xs font-medium text-zinc-500">Vidēji</div>
                                    <div class="mt-0.5 text-[11px] leading-tight text-zinc-500">L/100 km</div>
                                    <div class="mt-1 text-lg font-semibold tabular-nums text-zinc-100">
                                        {{ $stats['avg_l100'] !== null ? number_format($stats['avg_l100'], 2) : '—' }}
                                    </div>
                                </div>
                                <div
                                    class="rounded-2xl bg-zinc-950/50 p-4 ring-1 ring-white/10"
                                    title="Pēdējais pilno bāku intervāls (starp divām pilnām bākām)."
                                >
                                    <div class="text-xs font-medium text-zinc-500">Pēdējais</div>
                                    <div class="mt-0.5 text-[11px] leading-tight text-zinc-500">L/100 km</div>
                                    <div class="mt-1 text-lg font-semibold tabular-nums text-zinc-100">
                                        {{ $stats['last_l100'] !== null ? number_format($stats['last_l100'], 2) : '—' }}
                                    </div>
                                </div>
                                <div
                                    class="rounded-2xl bg-zinc-950/50 p-4 ring-1 ring-white/10"
                                    title="Pēdējā intervāla patēriņš reizināts ar tās pašas uzpildes €/l."
                                >
                                    <div class="text-xs font-medium text-zinc-500">Izdevumi</div>
                                    <div class="mt-0.5 text-[11px] leading-tight text-zinc-500">€/100 km</div>
                                    <div class="mt-1 text-lg font-semibold tabular-nums text-zinc-100">
                                        {{ $stats['eur_per_100'] !== null ? number_format($stats['eur_per_100'], 2) : '—' }}
                                    </div>
                                </div>
                                <div
                                    class="rounded-2xl bg-zinc-950/50 p-4 ring-1 ring-white/10"
                                    title="Pēdējā žurnāla ieraksta cena (nav obligāti pilna bāka)."
                                >
                                    <div class="text-xs font-medium text-zinc-500">Cena</div>
                                    <div class="mt-0.5 text-[11px] leading-tight text-zinc-500">€/l (pēdējā)</div>
                                    <div class="mt-1 text-lg font-semibold tabular-nums text-zinc-100">
                                        {{ $stats['last_price_per_l'] !== null ? number_format($stats['last_price_per_l'], 3) : '—' }}
                                    </div>
                                </div>
                                <div
                                    class="col-span-2 rounded-2xl bg-zinc-950/50 p-4 ring-1 ring-white/10 sm:col-span-2 lg:col-span-1"
                                    title="Intervāli, kuros patēriņš > 30% virs vidējā."
                                >
                                    <div class="text-xs font-medium text-zinc-500">Brīdinājumi</div>
                                    <div class="mt-0.5 text-[11px] leading-tight text-zinc-500">„Anomālijas”</div>
                                    <div class="mt-1 text-lg font-semibold tabular-nums text-zinc-100">
                                        {{ (int) ($stats['anomaly_count'] ?? 0) }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($selectedCar)
                        <?php // Eksports uz CSV ?>
                        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                            <a
                                href="{{ route('degviela.export', ['car_id' => $selectedCar->id]) }}"
                                class="inline-flex items-center justify-center rounded-xl bg-red-600/90 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-red-500/30 hover:bg-red-500"
                            >
                                Eksportēt CSV
                            </a>
                            <p class="text-sm text-zinc-400">
                                CSV satur visus ierakstus šim auto (ar UTF-8 kodējumu Excel atbalstam).
                            </p>
                        </div>

                        @if (($fuelMeta['intervals_usable'] ?? 0) === 0)
                            <?php // Paskaidrojums, kāpēc nav pietiekami datu patēriņa aprēķinam ?>
                            <div class="mt-5 rounded-2xl bg-zinc-950/60 p-4 ring-1 ring-white/10">
                                <div class="text-sm font-semibold text-zinc-100">Kāpēc nav patēriņa skaitļu?</div>
                                <p class="mt-2 text-sm leading-relaxed text-zinc-400">
                                    Lai parādītos
                                    <span class="text-zinc-200">L/100 km</span>
                                    , grafiks un apkopojums, vajag vismaz
                                    <span class="font-semibold text-zinc-200">divas pilnas bākas pēc kārtas</span>
                                    ar derīgu odometru (otrais odometrs lielāks par pirmo). Daļējās uzpildes žurnālā
                                    drīkst būt, bet tās netiek izmantotas šim aprēķinam.
                                </p>
                                @if (($fuelMeta['full_tank_rows'] ?? 0) > 0)
                                    <p class="mt-2 text-sm text-zinc-500">
                                        Šobrīd pilno bāku ierakstu skaits: {{ $fuelMeta['full_tank_rows'] }}.
                                    </p>
                                @endif
                            </div>
                        @endif

                        @if (! empty($anomalies))
                            <?php // Saraksts ar anomālijām (augstāks patēriņš nekā parasti) ?>
                            <div class="mt-5 rounded-2xl border border-amber-500/25 bg-amber-500/5 p-4">
                                <div class="text-sm font-semibold text-amber-100">Augstāks patēriņš nekā parasti</div>
                                <p class="mt-1 text-xs text-amber-100/70">
                                    Šie intervāli pārsniedz vidējo par vairāk nekā 30% — pārbaudi odometru, „pilna bāka”
                                    atzīmi vai īpašus apstākļus (pilsēta, aukstums, piekabe).
                                </p>
                                <ul class="mt-3 space-y-2 text-sm text-amber-50/90">
                                    @foreach ($anomalies as $a)
                                        <li class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                            <span class="font-mono text-xs text-amber-200/90">{{ $a['date'] }}</span>
                                            <span class="tabular-nums font-semibold">
                                                {{ number_format($a['l100'], 2) }} L/100 km
                                            </span>
                                            <span class="text-xs text-amber-100/60">
                                                (vidēji {{ number_format($a['avg_l100'], 2) }})
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif
                </section>

                <details class="mt-6 rounded-2xl bg-zinc-900/40 ring-1 ring-white/10 open:bg-zinc-900/55">
                    <summary
                        class="cursor-pointer list-none px-5 py-4 text-sm font-semibold text-zinc-200 marker:content-none [&::-webkit-details-marker]:hidden"
                    >
                        <span class="inline-flex items-center gap-2">
                            <span class="rounded-lg bg-zinc-800 px-2 py-0.5 text-xs font-bold text-zinc-300">?</span>
                            Kā tiek aprēķināts patēriņš?
                        </span>
                    </summary>
                    <div class="border-t border-white/10 px-5 pb-5 pt-2 text-sm leading-relaxed text-zinc-400">
                        <ol class="list-decimal space-y-2 pl-5">
                            <li>Ieraksti tiek kārtoti pēc datuma un odometra.</li>
                            <li>
                                Tiek meklētas tikai rindas ar atzīmi
                                <span class="font-semibold text-zinc-200">„Pilna bāka”</span>
                                .
                            </li>
                            <li>
                                Starp divām tādām rindām:
                                <span class="font-mono text-zinc-300">
                                    nobraukums = otrais odometrs − pirmais odometrs
                                </span>
                                .
                            </li>
                            <li>
                                <span class="font-mono text-zinc-300">
                                    L/100 km = (otrajā uzpildē iepildītie litri ÷ nobraukums) × 100
                                </span>
                                — tieši tās otrās uzpildes litri, kas „aizpilda bāku” pēc nobraukuma.
                            </li>
                            <li>
                                „Pēdējā cena €/l” ir no
                                <span class="font-semibold text-zinc-200">pēdējā žurnāla ieraksta</span>
                                (ne obligāti pilna bāka).
                            </li>
                        </ol>
                    </div>
                </details>

                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                        <h2 class="text-lg font-semibold text-zinc-100">Pievienot uzpildi</h2>
                        <p class="mt-1 text-sm text-zinc-400">
                            Odometrs vienmēr augošā secībā — sistēma nepieļauj mazāku vērtību nekā iepriekšēji
                            ierakstīts maksimums.
                        </p>

                        @if ($selectedCar)
                            <form
                                method="POST"
                                action="{{ route('degviela.store') }}"
                                class="mt-6 space-y-4"
                                id="fuelForm"
                            >
                                @csrf
                                <input type="hidden" name="car_id" value="{{ $selectedCar->id }}" />

                                <div class="grid gap-4 sm:grid-cols-2">
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

                                    <div>
                                        <label class="text-sm font-semibold text-zinc-200">Odometrs (km)</label>
                                        <input
                                            type="number"
                                            name="odometer_km"
                                            required
                                            min="0"
                                            placeholder="Piem.: 201500"
                                            value="{{ old('odometer_km') }}"
                                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                        />
                                    </div>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="text-sm font-semibold text-zinc-200">Litri (L)</label>
                                        <input
                                            type="number"
                                            name="liters"
                                            id="fuel_liters"
                                            step="0.01"
                                            required
                                            min="0.01"
                                            placeholder="Piem.: 42.30"
                                            value="{{ old('liters') }}"
                                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                        />
                                    </div>

                                    <div>
                                        <label class="text-sm font-semibold text-zinc-200">Summa (€)</label>
                                        <input
                                            type="number"
                                            name="total_eur"
                                            id="fuel_total_eur"
                                            step="0.01"
                                            required
                                            min="0"
                                            placeholder="Piem.: 71.95"
                                            value="{{ old('total_eur') }}"
                                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                        />
                                    </div>
                                </div>

                                <div class="rounded-xl bg-zinc-950/50 px-4 py-3 text-sm ring-1 ring-white/10">
                                    <span class="text-zinc-500">Aprēķins:</span>
                                    <span id="fuel_price_preview" class="ml-1 font-semibold tabular-nums text-zinc-200">
                                        — €/l
                                    </span>
                                    <span class="mt-1 block text-xs text-zinc-500">
                                        Summa ÷ litri (pirms saglabāšanas, lai noķertu kļūdas).
                                    </span>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="text-sm font-semibold text-zinc-200">Degvielas veids</label>
                                        <select
                                            name="fuel_type"
                                            required
                                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                        >
                                            @foreach (['Dīzelis', 'Benzīns', 'LPG', 'Elektro', 'Cits'] as $ft)
                                                <option
                                                    value="{{ $ft }}"
                                                    @selected(old('fuel_type', 'Dīzelis') === $ft)
                                                >
                                                    {{ $ft }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="text-sm font-semibold text-zinc-200">
                                            Stacija (nav obligāts)
                                        </label>
                                        <input
                                            type="text"
                                            name="station"
                                            maxlength="80"
                                            placeholder="Piem.: Neste, Virši"
                                            value="{{ old('station') }}"
                                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                        />
                                    </div>
                                </div>

                                <div
                                    class="flex items-start gap-3 rounded-xl bg-zinc-950/40 px-4 py-3 ring-1 ring-white/10"
                                >
                                    <input type="hidden" name="is_full_tank" value="0" />
                                    <input
                                        id="is_full"
                                        type="checkbox"
                                        name="is_full_tank"
                                        value="1"
                                        class="mt-1 h-4 w-4 rounded border-white/10 bg-zinc-900 text-red-600 focus:ring-red-500/50"
                                        @checked(old('is_full_tank', '1') === '1')
                                    />
                                    <label for="is_full" class="text-sm text-zinc-200">
                                        <span class="font-semibold">Pilna bāka</span>
                                        <span class="mt-1 block text-xs font-normal text-zinc-500">
                                            Atzīmē tikai tad, ja tiešām uzpildīji līdz pilnai bākai. Noņemot atzīmi,
                                            ieraksts paliek žurnālā, bet netiek izmantots L/100 km aprēķinam.
                                        </span>
                                    </label>
                                </div>

                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Piezīme (nav obligāts)</label>
                                    <input
                                        type="text"
                                        name="note"
                                        maxlength="1000"
                                        placeholder="Piem.: pilsēta, aukstums, riepas…"
                                        value="{{ old('note') }}"
                                        class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                                    />
                                </div>

                                <button
                                    type="submit"
                                    class="w-full rounded-xl bg-red-600 px-5 py-3 text-base font-semibold text-white hover:bg-red-500"
                                >
                                    Pievienot uzpildi
                                </button>
                            </form>

                            <script>
                                (function () {
                                    // Iegūst ievades laukus priekš €/l priekšskatījuma
                                    const liters = document.getElementById('fuel_liters');
                                    const total = document.getElementById('fuel_total_eur');
                                    const out = document.getElementById('fuel_price_preview');
                                    if (!liters || !total || !out) return;

                                    // Aprēķina un parāda cenu par litru (summa ÷ litri)
                                    function fmt() {
                                        const l = parseFloat(String(liters.value).replace(',', '.'));
                                        const e = parseFloat(String(total.value).replace(',', '.'));
                                        if (!(l > 0) || !(e >= 0)) {
                                            out.textContent = '— €/l';
                                            return;
                                        }
                                        out.textContent = (e / l).toFixed(3).replace('.', ',') + ' €/l';
                                    }

                                    // Pārrēķina, kad lietotājs maina litrus vai summu
                                    liters.addEventListener('input', fmt);
                                    total.addEventListener('input', fmt);
                                    // Pirmais aprēķins uzreiz pēc lapas ielādes
                                    fmt();
                                })();
                            </script>
                        @else
                            <div class="mt-6 text-sm text-zinc-400">Vispirms pievieno un apstiprini auto.</div>
                        @endif
                    </section>

                    <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                        <div>
                            <h2 class="text-lg font-semibold text-zinc-100">Grafiks</h2>
                            <p class="mt-1 text-sm text-zinc-400">
                                Katrs punkts = viens
                                <span class="text-zinc-200">pilno bāku intervāls</span>
                                (datums = otrās pilnās uzpildes diena).
                            </p>
                        </div>

                        <div class="mt-4 h-[200px] min-h-[160px]">
                            @if (! empty($chart['labels']))
                                <canvas id="fuelChart"></canvas>
                            @else
                                <div
                                    class="flex h-[160px] items-center justify-center rounded-xl bg-zinc-950/50 text-center text-sm text-zinc-500 ring-1 ring-white/10"
                                >
                                    Šeit parādīsies līnija, kad būs vismaz viens derīgs intervāls starp divām pilnām
                                    bākām.
                                </div>
                            @endif
                        </div>
                    </section>
                </div>

                @if ($selectedCar && ! empty($intervals))
                    <section class="mt-6 rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                        <h2 class="text-lg font-semibold text-zinc-100">Intervālu tabula</h2>
                        <p class="mt-1 text-sm text-zinc-400">
                            Tie paši dati kā grafikā — pārskatāmi tabulā (jaunākie augšā).
                        </p>
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full min-w-[640px] text-sm">
                                <thead class="text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                    <tr class="border-b border-white/10">
                                        <th class="py-2 pr-4">Otrā pilnā (datums)</th>
                                        <th class="py-2 pr-4">Odometrs</th>
                                        <th class="py-2 pr-4">Nobraukums</th>
                                        <th class="py-2 pr-4">Litri</th>
                                        <th class="py-2 pr-4">Patēriņš</th>
                                        <th class="py-2">€/l</th>
                                    </tr>
                                </thead>
                                <tbody class="text-zinc-200">
                                    @foreach (collect($intervals)->reverse() as $inv)
                                        <tr class="border-b border-white/5">
                                            <td class="py-3 whitespace-nowrap font-mono text-xs text-zinc-300">
                                                {{ $inv['date'] }}
                                            </td>
                                            <td class="py-3 whitespace-nowrap tabular-nums text-zinc-400">
                                                {{ $inv['odometer_from'] }} → {{ $inv['odometer_to'] }} km
                                            </td>
                                            <td class="py-3 whitespace-nowrap tabular-nums">{{ $inv['km'] }} km</td>
                                            <td class="py-3 whitespace-nowrap tabular-nums">
                                                {{ number_format($inv['liters'], 2) }} L
                                            </td>
                                            <td
                                                class="py-3 whitespace-nowrap tabular-nums font-semibold text-red-200/90"
                                            >
                                                {{ number_format($inv['l100'], 2) }} L/100km
                                            </td>
                                            <td class="py-3 whitespace-nowrap tabular-nums text-zinc-400">
                                                {{ $inv['eurl'] !== null ? number_format($inv['eurl'], 3) : '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                <section class="mt-6 rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-lg font-semibold text-zinc-100">Uzpildes žurnāls</h2>
                        <div class="text-sm text-zinc-400">{{ $entries->count() }} no pēdējiem 50 ierakstiem</div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[720px] text-sm">
                            <thead class="text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                <tr class="border-b border-white/10">
                                    <th class="py-2 pr-3">Datums</th>
                                    <th class="py-2 pr-3">Odometrs</th>
                                    <th class="py-2 pr-3">Litri</th>
                                    <th class="py-2 pr-3">Summa</th>
                                    <th class="py-2 pr-3">€/l</th>
                                    <th class="py-2 pr-3">Veids</th>
                                    <th class="py-2 pr-3">Pilna</th>
                                    <th class="py-2 pr-3">Stacija</th>
                                    <th class="py-2 text-right">Darbības</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($entries as $e)
                                    <tr class="border-b border-white/5">
                                        <td class="py-3 whitespace-nowrap">{{ $e->date->format('Y-m-d') }}</td>
                                        <td class="py-3 whitespace-nowrap tabular-nums">{{ $e->odometer_km }} km</td>
                                        <td class="py-3 whitespace-nowrap tabular-nums">
                                            {{ number_format($e->liters, 2) }} L
                                        </td>
                                        <td class="py-3 whitespace-nowrap tabular-nums">
                                            {{ number_format($e->total_eur, 2) }} €
                                        </td>
                                        <td class="py-3 whitespace-nowrap tabular-nums">
                                            {{ $e->price_per_liter !== null ? number_format($e->price_per_liter, 3) : '—' }}
                                        </td>
                                        <td class="py-3 whitespace-nowrap">{{ $e->fuel_type }}</td>
                                        <td class="py-3 whitespace-nowrap">
                                            @if ($e->is_full_tank)
                                                <span
                                                    class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs font-medium text-emerald-200"
                                                >
                                                    Jā
                                                </span>
                                            @else
                                                <span
                                                    class="rounded-full bg-zinc-700/80 px-2 py-0.5 text-xs text-zinc-400"
                                                >
                                                    Nē
                                                </span>
                                            @endif
                                        </td>
                                        <td
                                            class="py-3 max-w-[140px] truncate text-zinc-400"
                                            title="{{ $e->station }}"
                                        >
                                            {{ $e->station ?? '—' }}
                                        </td>
                                        <td class="py-3 text-right whitespace-nowrap">
                                            <form
                                                method="POST"
                                                action="{{ route('degviela.destroy', $e->id) }}"
                                                onsubmit="return confirm('Dzēst šo uzpildes ierakstu?');"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="rounded-lg bg-zinc-800 px-3 py-2 text-xs font-semibold ring-1 ring-white/10 hover:bg-zinc-700"
                                                >
                                                    Dzēst
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="py-8 text-center text-zinc-400">
                                            Nav uzpildes ierakstu.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>

        @if (! empty($chart['labels']))
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
            <script>
                // Dati grafikam (datumi, patēriņš, cena)
                const labels = @json($chart['labels']);
                const l100 = @json($chart['l100']);
                const eurl = @json($chart['eurl']);

                // Krāsas un līniju režģis tumšajam fonam
                const tickColor = '#a1a1aa';
                const gridColor = 'rgba(255,255,255,0.06)';

                // Uzzīmē grafiku uz canvas elementa
                const ctx = document.getElementById('fuelChart');
                if (ctx && typeof Chart !== 'undefined') {
                    // Chart.js konfigurācija (2 līnijas ar 2 asīm)
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [
                                {
                                    // L/100 km līnija (kreisa ass)
                                    label: 'L/100 km',
                                    data: l100,
                                    yAxisID: 'y',
                                    borderColor: 'rgba(248, 113, 113, 0.95)',
                                    backgroundColor: 'rgba(248, 113, 113, 0.12)',
                                    tension: 0.25,
                                    fill: false,
                                    pointRadius: 3,
                                    pointHoverRadius: 5,
                                },
                                {
                                    // €/l līnija (labā ass)
                                    label: '€/l',
                                    data: eurl,
                                    yAxisID: 'y1',
                                    borderColor: 'rgba(250, 204, 21, 0.9)',
                                    backgroundColor: 'rgba(250, 204, 21, 0.08)',
                                    tension: 0.25,
                                    fill: false,
                                    pointRadius: 3,
                                    spanGaps: true,
                                },
                            ],
                        },
                        options: {
                            // Pamatuzstādījumi (responsīvs grafiks un ērtāka tooltip mijiedarbība)
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                // Leģenda un tooltip noformējums
                                legend: {
                                    labels: { color: tickColor, font: { size: 12 } },
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(24, 24, 27, 0.95)',
                                    titleColor: '#f4f4f5',
                                    bodyColor: '#e4e4e7',
                                    borderColor: 'rgba(255,255,255,0.1)',
                                    borderWidth: 1,
                                },
                            },
                            scales: {
                                // X ass (datumi)
                                x: {
                                    ticks: { color: tickColor, maxRotation: 45, minRotation: 0 },
                                    grid: { color: gridColor },
                                },
                                // Y ass (patēriņš)
                                y: {
                                    position: 'left',
                                    title: { display: true, text: 'L/100 km', color: tickColor },
                                    ticks: { color: tickColor },
                                    grid: { color: gridColor },
                                },
                                // Y1 ass (cena)
                                y1: {
                                    position: 'right',
                                    title: { display: true, text: '€/l', color: tickColor },
                                    ticks: { color: tickColor },
                                    grid: { drawOnChartArea: false },
                                },
                            },
                        },
                    });
                }
            </script>
        @endif

        @include('partials.flatpickr-lv-scripts')
    </body>
</html>
