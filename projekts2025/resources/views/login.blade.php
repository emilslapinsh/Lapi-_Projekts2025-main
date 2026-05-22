<!DOCTYPE html>
<?php // Pieslēgšanās forma ?>
<html lang="lv">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Pieslēgties</title>
        @include('partials.vite-assets')
    </head>

    <body class="min-h-screen bg-zinc-950 text-zinc-100">
        <?php // Fona gradients un vietas migluma slāņi ?>
        <div class="pointer-events-none fixed inset-0">
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900"></div>
            <div class="absolute -top-20 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-red-600/20 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-72 w-72 rounded-full bg-zinc-700/10 blur-3xl"></div>
        </div>

        <div class="relative flex min-h-screen items-center justify-center px-6">
            <div class="w-full max-w-md rounded-2xl bg-zinc-900/50 p-8 ring-1 ring-white/10">
                <div class="text-center">
                    <div class="text-2xl font-bold tracking-wide uppercase">Pieslēgties</div>
                    <p class="mt-2 text-sm text-zinc-400">Auto apkopes un izdevumu palīgrīks</p>
                </div>

                @if ($errors->any())
                    <div class="mt-6 rounded-2xl bg-red-500/10 p-4 ring-1 ring-red-500/20">
                        <div class="text-sm font-semibold text-red-200">Kļūda</div>
                        <ul class="mt-2 space-y-1 text-sm text-red-100/80">
                            @foreach ($errors->all() as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ url('/login') }}" method="POST" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <label class="text-sm font-semibold text-zinc-200">Lietotājvārds</label>
                        <input
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            placeholder="Ievadiet lietotājvārdu"
                            required
                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                        />
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-zinc-200">Parole</label>
                        <input
                            type="password"
                            name="password"
                            placeholder="Ievadiet paroli"
                            required
                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                        />
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-red-600 px-5 py-3 text-base font-semibold text-white hover:bg-red-500"
                    >
                        Pieslēgties
                    </button>

                    <div class="pt-2 text-center text-sm text-zinc-400">
                        Nav konta?
                        <a href="{{ route('register') }}" class="font-semibold text-zinc-200 hover:text-white">
                            Reģistrēties
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>
