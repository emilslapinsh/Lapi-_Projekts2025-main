<!DOCTYPE html>
<html lang="lv">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Labot izdevumu</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.flatpickr-lv-head')
    </head>

    <body class="min-h-screen bg-zinc-950 text-zinc-100">
        <div class="pointer-events-none fixed inset-0">
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900"></div>
            <div class="absolute -top-20 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-red-600/20 blur-3xl"></div>
        </div>

        <div class="relative mx-auto min-h-screen max-w-2xl px-6 py-10">
            <header class="mb-8 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold uppercase tracking-wide">Labot izdevumu</h1>
                    <p class="mt-1 text-sm text-zinc-400">
                        {{ $expense->car->brand }} {{ $expense->car->model }} ({{ $expense->car->year }})
                    </p>
                </div>
                <a
                    href="{{ route('izdevumi.index', array_merge(request()->query(), ['car_id' => $expense->car_id])) }}"
                    class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-semibold ring-1 ring-white/10 hover:bg-zinc-700"
                >
                    Atpakaļ
                </a>
            </header>

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
                <form method="POST" action="{{ route('expenses.update', $expense) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

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
                                id="expense_type"
                                required
                                class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                            >
                                @foreach ($expenseTypes as $t)
                                    <option value="{{ $t }}" @selected(old('type', $expense->type) === $t)>
                                        {{ $t }}
                                    </option>
                                @endforeach

                                @if (! in_array($expense->type, $expenseTypes, true))
                                    <option value="{{ $expense->type }}" selected>
                                        {{ $expense->type }} (vēsturisks)
                                    </option>
                                @endif
                            </select>
                            <p
                                id="expense_type_hint"
                                class="mt-2 min-h-[2.5rem] text-xs leading-relaxed text-zinc-500"
                            ></p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-zinc-200">Datums</label>
                            <input
                                type="text"
                                name="date"
                                required
                                value="{{ old('date', $expense->date->toDateString()) }}"
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
                                value="{{ old('amount', $expense->amount) }}"
                                class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                            />
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-zinc-200">Nobraukums (km)</label>
                            <input
                                type="number"
                                name="mileage"
                                min="0"
                                value="{{ old('mileage', $expense->mileage) }}"
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
                            value="{{ old('description', $expense->description) }}"
                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                        />
                    </div>

                    <p class="text-xs leading-relaxed text-zinc-500">
                        <span class="font-semibold text-zinc-400">Padomi:</span>
                        Serviss — norādi nobraukumu, lai kopējais €/km aprēķins būtu jēdzīgāks. Remonts — īss apraksts
                        palīdz atcerēties darbu. Apdrošināšana — var norādīt polises periodu aprakstā.
                    </p>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button
                            type="submit"
                            class="rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-500"
                        >
                            Saglabāt
                        </button>
                        <a
                            href="{{ route('izdevumi.index', array_merge(request()->query(), ['car_id' => $expense->car_id])) }}"
                            class="rounded-xl bg-zinc-800 px-5 py-3 text-sm font-semibold ring-1 ring-white/10 hover:bg-zinc-700"
                        >
                            Atcelt
                        </a>
                    </div>
                </form>
            </section>
        </div>

        <script>
            (function () {
                const hints = @json($typeHints ?? []);
                const sel = document.getElementById('expense_type');
                const hintEl = document.getElementById('expense_type_hint');
                if (!sel || !hintEl) return;
                function sync() {
                    hintEl.textContent = hints[sel.value] || '';
                }
                sel.addEventListener('change', sync);
                sync();
            })();
        </script>
        @include('partials.flatpickr-lv-scripts')
    </body>
</html>
