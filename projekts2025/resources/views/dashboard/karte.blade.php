<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Karte</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />

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
            box-shadow: 0 12px 35px rgba(0,0,0,0.45);
        }
        .leaflet-popup-tip {
            background: rgba(24, 24, 27, 0.95);
        }
        .leaflet-control-zoom a {
            background: rgba(24,24,27,0.9);
            color: #f4f4f5;
            border: 1px solid rgba(255,255,255,0.12);
        }
        .leaflet-control-zoom a:hover {
            background: rgba(39,39,42,0.9);
        }
    </style>
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
                <h1 class="text-2xl font-bold tracking-wide uppercase">Karte</h1>
                <p class="mt-1 text-sm text-zinc-400">
                    Lokāciju pārskats (uzpildes stacijas, servisi un EV uzlāde).
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}"
                   class="rounded-lg bg-zinc-800 px-5 py-2.5 text-base font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700">
                    Atpakaļ uz paneli
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
            <section class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 rounded-2xl bg-zinc-900/50 p-4 ring-1 ring-white/10">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="text-base font-semibold">Kartes skats</div>
                        <div class="text-sm text-zinc-400" id="hint">
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl ring-1 ring-white/10">
                        <div id="map"></div>
                    </div>
                </div>

                <aside class="rounded-2xl bg-zinc-900/50 p-5 ring-1 ring-white/10">
                    <div class="mb-4">
                        <div class="text-base font-semibold">Meklēšana</div>
                        <p class="mt-1 text-sm text-zinc-400">
                            Filtrē lokācijas pēc nosaukuma un tipa.
                        </p>
                    </div>

                    <input
                        type="text"
                        id="search"
                        placeholder="Meklēt lokācijas..."
                        class="w-full rounded-lg bg-zinc-950/60 px-4 py-2.5 text-base text-zinc-100 ring-1 ring-white/10 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/40"
                    />

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button class="filter-btn active rounded-full bg-red-600 px-3 py-2 text-sm font-semibold text-white"
                                data-type="all">
                            Visi
                        </button>

                        <button class="filter-btn rounded-full bg-zinc-800 px-3 py-2 text-sm font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                                data-type="gas_station">
                            Uzpildes stacijas
                        </button>

                        <button class="filter-btn rounded-full bg-zinc-800 px-3 py-2 text-sm font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                                data-type="service_center">
                            Servisi
                        </button>

                       
                        <button class="filter-btn rounded-full bg-zinc-800 px-3 py-2 text-sm font-semibold text-zinc-100 ring-1 ring-white/10 hover:bg-zinc-700"
                                data-type="ev_charging">
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
        const map = L.map('map').setView([56.946, 24.105], 7);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        const clusterGroup = L.markerClusterGroup();
        map.addLayer(clusterGroup);

        let allMarkers = [];
        let currentFilter = 'all';
        let searchQuery = '';

        const listEl = document.getElementById('location-list');
        const searchInput = document.getElementById('search');
        const filterButtons = document.querySelectorAll('.filter-btn');
        const hintEl = document.getElementById('hint');

        function typeLabel(type) {
            if (type === 'gas_station') return 'uzpildes stacija';
            if (type === 'service_center') return 'autoserviss';
            if (type === 'ev_charging') return 'EV uzlāde';
            return (type ?? '').replace('_', ' ');
        }

        function markerColor(type) {
            if (type === 'gas_station') return '#ef4444';  
            if (type === 'service_center') return '#22c55e';  
            if (type === 'ev_charging') return '#f97316';     
            return '#3b82f6';
        }

        function bboxString() {
            const b = map.getBounds();
            return [
                b.getSouth().toFixed(6),
                b.getWest().toFixed(6),
                b.getNorth().toFixed(6),
                b.getEast().toFixed(6),
            ].join(',');
        }

        function updateHint() {
            const zoom = map.getZoom();
            if (zoom < 10) {
                hintEl.textContent = 'Padoms: pietuvini karti (DUS/Servisi no zoom ≥ 10, EV no zoom ≥ 11).';
            } else if (zoom < 11) {
                hintEl.textContent = 'DUS un servisi ielādējas. EV uzlādei nepieciešams zoom ≥ 11.';
            } else {
                hintEl.textContent = 'Vari pārvietot karti — lokācijas ielādēsies tikai redzamajā apgabalā.';
            }
        }

        function renderList() {
            listEl.innerHTML = '';

            const filtered = allMarkers.filter(({ name, type, address }) => {
                const okType = (currentFilter === 'all' || type === currentFilter);
                const q = searchQuery;
                const okSearch =
                    (name || '').toLowerCase().includes(q) ||
                    (address || '').toLowerCase().includes(q);

                return okType && okSearch;
            });

            if (filtered.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'rounded-xl bg-zinc-950/40 p-4 text-sm text-zinc-400 ring-1 ring-white/10';
                empty.textContent = 'Nav atrastu lokāciju pēc izvēlētā filtra.';
                listEl.appendChild(empty);
                return;
            }

            filtered.forEach(({ marker, name, type, address }) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className =
                    'w-full text-left rounded-xl bg-zinc-950/40 p-4 ring-1 ring-white/10 transition hover:bg-zinc-900 hover:ring-white/20';

                item.innerHTML = `
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-base font-semibold text-zinc-100">${name}</div>
                            <div class="mt-1 text-sm text-zinc-400">${address ?? ''}</div>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-white/10"
                              style="background: rgba(24,24,27,0.6); color: #e4e4e7;">
                            ${typeLabel(type)}
                        </span>
                    </div>
                `;

                item.addEventListener('click', () => {
                    map.setView(marker.getLatLng(), Math.max(map.getZoom(), 13));
                    marker.openPopup();
                });

                listEl.appendChild(item);
            });
        }

        let fetchTimer = null;
        function scheduleFetch() {
            if (fetchTimer) clearTimeout(fetchTimer);
            fetchTimer = setTimeout(fetchLocations, 350);
        }

        function shouldFetchAtZoom(type, zoom) {
            if (type === 'ev_charging') return zoom >= 11;
            if (type === 'gas_station') return zoom >= 10;
            if (type === 'service_center') return zoom >= 10;
            if (type === 'all') return zoom >= 10;
            return zoom >= 10;
        }

        async function fetchLocations() {
            updateHint();

            const zoom = map.getZoom();
            if (!shouldFetchAtZoom(currentFilter, zoom)) {
                allMarkers = [];
                clusterGroup.clearLayers();
                renderList();
                return;
            }

            const bbox = bboxString();
            const url = `/api/locations?bbox=${encodeURIComponent(bbox)}&zoom=${zoom}&type=${encodeURIComponent(currentFilter)}`;

            try {
                const res = await fetch(url);
                const locations = await res.json();

                allMarkers = [];
                clusterGroup.clearLayers();

                locations.forEach(location => {
                    const lat = parseFloat(location.latitude);
                    const lon = parseFloat(location.longitude);
                    if (Number.isNaN(lat) || Number.isNaN(lon)) return;

                    const color = markerColor(location.type);

                    const marker = L.circleMarker([lat, lon], {
                        radius: 8,
                        color: color,
                        fillColor: color,
                        fillOpacity: 0.85,
                        weight: 2,
                    });

                    marker.bindPopup(`
                        <div style="min-width: 220px">
                            <div style="font-weight:700; font-size: 14px; margin-bottom: 6px;">${location.name ?? ''}</div>
                            <div style="color:#a1a1aa; font-size: 12px;">${location.address ?? ''}</div>
                            <div style="margin-top:8px; font-size: 12px; color:#e4e4e7;">
                                Tips: ${typeLabel(location.type)}
                            </div>
                        </div>
                    `);

                    marker.on('mouseover', () => marker.openPopup());
                    marker.on('mouseout', () => marker.closePopup());

                    clusterGroup.addLayer(marker);

                    allMarkers.push({
                        marker,
                        name: location.name ?? '',
                        type: location.type ?? '',
                        address: location.address ?? ''
                    });
                });

                renderList();
            } catch (err) {
                console.error(err);
            }
        }

        filterButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                filterButtons.forEach(b => {
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

        searchInput.addEventListener('input', e => {
            searchQuery = e.target.value.toLowerCase();
            renderList();
        });

        map.on('moveend', scheduleFetch);
        map.on('zoomend', scheduleFetch);

        fetchLocations();
    </script>
</body>
</html>
