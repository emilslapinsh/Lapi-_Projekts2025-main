<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Degvielas patēriņš</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-zinc-950 text-zinc-100">
    <div class="pointer-events-none fixed inset-0">
        <div class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900"></div>
        <div class="absolute -top-20 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-red-600/20 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 h-72 w-72 rounded-full bg-zinc-700/10 blur-3xl"></div>
    </div>

    <div class="relative mx-auto min-h-screen max-w-6xl px-6">
        <header class="flex items-center justify-between py-8">
            <div>
                <h1 class="text-2xl font-bold tracking-wide text-zinc-100 uppercase">Degvielas patēriņš</h1>
                <p class="mt-1 text-sm text-zinc-400">
                    Uzpildes žurnāls, patēriņa aprēķins (L/100km) un cenu dinamika.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}"
                   class="rounded-lg bg-zinc-800 px-5 py-2.5 text-base font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700">
                    Uz paneli
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="rounded-lg bg-red-600 px-5 py-2.5 text-base font-semibold text-white hover:bg-red-500">
                        Iziet
                    </button>
                </form>
            </div>
        </header>

        <main class="pb-12">
            @if(session('success'))
                <div class="mb-6 rounded-2xl bg-emerald-500/10 p-4 ring-1 ring-emerald-500/20">
                    <div class="text-sm font-semibold text-emerald-200">Veiksmīgi</div>
                    <div class="mt-1 text-sm text-emerald-100/80">{{ session('success') }}</div>
                </div>
            @endif

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

            <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="flex-1">
                        <label class="text-sm font-semibold text-zinc-200">Izvēlies auto</label>
                        <form method="GET" action="{{ route('degviela.index') }}">
                            <select name="car_id"
                                    onchange="this.form.submit()"
                                    class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                @forelse($cars as $car)
                                    <option value="{{ $car->id }}" {{ optional($selectedCar)->id === $car->id ? 'selected' : '' }}>
                                        {{ $car->brand }} {{ $car->model }} ({{ $car->year }})
                                    </option>
                                @empty
                                    <option value="">Nav pieejamu auto</option>
                                @endforelse
                            </select>
                        </form>

                        @if(!$selectedCar)
                            <p class="mt-3 text-sm text-zinc-400">Nav izvēlēta auto.</p>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5 lg:gap-4">
                        <div class="rounded-2xl bg-zinc-950/50 p-4 ring-1 ring-white/10">
                            <div class="text-xs text-zinc-500">Vidēji (L/100km)</div>
                            <div class="mt-1 text-lg font-semibold">
                                {{ $stats['avg_l100'] !== null ? number_format($stats['avg_l100'], 2) : '—' }}
                            </div>
                        </div>
                        <div class="rounded-2xl bg-zinc-950/50 p-4 ring-1 ring-white/10">
                            <div class="text-xs text-zinc-500">Pēdējais (L/100km)</div>
                            <div class="mt-1 text-lg font-semibold">
                                {{ $stats['last_l100'] !== null ? number_format($stats['last_l100'], 2) : '—' }}
                            </div>
                        </div>
                        <div class="rounded-2xl bg-zinc-950/50 p-4 ring-1 ring-white/10">
                            <div class="text-xs text-zinc-500">€/100km</div>
                            <div class="mt-1 text-lg font-semibold">
                                {{ $stats['eur_per_100'] !== null ? number_format($stats['eur_per_100'], 2) : '—' }}
                            </div>
                        </div>
                        <div class="rounded-2xl bg-zinc-950/50 p-4 ring-1 ring-white/10">
                            <div class="text-xs text-zinc-500">Pēdējā cena (€/l)</div>
                            <div class="mt-1 text-lg font-semibold">
                                {{ $stats['last_price_per_l'] !== null ? number_format($stats['last_price_per_l'], 3) : '—' }}
                            </div>
                        </div>
                        <div class="rounded-2xl bg-zinc-950/50 p-4 ring-1 ring-white/10">
                            <div class="text-xs text-zinc-500">Anomālijas</div>
                            <div class="mt-1 text-lg font-semibold">
                                {{ $stats['anomaly_count'] ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                @if($selectedCar)
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route('degviela.export', ['car_id' => $selectedCar->id]) }}"
                           class="rounded-xl bg-zinc-800 px-4 py-2 text-sm font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700">
                            Eksportēt CSV
                        </a>

                        <div class="rounded-xl bg-zinc-950/40 px-4 py-2 text-sm text-zinc-300 ring-1 ring-white/10">
                            Padoms: patēriņš tiek rēķināts starp ierakstiem ar <span class="text-zinc-100 font-semibold">“Pilna bāka”</span>.
                        </div>
                    </div>
                @endif
            </section>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                    <h2 class="text-lg font-semibold text-zinc-100">Pievienot uzpildi</h2>
                    <p class="mt-1 text-sm text-zinc-400">Jo precīzāks odometrs, jo labāks patēriņa aprēķins.</p>

                    @if($selectedCar)
                        <form method="POST" action="{{ route('degviela.store') }}" class="mt-6 space-y-4">
                            @csrf
                            <input type="hidden" name="car_id" value="{{ $selectedCar->id }}">

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Datums</label>
                                    <input type="date" name="date" required value="{{ now()->toDateString() }}"
                                           class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                </div>

                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Odometrs (km)</label>
                                    <input type="number" name="odometer_km" required min="0" placeholder="Piem.: 201500"
                                           class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Litri (L)</label>
                                    <input type="number" name="liters" step="0.01" required min="0.01" placeholder="Piem.: 42.30"
                                           class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                </div>

                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Summa (€)</label>
                                    <input type="number" name="total_eur" step="0.01" required min="0" placeholder="Piem.: 71.95"
                                           class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Degvielas veids</label>
                                    <select name="fuel_type" required
                                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                        <option value="Dīzelis">Dīzelis</option>
                                        <option value="Benzīns">Benzīns</option>
                                        <option value="LPG">LPG</option>
                                        <option value="Elektro">Elektro</option>
                                        <option value="Cits">Cits</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Stacija (nav obligāts)</label>
                                    <input type="text" name="station" maxlength="80" placeholder="Piem.: Neste, Virši"
                                           class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                </div>
                            </div>

                            <div class="flex items-center gap-3 rounded-xl bg-zinc-950/40 px-4 py-3 ring-1 ring-white/10">
                                <input id="is_full" type="checkbox" name="is_full_tank" value="1" checked
                                       class="h-4 w-4 rounded border-white/10 bg-zinc-900 text-red-600 focus:ring-red-500/50">
                                <label for="is_full" class="text-sm text-zinc-200">
                                    Pilna bāka (ieteicams patēriņa aprēķinam)
                                </label>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-zinc-200">Piezīme (nav obligāts)</label>
                                <input type="text" name="note" maxlength="1000" placeholder="Piem.: pilsēta / šoseja, braukšanas stils, riepas..."
                                       class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                            </div>

                            <button type="submit"
                                    class="w-full rounded-xl bg-red-600 px-5 py-3 text-base font-semibold text-white hover:bg-red-500">
                                Pievienot uzpildi
                            </button>
                        </form>
                    @else
                        <div class="mt-6 text-sm text-zinc-400">Vispirms pievieno/izvēlies auto.</div>
                    @endif
                </section>

                <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-zinc-100">Grafiks</h2>
                            <p class="mt-1 text-sm text-zinc-400">L/100km (no “pilna bāka” pāriem) un €/l.</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <canvas id="fuelChart" height="140"></canvas>
                    </div>

                    <div class="mt-4 rounded-xl bg-zinc-950/40 p-4 ring-1 ring-white/10">
                        <div class="text-sm font-semibold text-zinc-100">Kā tas strādā?</div>
                        <div class="mt-1 text-sm text-zinc-400">
                            Patēriņš tiek rēķināts starp divām uzpildēm, kur abās atzīmēta “Pilna bāka”.
                            L/100km = (uzpildītie litri) / (nobrauktie km) * 100.
                        </div>
                    </div>
                </section>
            </div>

            <section class="mt-6 rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-zinc-100">Uzpildes (pēdējie 50)</h2>
                    <div class="text-sm text-zinc-400">{{ $entries->count() }} ieraksti</div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-zinc-400">
                            <tr class="border-b border-white/10">
                                <th class="py-2 text-left">Datums</th>
                                <th class="py-2 text-left">Odometrs</th>
                                <th class="py-2 text-left">Litri</th>
                                <th class="py-2 text-left">Summa</th>
                                <th class="py-2 text-left">€/l</th>
                                <th class="py-2 text-left">Veids</th>
                                <th class="py-2 text-left">Pilna bāka</th>
                                <th class="py-2 text-left">Stacija</th>
                                <th class="py-2 text-right">Darbības</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($entries as $e)
                                <tr class="border-b border-white/5">
                                    <td class="py-3 whitespace-nowrap">{{ $e->date->format('Y-m-d') }}</td>
                                    <td class="py-3 whitespace-nowrap">{{ $e->odometer_km }} km</td>
                                    <td class="py-3 whitespace-nowrap">{{ number_format($e->liters, 2) }} L</td>
                                    <td class="py-3 whitespace-nowrap">{{ number_format($e->total_eur, 2) }} €</td>
                                    <td class="py-3 whitespace-nowrap">{{ $e->price_per_liter !== null ? number_format($e->price_per_liter, 3) : '—' }}</td>
                                    <td class="py-3 whitespace-nowrap">{{ $e->fuel_type }}</td>
                                    <td class="py-3 whitespace-nowrap">{{ $e->is_full_tank ? 'Jā' : 'Nē' }}</td>
                                    <td class="py-3 text-zinc-300">{{ $e->station ?? '—' }}</td>
                                    <td class="py-3 text-right whitespace-nowrap">
                                        <form method="POST" action="{{ route('degviela.destroy', $e->id) }}"
                                              onsubmit="return confirm('Dzēst šo uzpildes ierakstu?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded-lg bg-zinc-800 px-3 py-2 text-xs font-semibold ring-1 ring-white/10 hover:bg-zinc-700">
                                                Dzēst
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-6 text-zinc-400">Nav uzpildes ierakstu.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    {{-- Chart.js (ja jau ir pie tevis, super; ja nav, ieliec to app.js vai layoutā) --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const labels = @json($chart['labels']);
        const l100 = @json($chart['l100']);
        const eurl = @json($chart['eurl']);

        const ctx = document.getElementById('fuelChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        { label: 'L/100km', data: l100, yAxisID: 'y' },
                        { label: '€/l', data: eurl, yAxisID: 'y1' },
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { title: { display: true, text: 'L/100km' } },
                        y1: { position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: '€/l' } }
                    }
                }
            });
        }
    </script>
</body>
</html>
