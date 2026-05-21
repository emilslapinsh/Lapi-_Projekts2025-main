<!DOCTYPE html>
<html lang="lv">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Apkopes kalendārs</title>

        <?php // Pieslēdz projekta stilus un JS ?>
        @include('partials.vite-assets')

        <?php // FullCalendar stili kalendāram ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" />

        @include('partials.flatpickr-lv-head')

        <style>
            .fc {
                --fc-border-color: rgba(255, 255, 255, 0.1);
                --fc-page-bg-color: transparent;
                --fc-neutral-bg-color: rgba(24, 24, 27, 0.55);
                --fc-today-bg-color: rgba(239, 68, 68, 0.1);
                --fc-event-border-color: rgba(239, 68, 68, 0.35);
                --fc-event-bg-color: rgba(239, 68, 68, 0.25);
                --fc-event-text-color: #fff;
            }

            .fc .fc-toolbar-title {
                color: #e4e4e7;
                font-weight: 700;
            }
        </style>
    </head>

    <body class="min-h-screen bg-zinc-950 text-zinc-100">
        <?php // Fona vizuālais efekts ?>
        <div class="pointer-events-none fixed inset-0">
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900"></div>
            <div class="absolute -top-20 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-red-600/20 blur-3xl"></div>
        </div>

        <div class="relative mx-auto min-h-screen max-w-6xl px-6">
            <?php // Galvene ar navigāciju ?>
            <header class="flex items-center justify-between py-8">
                <div>
                    <h1 class="text-2xl font-bold uppercase">Apkopes kalendārs</h1>
                    <p class="mt-1 text-sm text-zinc-400">
                        Plānojiet auto apkopes un citus ar transportu saistītus notikumus.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <a
                        href="{{ route('home') }}"
                        class="rounded-lg bg-zinc-800 px-5 py-2.5 text-base font-semibold ring-1 ring-white/10 hover:bg-zinc-700"
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
                <?php // Filtrs pēc notikuma veida un poga jauna notikuma izveidei ?>
                <section class="mt-6 rounded-2xl bg-zinc-900/50 p-6 ring-1 ring-white/10">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div class="flex-1">
                            <label for="eventFilter" class="block text-sm font-semibold text-zinc-200">
                                Filtrs pēc notikuma veida
                            </label>
                            <select
                                id="eventFilter"
                                class="mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/40"
                            >
                                <option value="">Visi</option>
                                @foreach ($eventTypes as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button
                            type="button"
                            id="addEventBtn"
                            class="shrink-0 rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-500"
                        >
                            Pievienot notikumu
                        </button>
                    </div>
                </section>

                <?php // FullCalendar laukums ?>
                <section class="mt-6 rounded-2xl bg-zinc-900/50 p-4 ring-1 ring-white/10 sm:p-6">
                    <div id="calendar"></div>
                </section>
            </main>
        </div>

        <?php // Logs notikuma pievienošanai un labošanai ?>
        <div
            id="eventModal"
            class="fixed inset-0 z-50 hidden"
            role="dialog"
            aria-modal="true"
            aria-labelledby="eventModalTitle"
        >
            <div id="eventModalBackdrop" class="absolute inset-0 bg-black/60"></div>

            <div class="relative mx-auto flex min-h-screen max-w-2xl items-center px-4 py-8 sm:px-6">
                <div class="max-h-[90vh] w-full overflow-y-auto rounded-2xl bg-zinc-950 p-6 ring-1 ring-white/10">
                    <h2 id="eventModalTitle" class="text-xl font-semibold text-zinc-100">Pievienot notikumu</h2>
                    <input type="hidden" id="editingEventId" value="" />

                    <div class="mt-6 space-y-4">
                        <div>
                            <label for="eventDate" class="block text-sm font-semibold text-zinc-200">Datums</label>
                            <input
                                id="eventDate"
                                type="text"
                                inputmode="none"
                                autocomplete="off"
                                placeholder="Izvēlies datumu..."
                                class="mt-2 w-full rounded-xl bg-zinc-900/60 px-4 py-3 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/40"
                            />
                        </div>

                        <div>
                            <label for="eventType" class="block text-sm font-semibold text-zinc-200">Veids</label>
                            <select
                                id="eventType"
                                class="mt-2 w-full rounded-xl bg-zinc-900/60 px-4 py-3 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/40"
                            >
                                @foreach ($eventTypes as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="eventDesc" class="block text-sm font-semibold text-zinc-200">Apraksts</label>
                            <textarea
                                id="eventDesc"
                                rows="3"
                                maxlength="5000"
                                class="mt-2 w-full rounded-xl bg-zinc-900/60 px-4 py-3 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/40"
                                placeholder="Piemēram, TA termiņš, riepu maiņa, servisa pieraksts… (nav obligāts)"
                            ></textarea>
                        </div>
                    </div>

                    <div
                        id="modalError"
                        class="mt-4 hidden rounded-xl bg-red-500/10 p-3 text-sm text-red-200 ring-1 ring-red-500/25"
                    ></div>

                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <button
                            type="button"
                            id="deleteEventBtn"
                            class="hidden rounded-xl bg-red-950/80 px-5 py-3 text-sm font-semibold text-red-200 ring-1 ring-red-500/30 hover:bg-red-900/80"
                        >
                            Dzēst notikumu
                        </button>
                        <div class="flex justify-end gap-3 sm:ml-auto">
                            <button
                                type="button"
                                id="cancelModalBtn"
                                class="rounded-xl bg-zinc-800 px-5 py-3 text-sm font-semibold ring-1 ring-white/10 hover:bg-zinc-700"
                            >
                                Atcelt
                            </button>
                            <button
                                type="button"
                                id="saveEventBtn"
                                class="rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-500"
                            >
                                Saglabāt
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php // Īss paziņojums augšējā stūrī (success/error) ?>
        <div
            id="toast"
            class="fixed right-4 top-4 z-[60] hidden max-w-sm rounded-xl p-4 shadow-lg ring-1 sm:right-6 sm:top-6"
        >
            <div id="toastText" class="text-sm font-medium"></div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/lv.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // JS starts pēc lapas ielādes
                // Stils, ko izmanto datuma laukam Flatpickr 
                var fpAltCls =
                    'mt-2 w-full rounded-xl bg-zinc-900/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/40';

                // API ceļš un CSRF tokens priekš pieprasījumiem
                const apiBase = @json(url('/api/events'));
                const csrf = @json(csrf_token());

                // Modālā loga elementi
                const modal = document.getElementById('eventModal');
                const modalTitle = document.getElementById('eventModalTitle');
                const editingEventId = document.getElementById('editingEventId');
                const deleteEventBtn = document.getElementById('deleteEventBtn');
                const modalError = document.getElementById('modalError');

                // Datuma izvēle notikuma izveidei/labošanai
                const fpEventDate = flatpickr('#eventDate', {
                    locale: flatpickr.l10ns.lv,
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd.m.Y',
                    allowInput: true,
                    clickOpens: true,
                    disableMobile: true,
                    appendTo: modal,
                    static: false,
                    monthSelectorType: 'static',
                    altInputClass: fpAltCls,
                });

                // Paslēpj kļūdas tekstu modālajā logā
                function hideModalError() {
                    modalError.textContent = '';
                    modalError.classList.add('hidden');
                }

                // Parāda kļūdas tekstu modālajā logā
                function showModalError(msg) {
                    modalError.textContent = msg;
                    modalError.classList.remove('hidden');
                }

                // Parāda īsu paziņojumu (zaļš vai sarkans)
                function showToast(text, ok) {
                    const toast = document.getElementById('toast');
                    const toastText = document.getElementById('toastText');
                    toastText.textContent = text;
                    toast.classList.remove(
                        'hidden',
                        'bg-emerald-500/15',
                        'ring-emerald-500/25',
                        'text-emerald-100',
                        'bg-red-500/15',
                        'ring-red-500/25',
                        'text-red-100'
                    );
                    if (ok) {
                        toast.classList.add('bg-emerald-500/15', 'ring-emerald-500/25', 'text-emerald-100');
                    } else {
                        toast.classList.add('bg-red-500/15', 'ring-red-500/25', 'text-red-100');
                    }
                    toast.classList.add('ring-1');
                    clearTimeout(showToast._t);
                    showToast._t = setTimeout(function () {
                        toast.classList.add('hidden');
                    }, 3200);
                }

                // Izvelk pirmo validācijas kļūdu no JSON atbildes
                function firstValidationMessage(data) {
                    if (!data || !data.errors) return data && data.message ? data.message : 'Kļūda saglabājot.';
                    const keys = Object.keys(data.errors);
                    if (!keys.length) return 'Kļūda saglabājot.';
                    const arr = data.errors[keys[0]];
                    return Array.isArray(arr) ? arr[0] : String(arr);
                }

                // Inicializē FullCalendar ar notikumu ielādi
                const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                    initialView: 'dayGridMonth',
                    locale: 'lv',
                    firstDay: 1,
                    height: 'auto',
                    dayMaxEvents: 4,
                    eventDisplay: 'block',
                    displayEventTime: false,
                    events: apiBase,

                    // Teksts uz notikuma (title + apraksts)
                    eventDidMount: function (info) {
                        const desc = (info.event.extendedProps && info.event.extendedProps.description) || '';
                        const t = info.event.title || '';
                        info.el.title = desc ? t + ' - ' + desc : t;
                    },

                    // Klikšķis uz dienas atver izveides logu
                    dateClick: function (info) {
                        openModalForCreate(info.dateStr);
                    },

                    // Klikšķis uz notikuma atver labošanas logu
                    eventClick: function (info) {
                        info.jsEvent.preventDefault();
                        const ev = info.event;
                        const id = ev.id;
                        const title = ev.title;
                        const start = ev.startStr ? ev.startStr.slice(0, 10) : '';
                        const desc = (ev.extendedProps && ev.extendedProps.description) || '';
                        openModalForEdit(id, start, title, desc);
                    },
                });

                calendar.render();

                // Atver jauna notikuma izveidi
                function openModalForCreate(dateStr) {
                    hideModalError();
                    editingEventId.value = '';
                    modalTitle.textContent = 'Pievienot notikumu';
                    deleteEventBtn.classList.add('hidden');
                    document.getElementById('eventDesc').value = '';
                    document.getElementById('eventType').selectedIndex = 0;
                    if (dateStr) {
                        fpEventDate.setDate(dateStr, true, 'Y-m-d');
                    } else {
                        fpEventDate.clear();
                    }
                    modal.classList.remove('hidden');
                    if (dateStr)
                        setTimeout(function () {
                            fpEventDate.open();
                        }, 0);
                }

                // Atver notikuma labošanu
                function openModalForEdit(id, dateStr, title, description) {
                    hideModalError();
                    editingEventId.value = String(id);
                    modalTitle.textContent = 'Labot notikumu';
                    deleteEventBtn.classList.remove('hidden');
                    fpEventDate.setDate(dateStr, true, 'Y-m-d');
                    const typeSel = document.getElementById('eventType');
                    typeSel.querySelectorAll('option[data-legacy="1"]').forEach(function (o) {
                        o.remove();
                    });
                    typeSel.value = title;
                    if (typeSel.value !== title) {
                        const opt = document.createElement('option');
                        opt.value = title;
                        opt.textContent = title + ' (vēsturisks)';
                        opt.setAttribute('data-legacy', '1');
                        typeSel.appendChild(opt);
                        typeSel.value = title;
                    }
                    document.getElementById('eventDesc').value = description || '';
                    modal.classList.remove('hidden');
                }

                // Aizver un notīra pagaidu datus
                function closeModal() {
                    modal.classList.add('hidden');
                    fpEventDate.close();
                    hideModalError();
                    document
                        .getElementById('eventType')
                        .querySelectorAll('option[data-legacy="1"]')
                        .forEach(function (o) {
                            o.remove();
                        });
                }

                // Poga "Pievienot notikumu"
                document.getElementById('addEventBtn').addEventListener('click', function () {
                    openModalForCreate('');
                    setTimeout(function () {
                        fpEventDate.open();
                    }, 0);
                });
                document.getElementById('cancelModalBtn').addEventListener('click', closeModal);
                document.getElementById('eventModalBackdrop').addEventListener('click', closeModal);

                // ESC aizver modāli
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
                });

                // Saglabā notikumu
                document.getElementById('saveEventBtn').addEventListener('click', async function () {
                    hideModalError();
                    const date = document.getElementById('eventDate').value;
                    const title = document.getElementById('eventType').value;
                    const description = document.getElementById('eventDesc').value;

                    if (!date) {
                        showModalError('Izvēlies datumu.');
                        return;
                    }

                    const id = editingEventId.value;
                    const url = id ? apiBase + '/' + encodeURIComponent(id) : apiBase;
                    const method = id ? 'PUT' : 'POST';

                    const res = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ date: date, title: title, description: description || '' }),
                    });

                    let data = {};
                    try {
                        data = await res.json();
                    } catch (e) {}

                    if (!res.ok) {
                        showModalError(firstValidationMessage(data));
                        return;
                    }

                    closeModal();
                    calendar.refetchEvents();
                    showToast(id ? 'Notikums atjaunināts.' : 'Notikums pievienots.', true);
                });

                document.getElementById('deleteEventBtn').addEventListener('click', async function () {
                    const id = editingEventId.value;
                    if (!id) return;
                    if (!confirm('Dzēst šo notikumu?')) return;

                    const res = await fetch(apiBase + '/' + encodeURIComponent(id), {
                        method: 'DELETE',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    let data = {};
                    try {
                        data = await res.json();
                    } catch (e) {}

                    if (!res.ok) {
                        showToast(firstValidationMessage(data), false);
                        return;
                    }

                    closeModal();
                    calendar.refetchEvents();
                    showToast('Notikums dzēsts.', true);
                });

                document.getElementById('eventFilter').addEventListener('change', function () {
                    const value = this.value;
                    calendar.getEventSources().forEach(function (src) {
                        src.remove();
                    });
                    if (!value) {
                        calendar.addEventSource(apiBase);
                    } else {
                        calendar.addEventSource(apiBase + '?title=' + encodeURIComponent(value));
                    }
                });
            });
        </script>
    </body>
</html>
