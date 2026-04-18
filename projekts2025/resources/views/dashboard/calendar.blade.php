<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Apkopes kalendārs</title>

    {{-- Tailwind stili tiek kompilēti ar Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- FullCalendar bibliotēkas CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">

    <style>
        .fc {
            --fc-border-color: rgba(255,255,255,0.10);
            --fc-page-bg-color: transparent;
            --fc-neutral-bg-color: rgba(24,24,27,0.55);
            --fc-today-bg-color: rgba(239,68,68,0.10);
            --fc-event-border-color: rgba(239,68,68,0.35);
            --fc-event-bg-color: rgba(239,68,68,0.25);
            --fc-event-text-color: #fff;
        }

        .fc .fc-toolbar-title {
            color: #e4e4e7;
            font-weight: 700;
        }
    </style>
</head>

<body class="min-h-screen bg-zinc-950 text-zinc-100">

    <!-- Fona vizuālais efekts -->
    <div class="pointer-events-none fixed inset-0">
        <div class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900"></div>
        <div class="absolute -top-20 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-red-600/20 blur-3xl"></div>
    </div>

    <div class="relative mx-auto min-h-screen max-w-6xl px-6">

        <!-- Galvene -->
        <header class="flex items-center justify-between py-8">
            <div>
                <h1 class="text-2xl font-bold uppercase">Apkopes kalendārs</h1>
                <p class="mt-1 text-sm text-zinc-400">
                    Plānojiet auto apkopes un citus ar transportu saistītus notikumus.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}"
                   class="rounded-lg bg-zinc-800 px-5 py-2.5 text-base font-semibold ring-1 ring-white/10 hover:bg-zinc-700">
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

            <!-- Filtrs un pievienošanas poga -->
            <section class="rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                <div class="flex flex-col gap-4 lg:flex-row lg:justify-between">

                    <!-- Notikumu filtrēšana pēc tipa -->
                    <div class="flex-1">
                        <label for="eventFilter" class="block text-sm font-semibold">
                            Filtrs pēc notikuma veida
                        </label>
                        <select id="eventFilter"
                                class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 ring-1 ring-white/10">
                            <option value="">Visi</option>
                            <option value="Apskate">Apskate</option>
                            <option value="Serviss">Serviss</option>
                            <option value="Cits">Cits</option>
                        </select>
                    </div>

                    <!-- Jauna notikuma pievienošana -->
                    <button id="addEventBtn"
                            class="rounded-xl bg-red-600 px-5 py-3 font-semibold text-white hover:bg-red-500">
                        Pievienot notikumu
                    </button>
                </div>
            </section>

            <!-- Kalendāra konteiners -->
            <section class="mt-6 rounded-2xl bg-zinc-900/50 p-4 ring-1 ring-white/10">
                <div id="calendar"></div>
            </section>

        </main>
    </div>

    <!-- MODĀLAIS LOGS notikuma pievienošanai -->
    <div id="eventModal" class="fixed inset-0 z-50 hidden">
        <!-- Tumšais fons -->
        <div id="eventModalBackdrop" class="absolute inset-0 bg-black/60"></div>

        <div class="relative mx-auto flex min-h-screen max-w-2xl items-center px-6">
            <div class="w-full rounded-2xl bg-zinc-950 p-6 ring-1 ring-white/10">

                <h2 class="text-xl font-semibold">Pievienot notikumu</h2>

                <!-- Formas lauki -->
                <div class="mt-6 space-y-4">
                    <input id="eventDate" type="date"
                           class="w-full rounded-xl bg-zinc-900/60 px-4 py-3 ring-1 ring-white/10" />

                    <select id="eventType"
                            class="w-full rounded-xl bg-zinc-900/60 px-4 py-3 ring-1 ring-white/10">
                        <option value="Apskate">Apskate</option>
                        <option value="Serviss">Serviss</option>
                        <option value="Cits">Cits</option>
                    </select>

                    <textarea id="eventDesc" rows="3"
                              class="w-full rounded-xl bg-zinc-900/60 px-4 py-3 ring-1 ring-white/10"
                              placeholder="Apraksts"></textarea>
                </div>

                <!-- Pogas -->
                <div class="mt-6 flex justify-end gap-3">
                    <button id="cancelModalBtn"
                            class="rounded-xl bg-zinc-800 px-5 py-3 ring-1 ring-white/10">
                        Atcelt
                    </button>
                    <button id="saveEventBtn"
                            class="rounded-xl bg-red-600 px-5 py-3 text-white">
                        Saglabāt
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast paziņojums -->
    <div id="toast"
         class="fixed right-6 top-6 hidden rounded-xl bg-zinc-950 p-4 ring-1 ring-white/10">
        <div id="toastText"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Inicializējam kalendāru un ielādējam notikumus no API
            const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                initialView: 'dayGridMonth',
                locale: 'lv',
                events: '/api/events',

                // Klikšķis uz konkrētas dienas atver modālo logu
                dateClick: function(info) {
                    openModal(info.dateStr);
                }
            });

            calendar.render();

            // Modal vadība
            const modal = document.getElementById('eventModal');

            function openModal(date = '') {
                document.getElementById('eventDate').value = date;
                modal.classList.remove('hidden');
            }

            function closeModal() {
                modal.classList.add('hidden');
            }

            document.getElementById('addEventBtn').addEventListener('click', () => openModal());
            document.getElementById('cancelModalBtn').addEventListener('click', closeModal);
            document.getElementById('eventModalBackdrop').addEventListener('click', closeModal);

            // Jauna notikuma saglabāšana
            document.getElementById('saveEventBtn').addEventListener('click', async function() {

                const date = document.getElementById('eventDate').value;
                const title = document.getElementById('eventType').value;
                const description = document.getElementById('eventDesc').value;

                if (!date || !description) return;

                await fetch('/api/events', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ date, title, description })
                });

                closeModal();
                calendar.refetchEvents();
                showToast("Notikums pievienots!");
            });

            // Vienkāršs paziņojuma logs
            function showToast(text) {
                const toast = document.getElementById('toast');
                document.getElementById('toastText').textContent = text;
                toast.classList.remove('hidden');
                setTimeout(() => toast.classList.add('hidden'), 2000);
            }

            // Notikumu filtrēšana pēc tipa
            document.getElementById('eventFilter').addEventListener('change', function() {
                const value = this.value;

                if (!value) {
                    calendar.removeAllEvents();
                    calendar.addEventSource('/api/events');
                    return;
                }

                fetch('/api/events?title=' + value)
                    .then(res => res.json())
                    .then(data => {
                        calendar.removeAllEvents();
                        calendar.addEventSource(data);
                    });
            });

        });
    </script>

</body>
</html>
