@section('css')
    <!-- Leaflet -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.7.1/dist/leaflet.js"></script>

    <!-- Leaflet.fullscreen plugin (must be AFTER leaflet.js) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.fullscreen/1.6.0/leaflet.fullscreen.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.fullscreen/1.6.0/Leaflet.fullscreen.min.js"></script>

    <style>
    /* ensure fullscreen control visible above other controls/legend */
        .leaflet-control-fullscreen {
            z-index: 10000 !important;
            background: rgba(255,255,255,0.95);
        }

        /* if plugin icon is missing, ensure some minimal padding/size */
        .leaflet-control-fullscreen a {
            width: 30px;
            height: 30px;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        /* optional: ensure map container can expand cleanly */
        #map { width:100%; height:700px; }

        /* Pin styles adapted from your example (valid CSS) */
        .pin-container { position: relative; width: 40px; height: 40px; display:inline-block; }

        .pin {
          width: 30px;
          height: 30px;
          border-radius: 50% 50% 50% 0;
          position: absolute;
          transform: rotate(-45deg);
          left: 50%;
          top: 50%;
          margin: -20px 0 0 -20px;
          animation-name: bounce;
          animation-fill-mode: both;
          animation-duration: 0.8s;
          display:flex;
          align-items:center;
          justify-content:center;
          font-size:13px;
          color: #fff;
          font-weight:600;
          box-shadow: 0 1px 4px rgba(0,0,0,0.25);
        }
        .pin:after {
          content: '';
          width: 14px;
          height: 14px;
          margin: 8px 0 0 8px;
          background: rgba(0,0,0,0.18);
          position: absolute;
          border-radius: 50%;
          z-index: 0;
        }

        .pin-green { background: #00C800; } /* green */
        .pin-blue  { background: #1370FF; } /* blue */
        .pin-red   { background: #FF3B30; } /* red */

        .pulse {
          background: rgba(0,0,0,0.08);
          border-radius: 50%;
          height: 14px;
          width: 14px;
          position: absolute;
          left: 50%;
          top: 50%;
          margin: 11px 0 0 -12px;
          transform: rotateX(55deg);
          z-index: -2;
        }
        .pulse:after {
          content: "";
          border-radius: 50%;
          height: 40px;
          width: 40px;
          position: absolute;
          margin: -13px 0 0 -13px;
          animation: pulsate 1s ease-out;
          animation-iteration-count: infinite;
          opacity: 0.0;
          box-shadow: 0 0 1px 2px rgba(137,132,155,0.25);
          animation-delay: 0s;
        }

        @keyframes pulsate {
          0%   { transform: scale(0.1, 0.1); opacity: 0.0; }
          50%  { opacity: 1.0; }
          100% { transform: scale(1.2, 1.2); opacity: 0; }
        }

        @keyframes bounce {
          0%   { opacity: 0; transform: translateY(-2000px) rotate(-45deg); }
          60%  { opacity: 1; transform: translateY(30px) rotate(-45deg); }
          80%  { transform: translateY(-10px) rotate(-45deg); }
          100% { transform: translateY(0) rotate(-45deg); }
        }

        /* Legend */
        .map-legend {
            background: rgba(255,255,255,0.95);
            padding: 8px 10px;
            border-radius: 6px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.3);
            font-family: Arial, sans-serif;
            font-size: 13px;
            line-height: 1.2;
        }
        .legend-item { display: flex; align-items: center; margin-bottom: 6px; }
        .legend-swatch {
            width: 18px; height: 18px; margin-right: 8px; border-radius: 50%;
            display:inline-block; border: 1px solid rgba(0,0,0,0.08);
        }

        .leaflet-control-fullscreen {
            background: white !important;
        }
        
        #map .map-overlay {
            position: absolute;
            inset: 0; /* top:0; right:0; bottom:0; left:0 */
            background: rgba(255,255,255,0.85);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10001; /* above controls/legend */
            pointer-events: all; /* block interactions while loading */
            transition: opacity 200ms ease;
            opacity: 1;
        }

        #map .map-overlay.hidden {
            opacity: 0;
            pointer-events: none;
            visibility: hidden;
        }

        /* spinner */
        .map-overlay .loader {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: Arial, sans-serif;
            color: #333;
            font-weight: 600;
        }

        .map-overlay .spinner {
            border: 4px solid rgba(0,0,0,0.08);
            border-top-color: rgba(0,0,0,0.6);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            animation: spin 0.9s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

            /* small screens */
        @media (max-width: 576px) {
            .map-legend { font-size: 12px; padding: 6px 8px; }
            .map-overlay .loader { font-size: 13px; }
            .map-overlay .spinner { width:28px; height:28px; border-width:3px; }
        }

    </style>
@endsection

<x-app-layout>
    <x-slot name="header">Service Map</x-slot>

    <div class="card mb-4">
       <div class="card-body" style="padding: 0px !important; ">
            <div class="row" style="padding:10px;">
                <div class="col-md-4">
                    <strong>Buyer with credit:</strong>
                    <span id="buyer-with-credit-count"></span>
                </div>
                <div class="col-md-4">
                    <strong>Buyer without credit:</strong>
                    <span id="buyer-without-credit-count"></span>
                </div>
                <div class="col-md-4">
                    <strong>Lead:</strong>
                    <span id="lead-count"></span>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div id="map" style="height:700px; position:relative;">
                        <!-- map tiles & controls live here -->
                        <div class="map-overlay hidden" id="map-overlay" aria-hidden="true" role="status" aria-live="polite">
                            <div class="loader">
                                <div class="spinner" aria-hidden="true"></div>
                                <div class="text"><span id="map-overlay-text">Loading map data…</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // init map (no fullscreen option here — we'll add the control programmatically)
        const map = L.map('map').setView([54.5, -3], 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Programmatically add the fullscreen control if plugin exists,
        // otherwise add a small fallback control that toggles browser fullscreen.
        (function addFullscreenControl() {
            try {
                const pluginExists = !!(window.L && L.Control && typeof L.Control.Fullscreen === 'function');

                if (pluginExists) {
                    // explicit add (so we control position)
                    const fs = new L.Control.Fullscreen({ position: 'bottomleft' });
                    map.addControl(fs);

                    // ensure map resizes after entering/exiting fullscreen
                    document.addEventListener('fullscreenchange', () => {
                        setTimeout(() => { try { map.invalidateSize(); } catch(e){} }, 200);
                    });

                    console.info('Leaflet.fullscreen plugin detected and control added.');
                    return;
                }

                console.warn('Leaflet.fullscreen plugin NOT detected — adding fallback fullscreen control.');

                // fallback control
                const FullscreenControl = L.Control.extend({
                    options: { position: 'bottomleft' },
                    onAdd: function () {
                        const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
                        const a = L.DomUtil.create('a', '', container);
                        a.href = '#';
                        a.title = 'Toggle fullscreen';
                        a.setAttribute('role','button');
                        a.innerHTML = '&#x26F6;'; // simple icon

                        L.DomEvent.on(a, 'mousedown', L.DomEvent.stopPropagation)
                            .on(a, 'click', L.DomEvent.stopPropagation)
                            .on(a, 'click', L.DomEvent.preventDefault)
                            .on(a, 'click', () => {
                                const elem = document.getElementById('map');
                                if (!document.fullscreenElement) {
                                    elem.requestFullscreen && elem.requestFullscreen();
                                } else {
                                    document.exitFullscreen && document.exitFullscreen();
                                }
                                // allow time for browser to enter/exit, then invalidate size
                                setTimeout(() => { try { map.invalidateSize(); } catch(e){} }, 300);
                            });

                        return container;
                    }
                });

                map.addControl(new FullscreenControl());

                // keep layout correct on fullscreen change
                document.addEventListener('fullscreenchange', () => {
                    setTimeout(() => { try { map.invalidateSize(); } catch(e){} }, 200);
                });

            } catch (err) {
                console.error('addFullscreenControl error', err);
            }
        })();

        // --- Legend, helpers and rest of your map code follow exactly as you had ---
        // legend
        const legend = L.control({ position: 'topright' });
        legend.onAdd = function () {
            const div = L.DomUtil.create('div', 'map-legend');
            div.innerHTML = `
                <div style="font-weight:600;margin-bottom:6px;">Map legend</div>
                <div class="legend-item"><span class="legend-swatch" style="background:#00C800"></span> Buyer with credit (green)</div>
                <div class="legend-item"><span class="legend-swatch" style="background:#FF3B30"></span> Buyer without credit (red)</div>
                <div class="legend-item"><span class="legend-swatch" style="background:#1370FF"></span> Lead (blue)</div>
            `;
            return div;
        };
        legend.addTo(map);

        // helpers
        function safeNum(v) {
            const n = parseFloat(v);
            return Number.isFinite(n) ? n : null;
        }
        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Keep created markers so we can clear
        const markers = [];
        function clearMarkers() {
            markers.forEach(m => {
                try { map.removeLayer(m); } catch(e){}
            });
            markers.length = 0;
        }

        let initialFit = false;

        function makeCountDivIcon(count, colorClass, offsetX = 0) {
            const html = `
                <div class="pin-container" style="transform: translateX(${offsetX}px);">
                    <div class="pin ${colorClass}" style="z-index:2;">
                        <span style="transform: rotate(45deg); display:inline-block; position:relative; z-index:3;">${count}</span>
                    </div>
                    <div class="pulse" style="z-index:1;"></div>
                </div>
            `;
            return L.divIcon({
                html: html,
                className: 'custom-count-icon',
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -38]
            });
        }

        // show/hide overlay
        function showMapLoading(message) {
            try {
                const ov = document.getElementById('map-overlay');
                if (!ov) return;
                const txt = document.getElementById('map-overlay-text');
                if (txt && message) txt.textContent = message;
                ov.classList.remove('hidden');
                ov.setAttribute('aria-hidden', 'false');
            } catch(e){ console.warn('showMapLoading error', e); }
        }
        function hideMapLoading() {
            try {
                const ov = document.getElementById('map-overlay');
                if (!ov) return;
                ov.classList.add('hidden');
                ov.setAttribute('aria-hidden', 'true');
            } catch(e){ console.warn('hideMapLoading error', e); }
        }


        // Main fetch + render (unchanged)
        async function refreshMap() {
            showMapLoading('Loading map data…'); // show overlay
            try {
                console.log('Refreshing map data...');
                const res = await fetch('{{ route("service-map.data") }}', { cache: 'no-store' });
                if (!res.ok) {
                    console.warn('Map data fetch failed:', res.status);
                    return;
                }
                const data = await res.json();

                clearMarkers();

                const agg = new Map();

                $('#buyer-with-credit-count').text((data.crediBuyers || []).length || '0');
                $('#buyer-without-credit-count').text((data.noCreditBuyers || []).length || '0');
                $('#lead-count').text((data.leads || []).length || '0');

                const combinedBuyers = [
                    ...(data.crediBuyers || []),
                    ...(data.noCreditBuyers || [])
                ];

                combinedBuyers.forEach(b => {
                    const lat = safeNum(b.latitude);
                    const lng = safeNum(b.longitude);
                    const postcode = (b.zipcode || b.postcode || '').trim();
                    if (lat === null || lng === null) return;
                    const key = `${postcode}|${lat.toFixed(6)}|${lng.toFixed(6)}`;
                    if (!agg.has(key)) agg.set(key, {
                        postcode,
                        lat,
                        lng,
                        buyers_with_credit: 0,
                        credit_profiles: '',
                        buyers_no_credit: 0,
                        no_credit_profiles: '',
                        leads: 0
                    });
                    const rec = agg.get(key);
                    const credit = Number(b.total_credit) || 0;
                    if (credit > 0) {
                        rec.buyers_with_credit += 1;
                        rec.credit_profiles += '<a href="'+b.profile_link+'" target="_blank">'+b.name+'</a>, &nbsp;&nbsp;';
                    } else {
                        rec.buyers_no_credit += 1;
                        rec.no_credit_profiles += '<a href="'+b.profile_link+'" target="_blank">'+b.name+'</a>, &nbsp;&nbsp;';
                    }
                });

                (data.leads || []).forEach(l => {
                    const lat = safeNum(l.latitude);
                    const lng = safeNum(l.longitude);
                    const postcode = (l.postcode || '').trim();
                    if (lat === null || lng === null) return;
                    const key = `${postcode}|${lat.toFixed(6)}|${lng.toFixed(6)}`;
                    if (!agg.has(key)) agg.set(key, {
                        postcode,
                        lat,
                        lng,
                        buyers_with_credit: 0,
                        credit_profiles: '',
                        buyers_no_credit: 0,
                        no_credit_profiles: '',
                        leads: 0
                    });
                    const rec = agg.get(key);
                    rec.leads += 1;
                });

                const bounds = L.latLngBounds();
                let addedAny = false;

                for (const [key, rec] of agg.entries()) {
                    const { lat, lng, postcode, buyers_with_credit, credit_profiles, buyers_no_credit, no_credit_profiles, leads } = rec;

                    if (buyers_with_credit > 0) {
                        const ico = makeCountDivIcon(buyers_with_credit, 'pin-green', -18);
                        const m = L.marker([lat, lng], { icon: ico })
                            .bindPopup(`
                                <div style="min-width:200px;">
                                    <strong>Postcode:</strong> ${escapeHtml(postcode || '')}<br>
                                    <strong>Buyers with credit:</strong> ${buyers_with_credit}<br>
                                    <strong>Profiles (credit):</strong> ${credit_profiles}<br>
                                    <strong>Buyers without credit:</strong> ${buyers_no_credit}<br>
                                    <strong>Profiles (without credit):</strong> ${no_credit_profiles}<br>
                                    <strong>Leads:</strong> ${leads}
                                </div>
                            `);
                        m.addTo(map);
                        markers.push(m);
                        try { bounds.extend(m.getLatLng()); addedAny = true; } catch(e){}
                    }

                    if (leads > 0) {
                        const ico = makeCountDivIcon(leads, 'pin-blue', 0);
                        const m = L.marker([lat, lng], { icon: ico })
                            .bindPopup(`
                                <div style="min-width:200px;">
                                    <strong>Postcode:</strong> ${escapeHtml(postcode || '')}<br>
                                    <strong>Buyers with credit:</strong> ${buyers_with_credit}<br>
                                    <strong>Profiles (credit):</strong> ${credit_profiles}<br>
                                    <strong>Buyers without credit:</strong> ${buyers_no_credit}<br>
                                    <strong>Profiles (without credit):</strong> ${no_credit_profiles}<br>
                                    <strong>Leads:</strong> ${leads}
                                </div>
                            `);
                        m.addTo(map);
                        markers.push(m);
                        try { bounds.extend(m.getLatLng()); addedAny = true; } catch(e){}
                    }

                    if (buyers_no_credit > 0) {
                        const ico = makeCountDivIcon(buyers_no_credit, 'pin-red', 18);
                        const m = L.marker([lat, lng], { icon: ico })
                            .bindPopup(`
                                <div style="min-width:200px;">
                                    <strong>Postcode:</strong> ${escapeHtml(postcode || '')}<br>
                                    <strong>Buyers with credit:</strong> ${buyers_with_credit}<br>
                                    <strong>Profiles (credit):</strong> ${credit_profiles}<br>
                                    <strong>Buyers without credit:</strong> ${buyers_no_credit}<br>
                                    <strong>Profiles (without credit):</strong> ${no_credit_profiles}<br>
                                    <strong>Leads:</strong> ${leads}
                                </div>
                            `);
                        m.addTo(map);
                        markers.push(m);
                        try { bounds.extend(m.getLatLng()); addedAny = true; } catch(e){}
                    }
                }

                if (!initialFit && addedAny && bounds.isValid()) {
                    map.fitBounds(bounds.pad(0.18));
                    initialFit = true;
                }

            } catch (err) {
                console.error('refreshMap error', err);
                showMapLoading('Error loading map data.');
            } finally {
                // hide overlay after a short delay so UI doesn't feel jumpy
                setTimeout(hideMapLoading, 300);
            }
        }

        // initial load
        refreshMap();

        // refresh interval (every 10s)
        // setInterval(refreshMap, 10000);
    });
</script>
