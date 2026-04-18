<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Izdevumu pārvaldība</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-zinc-950 text-zinc-100">
    <!-- Background glow -->
    <div class="pointer-events-none fixed inset-0">
        <div class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900"></div>
        <div class="absolute -top-20 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-red-600/20 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 h-72 w-72 rounded-full bg-zinc-700/10 blur-3xl"></div>
    </div>

    <div class="relative mx-auto min-h-screen max-w-6xl px-6">
        <!-- Header -->
        <header class="flex items-center justify-between py-8">
            <div>
                <h1 class="text-2xl font-bold tracking-wide text-zinc-100 uppercase">
                    Izdevumu pārvaldība
                </h1>
                <p class="mt-1 text-sm text-zinc-400">
                    Pievienojiet izdevumus un analizējiet auto izmaksas (kopā, mēnesī, €/km).
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
            {{-- Success message --}}
            @if(session('success'))
                <div class="mb-6 rounded-2xl bg-emerald-500/10 p-4 ring-1 ring-emerald-500/20">
                    <div class="text-sm font-semibold text-emerald-200">Veiksmīgi</div>
                    <div class="mt-1 text-sm text-emerald-100/80">{{ session('success') }}</div>
                </div>
            @endif

            {{-- Validation errors --}}
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

            {{-- ✅ Pending share requests --}}
            @if(isset($pendingCars) && $pendingCars->count() > 0)
                <section class="mb-6 rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                    <h2 class="text-lg font-semibold text-zinc-100">Koplietošanas pieprasījumi</h2>
                    <p class="mt-1 text-sm text-zinc-400">
                        Jums ir neapstiprināti koplietošanas pieprasījumi. Apstipriniet, lai auto parādītos sarakstā.
                    </p>

                    <div class="mt-4 space-y-3">
                        @foreach($pendingCars as $pc)
                            <div class="flex flex-col gap-3 rounded-xl bg-zinc-950/40 p-4 ring-1 ring-white/10 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="font-semibold">
                                        {{ $pc->brand }} {{ $pc->model }} ({{ $pc->year }})
                                    </div>
                                    <div class="text-sm text-zinc-400">
                                        Statuss: gaida apstiprinājumu
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('cars.confirm', $pc->id) }}">
                                    @csrf
                                    <button class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">
                                        Apstiprināt
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Car picker + stats --}}
            <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="flex-1">
                        <label class="text-sm font-semibold text-zinc-200">Izvēlies auto</label>

                        <form method="GET" action="{{ route('izdevumi.index') }}">
                            <select name="car_id"
                                    onchange="this.form.submit()"
                                    class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                @forelse($cars as $car)
                                    <option value="{{ $car->id }}" {{ optional($selectedCar)->id === $car->id ? 'selected' : '' }}>
                                        {{ $car->brand }} {{ $car->model }} ({{ $car->year }})
                                    </option>
                                @empty
                                    <option value="">Nav apstiprinātu auto</option>
                                @endforelse
                            </select>
                        </form>

                        @if(!$selectedCar)
                            <p class="mt-3 text-sm text-zinc-400">
                                Nav apstiprināta auto. Ja tev ir koplietošanas pieprasījums, apstiprini to augstāk.
                            </p>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                        <div class="rounded-2xl bg-zinc-950/50 p-4 ring-1 ring-white/10">
                            <div class="text-xs text-zinc-500">Kopā (€)</div>
                            <div class="mt-1 text-lg font-semibold">{{ number_format($stats['total'] ?? 0, 2) }}</div>
                        </div>

                        <div class="rounded-2xl bg-zinc-950/50 p-4 ring-1 ring-white/10">
                            <div class="text-xs text-zinc-500">Šomēnes (€)</div>
                            <div class="mt-1 text-lg font-semibold">{{ number_format($stats['month'] ?? 0, 2) }}</div>
                        </div>

                        <div class="rounded-2xl bg-zinc-950/50 p-4 ring-1 ring-white/10">
                            <div class="text-xs text-zinc-500">€/km</div>
                            <div class="mt-1 text-lg font-semibold">
                                {{ $stats['per_km'] !== null ? number_format($stats['per_km'], 4) : '—' }}
                            </div>
                        </div>

                        <div class="rounded-2xl bg-zinc-950/50 p-4 ring-1 ring-white/10">
                            <div class="text-xs text-zinc-500">Pēdējais nobraukums</div>
                            <div class="mt-1 text-lg font-semibold">
                                {{ $stats['last_mileage'] !== null ? $stats['last_mileage'].' km' : '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                @if($selectedCar)
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route('expenses.export', ['car_id' => $selectedCar->id]) }}"
                           class="rounded-xl bg-zinc-800 px-4 py-2 text-sm font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700">
                            Eksportēt CSV
                        </a>
                    </div>
                @endif
            </section>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                {{-- Add expense --}}
                <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                    <h2 class="text-lg font-semibold text-zinc-100">Pievienot izdevumu</h2>
                    <p class="mt-1 text-sm text-zinc-400">
                        Pievieno ierakstu, lai redzētu reālās auto uzturēšanas izmaksas.
                    </p>

                    @if($selectedCar)
                        <form method="POST" action="{{ route('expenses.store') }}" class="mt-6 space-y-4">
                            @csrf
                            <input type="hidden" name="car_id" value="{{ $selectedCar->id }}">

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Tips</label>
                                    <select name="type" required
                                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                        <option value="Degviela">Degviela</option>
                                        <option value="Serviss">Serviss</option>
                                        <option value="Remonts">Remonts</option>
                                        <option value="Apdrošināšana">Apdrošināšana</option>
                                        <option value="Nodokļi">Nodokļi</option>
                                        <option value="Cits">Cits</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Datums</label>
                                    <input type="date" name="date" required value="{{ now()->toDateString() }}"
                                           class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Summa (€)</label>
                                    <input type="number" name="amount" step="0.01" min="0" required
                                           placeholder="Piem.: 45.50"
                                           class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                </div>

                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Nobraukums (km) (nav obligāts)</label>
                                    <input type="number" name="mileage" min="0"
                                           placeholder="Piem.: 201500"
                                           class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                </div>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-zinc-200">Apraksts (nav obligāts)</label>
                                <input type="text" name="description" maxlength="255"
                                       placeholder="Piem.: Eļļas maiņa, riepu maiņa, uzpilde..."
                                       class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                            </div>

                            <button type="submit"
                                    class="w-full rounded-xl bg-red-600 px-5 py-3 text-base font-semibold text-white hover:bg-red-500">
                                Pievienot izdevumu
                            </button>
                        </form>
                    @else
                        <div class="mt-6 text-sm text-zinc-400">
                            Vispirms pievieno vai apstiprini auto.
                        </div>
                    @endif
                </section>

                {{-- Add car + share --}}
                <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                    <h2 class="text-lg font-semibold text-zinc-100">Auto</h2>
                    <p class="mt-1 text-sm text-zinc-400">
                        Pievieno auto vai koplieto ar citu lietotāju.
                    </p>

                    {{-- Add car --}}
                    <div class="mt-6">
                        <h3 class="text-sm font-semibold text-zinc-200">Pievienot auto</h3>

                        <form method="POST" action="{{ route('izdevumi.store') }}" class="mt-4 space-y-4">
                            @csrf

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Marka</label>
                                    <input name="brand" required placeholder="Piem.: Audi"
                                           class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                </div>

                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Modelis</label>
                                    <input name="model" required placeholder="Piem.: A6"
                                           class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Gads</label>
                                    <input type="number" name="year" required min="1900" max="2099" placeholder="1900–2099"
                                           class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                </div>

                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Nobraukums (km)</label>
                                    <input type="number" name="mileage" required min="0" placeholder="Piem.: 200000"
                                           class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                </div>
                            </div>

                            <button type="submit"
                                    class="w-full rounded-xl bg-zinc-800 px-5 py-3 text-base font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700">
                                Pievienot auto
                            </button>
                        </form>
                    </div>

                    {{-- Share car --}}
                    <div class="mt-8">
                        <h3 class="text-sm font-semibold text-zinc-200">Koplietot auto</h3>

                        @if($cars->isEmpty())
                            <div class="mt-3 text-sm text-zinc-400">Nav apstiprinātu auto, ko koplietot.</div>
                        @else
                            <form method="POST" action="" id="shareForm" class="mt-4 space-y-4">
                                @csrf

                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Izvēlies auto</label>
                                    <select name="car_id" required
                                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                        @foreach($cars as $car)
                                            <option value="{{ $car->id }}">
                                                {{ $car->brand }} {{ $car->model }} ({{ $car->year }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="text-sm font-semibold text-zinc-200">Lietotāja e-pasts</label>
                                    <input type="email" name="user_email" required placeholder="piemers@epasts.lv"
                                           class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                                </div>

                                <button type="submit"
                                        class="w-full rounded-xl bg-zinc-800 px-5 py-3 text-base font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700">
                                    Nosūtīt koplietošanas pieprasījumu
                                </button>

                                <div id="shareResult" class="hidden rounded-xl bg-zinc-950/50 p-4 ring-1 ring-white/10">
                                    <div class="text-sm font-semibold text-zinc-100">Ziņojums</div>
                                    <div id="shareResultText" class="mt-1 text-sm text-zinc-400"></div>
                                </div>
                            </form>
                        @endif
                    </div>
                </section>
            </div>

            {{-- Expenses list --}}
            <section class="mt-6 rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-zinc-100">Izdevumi (pēdējie 50)</h2>
                    <div class="text-sm text-zinc-400">{{ $expenses->count() }} ieraksti</div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-zinc-400">
                            <tr class="border-b border-white/10">
                                <th class="py-2 text-left">Datums</th>
                                <th class="py-2 text-left">Tips</th>
                                <th class="py-2 text-left">Summa</th>
                                <th class="py-2 text-left">Nobraukums</th>
                                <th class="py-2 text-left">Apraksts</th>
                                <th class="py-2 text-right">Darbības</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenses as $e)
                                <tr class="border-b border-white/5">
                                    <td class="py-3 whitespace-nowrap">{{ $e->date }}</td>
                                    <td class="py-3 whitespace-nowrap">{{ $e->type }}</td>
                                    <td class="py-3 whitespace-nowrap">{{ number_format($e->amount, 2) }} €</td>
                                    <td class="py-3 whitespace-nowrap">{{ $e->mileage ? $e->mileage.' km' : '—' }}</td>
                                    <td class="py-3 text-zinc-300">{{ $e->description ?? '—' }}</td>
                                    <td class="py-3 text-right whitespace-nowrap">
                                        <form method="POST" action="{{ route('expenses.destroy', $e->id) }}"
                                              onsubmit="return confirm('Dzēst šo izdevumu?');">
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
                                    <td colspan="6" class="py-6 text-zinc-400">
                                        Nav izdevumu ierakstu.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Koplietošana ar fetch: nosūtām uz /cars/{carId}/share
        const shareForm = document.getElementById('shareForm');
        if (shareForm) {
            shareForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const carId = formData.get('car_id');
                const userEmail = formData.get('user_email');

                fetch(`/cars/${carId}/share`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ user_email: userEmail })
                })
                .then(() => {
                    const box = document.getElementById('shareResult');
                    const text = document.getElementById('shareResultText');
                    text.textContent = 'Koplietošanas pieprasījums nosūtīts.';
                    box.classList.remove('hidden');
                })
                .catch(err => console.error('Error:', err));
            });
        }
    </script>
</body>
</html>
