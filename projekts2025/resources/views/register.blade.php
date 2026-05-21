<!DOCTYPE html>
<html lang="lv">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Reģistrācija</title>

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
                    <div class="text-2xl font-bold tracking-wide uppercase">Reģistrācija</div>
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

                <form action="{{ url('/register') }}" method="POST" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <label class="text-sm font-semibold text-zinc-200">Lietotājvārds</label>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            value="{{ old('username') }}"
                            placeholder="Izvēlieties lietotājvārdu"
                            required
                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                        />
                        @if ($errors->has('username'))
                            <div class="mt-2 text-sm text-red-200">{{ $errors->first('username') }}</div>
                        @endif
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-zinc-200">E-pasts</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email') }}"
                            placeholder="piemers@epasts.lv"
                            required
                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                        />
                        @if ($errors->has('email'))
                            <div class="mt-2 text-sm text-red-200">{{ $errors->first('email') }}</div>
                        @endif
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-zinc-200">Parole</label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Izveidojiet paroli"
                            required
                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                        />
                        @if ($errors->has('password'))
                            <div class="mt-2 text-sm text-red-200">{{ $errors->first('password') }}</div>
                        @endif
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-zinc-200">Paroles apstiprinājums</label>
                        <input
                            type="password"
                            name="password_confirmation"
                            id="password_confirmation"
                            placeholder="Atkārtojiet paroli"
                            required
                            class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                        />
                        @if ($errors->has('password_confirmation'))
                            <div class="mt-2 text-sm text-red-200">{{ $errors->first('password_confirmation') }}</div>
                        @endif
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-red-600 px-5 py-3 text-base font-semibold text-white hover:bg-red-500"
                    >
                        Reģistrēties
                    </button>

                    <div class="pt-2 text-center text-sm text-zinc-400">
                        Jau ir konts?
                        <a href="{{ route('login') }}" class="font-semibold text-zinc-200 hover:text-white">
                            Pieslēgties
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>
