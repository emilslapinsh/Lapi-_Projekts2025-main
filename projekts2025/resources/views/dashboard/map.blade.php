<!DOCTYPE html>
<?php // Interaktīvā Leaflet karte ar klasteriem; dati tiek atlasīti pēc redzamā apgabala ?>
<html lang="lv">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Karte</title>

        @include('partials.vite-assets')

        <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />

        <?php // Kartes augstums un uznirstošo logu izskata pielāgošana panelim ?>
        <style>
            #map {
                height: 620px;
                width: 100%;
                border-radius: 16px;
            }

            .leaflet-popup-content-wrapper {
                background: rgba(24, 24, 27, 0.95);
                color: #f4f4f5;
                border-radius: 14px;
                box-shadow: 0 12px 35px rgba(0, 0, 0, 0.45);
            }
            .leaflet-popup-tip {
                background: rgba(24, 24, 27, 0.95);
            }
            .leaflet-control-zoom a {
                background: rgba(24, 24, 27, 0.9);
                color: #f4f4f5;
                border: 1px solid rgba(255, 255, 255, 0.12);
            }
            .leaflet-control-zoom a:hover {
                background: rgba(39, 39, 42, 0.9);
            }
        </style>
    </head>

    <body class="min-h-screen bg-zinc-950 text-zinc-100">
        <?php // Fona gradients kā pārējās dashboard lapās ?>
                <?php // Fona gradients un glow slÄÅ†i ?>
        <div class="pointer-events-none fixed inset-0">
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900"></div>
            <div class="absolute -top-20 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-red-600/20 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-72 w-72 rounded-full bg-zinc-700/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto min-h-screen max-w-6xl px-6">
            <header class="flex items-center justify-between py-8">
                <div>
                    <h1 class="text-2xl font-bold tracking-wide uppercase text-zinc-100">Karte</h1>
                    <p class="mt-1 text-sm text-zinc-400">
                        Lokāciju pārskats (uzpildes stacijas, servisi un EV uzlāde).
                    </p>
                </div>

                <div class="flex items-center gap-3">
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
                <section class="grid gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2 rounded-2xl bg-zinc-900/50 p-4 ring-1 ring-white/10">
                        <div class="mb-3 flex items-center justify-between">
                            <div class="text-base font-semibold">Kartes skats</div>
                            <div class="text-sm text-zinc-400" id="hint"></div>
                        </div>

                        <div class="overflow-hidden rounded-2xl ring-1 ring-white/10">
                            <div id="map"></div>
                        </div>
                    </div>

                    <aside class="rounded-2xl bg-zinc-900/50 p-5 ring-1 ring-white/10">
                        <div class="mb-4">
                            <div class="text-base font-semibold">Meklēšana</div>
                            <p class="mt-1 text-sm text-zinc-400">Filtrē lokācijas pēc nosaukuma un tipa.</p>
                        </div>

                        <input
                            type="text"
                            id="search"
                            placeholder="Meklēt lokācijas..."
                            class="w-full rounded-lg bg-zinc-950/60 px-4 py-2.5 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/40"
                        />

                        <div class="mt-4 flex flex-wrap gap-2">
                            <button
                                class="filter-btn active rounded-full bg-red-600 px-3 py-2 text-sm font-semibold text-white"
                                data-type="all"
                            >
                                Visi
                            </button>

                            <button
                                class="filter-btn rounded-full bg-zinc-800 px-3 py-2 text-sm font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                                data-type="gas_station"
                            >
                                Uzpildes stacijas
                            </button>

                            <button
                                class="filter-btn rounded-full bg-zinc-800 px-3 py-2 text-sm font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                                data-type="service_center"
                            >
                                Servisi
                            </button>

                            <button
                                class="filter-btn rounded-full bg-zinc-800 px-3 py-2 text-sm font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                                data-type="ev_charging"
                            >
                                EV uzlāde
                            </button>
                        </div>

                        <div class="mt-5 border-t border-white/10 pt-4">
                            <div class="mb-2 text-sm font-semibold text-zinc-200">Lokācijas</div>
                            <div id="location-list" class="max-h-[420px] space-y-2 overflow-y-auto pr-1"></div>
                        </div>
                    </aside>
                </section>
            </main>
        </div>

        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
        <script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>

        <script>
            // Sākšanās skats ap Latviju; zoom 7 dod platu kontekstu
            const map = L.map('map').setView([56.946, 24.105], 7);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap',
            }).addTo(map);

            // Markeri grupē tuvu viens otram, lai nesajauktu karti
            const clusterGroup = L.markerClusterGroup();
            map.addLayer(clusterGroup);

            let allMarkers = [];
            let currentFilter = 'all';
            let searchQuery = '';

            const listEl = document.getElementById('location-list');
            const searchInput = document.getElementById('search');
            const filterButtons = document.querySelectorAll('.filter-btn');
            const hintEl = document.getElementById('hint');

            // Pop-up un sarakstam droši rāda tukšas vērtības kā tukšu virkni
            function safeText(value) {
                return (value ?? '').toString();
            }

            // API tips -> īss latvisks paraksts saskarnē
            function typeLabel(type) {
                if (type === 'gas_station') return 'uzpildes stacija';
                if (type === 'service_center') return 'autoserviss';
                if (type === 'ev_charging') return 'EV uzlāde';
                return (type ?? '').replace('_', ' ');
            }

            // Aplīša krāsa pēc lokācijas veida
            function markerColor(type) {
                if (type === 'gas_station') return '#ef4444';
                if (type === 'service_center') return '#22c55e';
                if (type === 'ev_charging') return '#f97316';
                return '#3b82f6';
            }

            // Backend sagaida south,west,north,east ar fiksētu precizitāti
            function bboxString() {
                const b = map.getBounds();
                return [
                    b.getSouth().toFixed(6),
                    b.getWest().toFixed(6),
                    b.getNorth().toFixed(6),
                    b.getEast().toFixed(6),
                ].join(',');
            }

            // Dinamisks padoms atkarībā no tā vai API vispār būs lokācijas
            function updateHint() {
                const zoom = map.getZoom();
                if (zoom < 10) {
                    hintEl.textContent = 'Padoms: pietuvini karti (DUS/Servisi no zoom ≥ 10, EV no zoom ≥ 11).';
                } else if (zoom < 11) {
                    hintEl.textContent = 'DUS un servisi ielādējas. EV uzlādei nepieciešams zoom ≥ 11.';
                } else {
                    hintEl.textContent = 'Vari pārvietot karti - lokācijas tiek atlasītas redzamajā apgabalā.';
                }
            }

            // Labās kolonnas sarakstu pēc aktivā filtra un meklēšanas
            function renderList() {
                listEl.innerHTML = '';

                const filtered = allMarkers.filter(({ name, type, address }) => {
                    const okType = currentFilter === 'all' || type === currentFilter;
                    const q = searchQuery;
                    const okSearch =
                        (name || '').toLowerCase().includes(q) || (address || '').toLowerCase().includes(q);

                    return okType && okSearch;
                });

                if (filtered.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'rounded-xl bg-zinc-950/40 p-4 text-sm text-zinc-400 ring-1 ring-white/10';
                    empty.textContent = 'Nav atrastu lokāciju pēc izvēlētā filtra.';
                    listEl.appendChild(empty);
                    return;
                }

                // Klikšķis uz rindas centrē karti pie marķiera
                filtered.forEach(({ marker, name, type, address }) => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className =
                        'w-full text-left rounded-xl bg-zinc-950/40 p-4 ring-1 ring-white/10 transition hover:bg-zinc-900 hover:ring-white/20';

                    const wrap = document.createElement('div');
                    wrap.className = 'flex items-start justify-between gap-3';

                    const left = document.createElement('div');

                    const title = document.createElement('div');
                    title.className = 'text-base font-semibold text-zinc-100';
                    title.textContent = safeText(name);

                    const sub = document.createElement('div');
                    sub.className = 'mt-1 text-sm text-zinc-400';
                    sub.textContent = safeText(address);

                    left.appendChild(title);
                    left.appendChild(sub);

                    const badge = document.createElement('span');
                    badge.className = 'shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-white/10';
                    badge.style.background = 'rgba(24,24,27,0.6)';
                    badge.style.color = '#e4e4e7';
                    badge.textContent = typeLabel(type);

                    wrap.appendChild(left);
                    wrap.appendChild(badge);
                    item.appendChild(wrap);

                    item.addEventListener('click', () => {
                        map.setView(marker.getLatLng(), Math.max(map.getZoom(), 13));
                        marker.openPopup();
                    });

                    listEl.appendChild(item);
                });
            }

            let fetchTimer = null;
            let fetchInFlight = false;
            // Neierosina API katru pikseļa pārvietojumu
            function scheduleFetch() {
                if (fetchTimer) clearTimeout(fetchTimer);
                fetchTimer = setTimeout(fetchLocations, 500);
            }

            // Backend ierobežo smago pieprasījumu skaitu zem zema zoom
            function shouldFetchAtZoom(type, zoom) {
                if (type === 'ev_charging') return zoom >= 11;
                if (type === 'gas_station') return zoom >= 10;
                if (type === 'service_center') return zoom >= 10;
                if (type === 'all') return zoom >= 10;
                return zoom >= 10;
            }

            // Ielādē lokācijas json pēc bbox + zoom + tips
            async function fetchLocations() {
                if (fetchInFlight) {
                    scheduleFetch();
                    return;
                }

                const zoom = map.getZoom();
                if (!shouldFetchAtZoom(currentFilter, zoom)) {
                    allMarkers = [];
                    clusterGroup.clearLayers();
                    renderList();
                    updateHint();
                    return;
                }

                const bbox = bboxString();
                const url = `/api/locations?bbox=${encodeURIComponent(bbox)}&zoom=${zoom}&type=${encodeURIComponent(currentFilter)}`;

                fetchInFlight = true;
                hintEl.textContent = 'Ielādē...';

                try {
                    const res = await fetch(url);
                    if (!res.ok) {
                        throw new Error(`HTTP ${res.status}`);
                    }
                    const locations = await res.json();

                    allMarkers = [];
                    clusterGroup.clearLayers();

                    locations.forEach((location) => {
                        const lat = parseFloat(location.latitude);
                        const lon = parseFloat(location.longitude);
                        if (Number.isNaN(lat) || Number.isNaN(lon)) return;

                        const color = markerColor(location.type);

                        // Aplītis labāk salīdzinās ar klasteru plugin nekā ikona
                        const marker = L.circleMarker([lat, lon], {
                            radius: 8,
                            color: color,
                            fillColor: color,
                            fillOpacity: 0.85,
                            weight: 2,
                        });

                        const popup = document.createElement('div');
                        popup.style.minWidth = '220px';

                        const pTitle = document.createElement('div');
                        pTitle.style.fontWeight = '700';
                        pTitle.style.fontSize = '14px';
                        pTitle.style.marginBottom = '6px';
                        pTitle.textContent = safeText(location.name);

                        const pAddr = document.createElement('div');
                        pAddr.style.color = '#a1a1aa';
                        pAddr.style.fontSize = '12px';
                        pAddr.textContent = safeText(location.address);

                        const pType = document.createElement('div');
                        pType.style.marginTop = '8px';
                        pType.style.fontSize = '12px';
                        pType.style.color = '#e4e4e7';
                        pType.textContent = 'Tips: ' + typeLabel(location.type);

                        popup.appendChild(pTitle);
                        popup.appendChild(pAddr);
                        popup.appendChild(pType);
                        marker.bindPopup(popup);

                        clusterGroup.addLayer(marker);

                        allMarkers.push({
                            marker,
                            name: location.name ?? '',
                            type: location.type ?? '',
                            address: location.address ?? '',
                        });
                    });

                    renderList();
                    if (locations.length === 0) {
                        hintEl.textContent =
                            'Šajā skatā nav punktu. Pietuvini (zoom ≥ 10) vai pagaidi — dati nāk no OpenStreetMap.';
                    } else {
                        updateHint();
                    }
                } catch (err) {
                    hintEl.textContent = 'Neizdevās ielādēt lokācijas. Pamēģini vēlreiz pēc brīža.';
                } finally {
                    fetchInFlight = false;
                }
            }

            // Filtra poga maina tipu un atkal ielādē punktus
            filterButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    filterButtons.forEach((b) => {
                        b.classList.remove('active');
                        b.classList.remove('bg-red-600', 'text-white');
                        b.classList.add('bg-zinc-800', 'text-zinc-100', 'ring-1', 'ring-white/10');
                    });

                    btn.classList.add('active');
                    btn.classList.remove('bg-zinc-800', 'text-zinc-100', 'ring-1', 'ring-white/10');
                    btn.classList.add('bg-red-600', 'text-white');

                    currentFilter = btn.dataset.type;
                    fetchLocations();
                });
            });

            // Meklēšana filtrē tikai jau ielādētos punktus lokāli
            searchInput.addEventListener('input', (e) => {
                searchQuery = e.target.value.toLowerCase();
                renderList();
            });

            map.on('moveend', scheduleFetch);
            map.on('zoomend', scheduleFetch);

            // Sākotnējais ielādes mēģinājums pēc skata
            fetchLocations();
        </script>
    </body>
</html>
