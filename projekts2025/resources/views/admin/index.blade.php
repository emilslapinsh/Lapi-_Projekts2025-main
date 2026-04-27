<!DOCTYPE html>
<html lang="lv">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Administrācija</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="min-h-screen bg-zinc-950 text-zinc-100">
        <div class="pointer-events-none fixed inset-0">
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900"></div>
            <div class="absolute -top-20 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-red-600/20 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-72 w-72 rounded-full bg-zinc-700/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto min-h-screen max-w-6xl px-6">
            <header class="flex flex-col gap-4 py-8 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-wide uppercase">Administrācija</h1>
                    <p class="mt-1 text-sm text-zinc-400">Lietotāju pārvaldība, meklēšana un kārtošana.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <a
                        href="{{ route('home') }}"
                        class="rounded-lg bg-zinc-800 px-5 py-2.5 text-base font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                    >
                        Atpakaļ uz paneli
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
                @if (session('success'))
                    <div class="mb-6 rounded-2xl bg-emerald-500/10 p-4 ring-1 ring-emerald-500/20">
                        <div class="text-sm font-semibold text-emerald-200">Veiksmīgi</div>
                        <div class="mt-1 text-sm text-emerald-100/80">{{ session('success') }}</div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 rounded-2xl bg-red-500/10 p-4 ring-1 ring-red-500/20">
                        <div class="text-sm font-semibold text-red-200">Neizdevās</div>
                        <div class="mt-1 text-sm text-red-100/80">{{ session('error') }}</div>
                    </div>
                @endif

                <section class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                        <div class="text-sm font-medium text-zinc-400">Lietotāji kopā</div>
                        <div class="mt-2 text-3xl font-bold text-white">{{ $stats['users'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                        <div class="text-sm font-medium text-zinc-400">Administratori</div>
                        <div class="mt-2 text-3xl font-bold text-white">{{ $stats['admins'] ?? 0 }}</div>
                        <p class="mt-2 text-xs text-zinc-500"></p>
                    </div>
                </section>

                <section class="mt-8 rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                    <form method="GET" action="{{ route('admin') }}" class="grid gap-4 lg:grid-cols-3">
                        <div class="lg:col-span-2">
                            <label for="q" class="block text-sm font-semibold text-zinc-200">Meklēt</label>
                            <input
                                id="q"
                                name="q"
                                value="{{ $q ?? '' }}"
                                placeholder="Lietotājvārds vai e-pasts..."
                                class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/40"
                            />
                        </div>

                        <div>
                            <label for="order_by" class="block text-sm font-semibold text-zinc-200">Kārtot</label>
                            <select
                                id="order_by"
                                name="order_by"
                                onchange="this.form.submit()"
                                class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/40"
                            >
                                <option
                                    value="username_asc"
                                    {{ ($orderBy ?? '') === 'username_asc' ? 'selected' : '' }}
                                >
                                    Lietotājvārds (A–Ž)
                                </option>
                                <option
                                    value="username_desc"
                                    {{ ($orderBy ?? '') === 'username_desc' ? 'selected' : '' }}
                                >
                                    Lietotājvārds (Ž–A)
                                </option>
                                <option value="email_asc" {{ ($orderBy ?? '') === 'email_asc' ? 'selected' : '' }}>
                                    E-pasts (A–Ž)
                                </option>
                                <option value="email_desc" {{ ($orderBy ?? '') === 'email_desc' ? 'selected' : '' }}>
                                    E-pasts (Ž–A)
                                </option>
                            </select>
                        </div>

                        <div class="flex items-end gap-3 lg:col-span-3">
                            <button
                                type="submit"
                                class="rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-500"
                            >
                                Atlasīt
                            </button>

                            <a
                                href="{{ route('admin') }}"
                                class="rounded-xl bg-zinc-800 px-5 py-3 text-sm font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                            >
                                Notīrīt filtrus
                            </a>
                        </div>
                    </form>
                </section>

                <section class="mt-8 overflow-hidden rounded-2xl bg-zinc-900/50 ring-1 ring-white/10">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[860px] text-sm">
                            <thead class="text-left text-zinc-400">
                                <tr class="border-b border-white/10">
                                    <th class="px-5 py-3 font-semibold">Lietotājvārds</th>
                                    <th class="px-5 py-3 font-semibold">E-pasts</th>
                                    <th class="px-5 py-3 font-semibold">Loma</th>
                                    <th class="px-5 py-3 font-semibold">Izveidots</th>
                                    <th class="px-5 py-3 text-right font-semibold">Darbības</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr class="border-b border-white/5">
                                        <td class="px-5 py-4 font-semibold text-zinc-100">
                                            {{ $user->username }}
                                        </td>
                                        <td class="px-5 py-4 text-zinc-200">
                                            {{ $user->email }}
                                        </td>
                                        <td class="px-5 py-4">
                                            @if ($user->isAdmin())
                                                <span
                                                    class="rounded-full bg-red-500/15 px-3 py-1 text-xs font-semibold text-red-200 ring-1 ring-red-500/25"
                                                >
                                                    Administrators
                                                </span>
                                            @else
                                                <span
                                                    class="rounded-full bg-zinc-800 px-3 py-1 text-xs font-semibold text-zinc-200 ring-1 ring-white/10"
                                                >
                                                    Lietotājs
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-zinc-300">
                                            {{ $user->created_at?->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <form
                                                method="POST"
                                                action="{{ route('admin.deleteUser', $user->id) }}"
                                                onsubmit="return apstiprinatDzesanu();"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="rounded-lg bg-zinc-950/40 px-3 py-2 text-xs font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-900 hover:ring-white/20"
                                                >
                                                    Dzēst
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-10 text-center text-zinc-400">
                                            Nav atrastu lietotāju pēc atlasītā filtra.
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
            function apstiprinatDzesanu() {
                return confirm('Vai tiešām vēlies dzēst šo lietotāju? Šo darbību nevar atsaukt.');
            }
        </script>
    </body>
</html>
