@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
@include('settings.partials.tabs')
{{-- Leaflet is only needed on this page, so it's loaded here rather than in
     layouts/app (keeps every other page untouched). Stylesheet <link> in body
     is valid HTML5; the script is synchronous so `L` exists before
     officeLocationPage() below runs. --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>

@php
    $locationsForJs = $locations->map(fn ($l) => [
        'id' => $l->id,
        'name' => $l->name,
        'latitude' => (float) $l->latitude,
        'longitude' => (float) $l->longitude,
        'radius_meters' => $l->radius_meters,
        'is_active' => $l->is_active,
    ])->values();
@endphp

<div x-data="officeLocationPage({
        locations: @js($locationsForJs),
        reopen: @js($errors->any()),
        mode: @js(old('_mode', 'create')),
        editId: @js(old('_location_id')),
        form: {
            name: @js(old('name', '')),
            latitude: @js(old('latitude', '')),
            longitude: @js(old('longitude', '')),
            radius_meters: @js(old('radius_meters', 150)),
        },
    })">

    <div class="flex items-start justify-between gap-4 mb-5">
        <div>
            <div class="text-base font-extrabold text-dash-ink">Lokasi Kantor</div>
            <div class="text-[13px] text-dash-muted mt-0.5">Titik dan radius yang dipakai untuk memvalidasi check-in intern. Check-in hanya diterima di dalam radius salah satu lokasi aktif.</div>
        </div>
        <button type="button" @click="openCreate()" class="flex-shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-lg border-0 bg-dash-navy text-white text-[13.5px] font-bold cursor-pointer hover:bg-dash-navy-dark">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Lokasi
        </button>
    </div>

    @if ($activeCount === 0)
        <div class="mb-4.5 flex items-start gap-3 px-4 py-3.5 rounded-xl bg-amber-50 border border-amber-300 text-amber-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0 mt-px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <div class="text-[13.5px] font-bold">Belum ada lokasi aktif. Semua absensi intern akan ditolak.</div>
        </div>
    @endif

    {{-- Overview map: every ACTIVE location as marker + radius circle.
         `isolate` gives the map its own stacking context so Leaflet's
         z-index 400-1000 panes/controls can't paint over the z-50 modal. --}}
    <div class="bg-white border border-dash-border rounded-xl p-4 mb-4.5">
        <div class="flex items-center justify-between mb-3">
            <div class="text-[14.5px] font-bold text-dash-ink">Peta Lokasi Aktif</div>
            <div class="text-xs text-dash-faint">{{ $activeCount }} lokasi aktif</div>
        </div>
        <div x-ref="overviewMap" class="isolate h-[340px] rounded-lg border border-dash-border-soft overflow-hidden"></div>
    </div>

    <div class="bg-white border border-dash-border rounded-xl overflow-hidden">
        <div class="grid grid-cols-[2fr_1.2fr_1.2fr_0.9fr_0.9fr_1.6fr] px-5 py-3.5 bg-dash-thead border-b border-dash-border text-[11.5px] font-bold text-dash-faint uppercase tracking-wide">
            <div>Nama</div><div>Latitude</div><div>Longitude</div><div>Radius</div><div>Status</div><div>Aksi</div>
        </div>
        @forelse ($locations as $location)
            @php
                $isLastActive = $location->is_active && $activeCount === 1;
                $deactivateConfirm = $isLastActive
                    ? "\"{$location->name}\" adalah lokasi aktif terakhir. Jika dinonaktifkan, tidak ada lokasi aktif lagi dan SEMUA absensi (check-in) intern akan ditolak. Tetap nonaktifkan?"
                    : "Nonaktifkan lokasi \"{$location->name}\"? Check-in di radius lokasi ini tidak akan diterima lagi.";
            @endphp
            <div class="grid grid-cols-[2fr_1.2fr_1.2fr_0.9fr_0.9fr_1.6fr] px-5 py-3.5 border-b border-dash-border-soft items-center {{ $location->is_active ? '' : 'bg-dash-thead' }}">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 {{ $location->is_active ? 'bg-dash-pill text-dash-navy' : 'bg-dash-bg text-dash-faint' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                    </div>
                    <span class="text-[13.5px] font-bold {{ $location->is_active ? 'text-dash-ink' : 'text-dash-muted' }}">{{ $location->name }}</span>
                </div>
                <div class="text-[13px] text-dash-slate tabular-nums">{{ $location->latitude }}</div>
                <div class="text-[13px] text-dash-slate tabular-nums">{{ $location->longitude }}</div>
                <div class="text-[13px] text-dash-slate">{{ $location->radius_meters }} m</div>
                <div>
                    @if ($location->is_active)
                        <span class="text-[11.5px] font-bold px-2.5 py-1 rounded-full text-green-700 bg-green-100">Aktif</span>
                    @else
                        <span class="text-[11.5px] font-bold px-2.5 py-1 rounded-full text-dash-muted bg-dash-bg">Nonaktif</span>
                    @endif
                </div>
                <div class="flex gap-2 flex-wrap">
                    <button type="button" @click="openEdit(@js($location->id))" class="border border-dash-border bg-white rounded-md px-3 py-1.5 text-xs font-bold text-dash-navy cursor-pointer">Edit</button>
                    @if ($location->is_active)
                        <form method="POST" action="{{ route('settings.office-locations.deactivate', $location) }}" onsubmit="return confirm(@js($deactivateConfirm));">
                            @csrf
                            <button type="submit" class="border border-red-200 bg-red-50 rounded-md px-3 py-1.5 text-xs font-bold text-red-700 cursor-pointer">Nonaktifkan</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('settings.office-locations.activate', $location) }}">
                            @csrf
                            <button type="submit" class="border border-green-200 bg-green-50 rounded-md px-3 py-1.5 text-xs font-bold text-green-700 cursor-pointer">Aktifkan</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            @include('partials.empty-state', [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />',
                'title' => 'Belum ada lokasi',
                'description' => 'Klik "Tambah Lokasi" untuk menambahkan lokasi kantor pertama.',
            ])
        @endforelse
    </div>

    {{-- Tambah / Edit modal --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 bg-[rgba(16,24,40,0.5)] flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-[640px] max-w-[94vw] max-h-[92vh] overflow-auto p-6.5">
            <div class="text-base font-extrabold text-dash-ink mb-4.5" x-text="mode === 'edit' ? 'Edit Lokasi Kantor' : 'Tambah Lokasi Kantor'"></div>
            <form method="POST" :action="formAction">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="mode !== 'edit'">
                <input type="hidden" name="_mode" :value="mode">
                <input type="hidden" name="_location_id" :value="editId">

                <div class="grid grid-cols-3 gap-3.5">
                    <div class="col-span-3">
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Nama Lokasi</label>
                        <input name="name" x-model="form.name" required placeholder="mis. Kantor Pusat Tangerang" class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Latitude</label>
                        <input type="number" step="any" min="-90" max="90" name="latitude" x-model="form.latitude" @input.debounce.600ms="refreshMap(false)" required placeholder="-6.1783000" class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Longitude</label>
                        <input type="number" step="any" min="-180" max="180" name="longitude" x-model="form.longitude" @input.debounce.600ms="refreshMap(false)" required placeholder="106.6319000" class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-bold text-dash-ink mb-1.5">Radius (meter)</label>
                        <input type="number" step="1" min="1" name="radius_meters" x-model="form.radius_meters" required class="w-full box-border px-3 py-2.5 border border-dash-border rounded-lg text-[13.5px] outline-none focus:border-dash-teal">
                    </div>
                </div>

                <div class="flex items-center gap-2 mt-3.5">
                    <button type="button" @click="refreshMap(true)" class="whitespace-nowrap px-3.5 py-2 rounded-lg border border-dash-border bg-white text-dash-navy text-[13px] font-bold cursor-pointer hover:bg-dash-bg">Perbarui Peta</button>
                    <button type="button" @click="useMyLocation()" :disabled="geoLoading" class="whitespace-nowrap flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-dash-border bg-white text-dash-slate text-[13px] font-bold cursor-pointer hover:bg-dash-bg disabled:opacity-60">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m0 13.5V21m9-9h-2.25M5.25 12H3m15 0a6 6 0 11-12 0 6 6 0 0112 0zm-3 0a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span x-text="geoLoading ? 'Mencari…' : 'Gunakan lokasi saya'"></span>
                    </button>
                </div>

                <div class="mt-2.5">
                    <div x-ref="pickerMap" class="isolate h-[280px] rounded-lg border border-dash-border overflow-hidden"></div>
                    <div class="text-xs text-dash-muted mt-1.5">Klik peta atau geser marker untuk mengisi koordinat. Lingkaran menunjukkan radius check-in.</div>
                    <div x-show="geoMessage" x-text="geoMessage" class="text-xs font-semibold text-red-600 mt-1"></div>
                </div>

                @if ($errors->any())
                    <div class="mt-3 px-3.5 py-2.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-[12.5px]">
                        <ul class="list-disc pl-4 m-0">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex gap-2.5 mt-5.5 justify-end">
                    <button type="button" @click="showModal = false" class="px-4.5 py-2.5 rounded-lg border border-dash-border bg-white text-dash-slate text-[13.5px] font-bold cursor-pointer">Batal</button>
                    <button type="submit" class="px-4.5 py-2.5 rounded-lg border-0 bg-dash-navy text-white text-[13.5px] font-bold cursor-pointer hover:bg-dash-navy-dark">Simpan Lokasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function officeLocationPage(config) {
    // Tangerang — fallback view when there's no coordinate to show yet.
    const DEFAULT_CENTER = [-6.1783, 106.6319];
    const TEAL = '#00a6c0';

    // Leaflet objects deliberately live in this closure, NOT in Alpine's
    // reactive state: Alpine wraps state in Proxies, which breaks Leaflet's
    // internal identity checks.
    let overviewMap = null;
    let pickerMap = null;
    let pickerMarker = null;
    let pickerCircle = null;

    const addTiles = (map) => L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    const round7 = (n) => Math.round(n * 1e7) / 1e7;

    return {
        locations: config.locations,
        showModal: config.reopen,
        mode: config.mode,
        editId: config.editId,
        form: config.form,
        geoMessage: '',
        geoLoading: false,

        get formAction() {
            return this.mode === 'edit'
                ? @js(url('/settings/office-locations')) + '/' + this.editId
                : @js(route('settings.office-locations.store'));
        },

        init() {
            this.initOverview();
            this.$watch('form.radius_meters', () => this.syncCircle());
            if (this.showModal) {
                this.$nextTick(() => this.initPicker());
            }
        },

        initOverview() {
            overviewMap = L.map(this.$refs.overviewMap, { scrollWheelZoom: false });
            addTiles(overviewMap);

            let bounds = null;
            this.locations.filter(l => l.is_active).forEach(l => {
                const popup = document.createElement('div');
                const title = document.createElement('b');
                title.textContent = l.name;
                popup.append(title, document.createElement('br'), 'Radius ' + l.radius_meters + ' m');

                L.marker([l.latitude, l.longitude]).addTo(overviewMap).bindPopup(popup);
                L.circle([l.latitude, l.longitude], {
                    radius: l.radius_meters, color: TEAL, fillColor: TEAL, fillOpacity: 0.15, weight: 2,
                }).addTo(overviewMap);

                // Not circle.getBounds(): that needs the map to already have a
                // view (it projects through map.layerPointToLatLng), and this
                // map gets its view from these very bounds.
                const circleBounds = L.latLng(l.latitude, l.longitude).toBounds(l.radius_meters * 2);
                bounds = bounds ? bounds.extend(circleBounds) : circleBounds;
            });

            if (bounds) {
                overviewMap.fitBounds(bounds, { padding: [30, 30], maxZoom: 17 });
            } else {
                overviewMap.setView(DEFAULT_CENTER, 12);
            }
        },

        openCreate() {
            this.mode = 'create';
            this.editId = null;
            this.form = { name: '', latitude: '', longitude: '', radius_meters: 150 };
            this.open();
        },

        openEdit(id) {
            const l = this.locations.find(x => x.id === id);
            if (!l) return;
            this.mode = 'edit';
            this.editId = id;
            this.form = { name: l.name, latitude: l.latitude, longitude: l.longitude, radius_meters: l.radius_meters };
            this.open();
        },

        open() {
            this.geoMessage = '';
            this.showModal = true;
            this.$nextTick(() => this.initPicker());
        },

        initPicker() {
            const el = this.$refs.pickerMap;
            if (!pickerMap) {
                pickerMap = L.map(el);
                addTiles(pickerMap);
                pickerMap.on('click', (e) => this.setCoords(e.latlng.lat, e.latlng.lng));

                // The container sits in an x-show modal, so it is display:none
                // (0x0) until opened. Whether $nextTick runs before or after
                // x-show reveals it isn't guaranteed, and a map measured at 0x0
                // renders one tile and misplaces the marker. Re-measure whenever
                // the container actually gets a size instead of guessing timing.
                new ResizeObserver(() => {
                    if (el.clientWidth > 0 && el.clientHeight > 0) {
                        pickerMap.invalidateSize();
                        this.centerPicker();
                    }
                }).observe(el);
            }

            if (el.clientWidth > 0) {
                pickerMap.invalidateSize();
                this.centerPicker();
            }
        },

        // Marker on the form's coordinates, or the default view when empty.
        centerPicker() {
            if (!this.refreshMap(false)) {
                this.removeMarker();
                pickerMap.setView(DEFAULT_CENTER, 13);
            }
        },

        validCoords() {
            const lat = parseFloat(this.form.latitude);
            const lng = parseFloat(this.form.longitude);
            if (Number.isNaN(lat) || Number.isNaN(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                return null;
            }
            return [lat, lng];
        },

        // Typed coordinates -> marker. Returns whether a marker was placed.
        refreshMap(fromButton) {
            if (!pickerMap) return false;
            const coords = this.validCoords();
            if (!coords) {
                if (fromButton) this.geoMessage = 'Latitude (-90 s/d 90) dan longitude (-180 s/d 180) harus diisi angka yang valid.';
                return false;
            }
            this.geoMessage = '';
            this.placeMarker(coords[0], coords[1]);
            pickerMap.setView(coords, Math.max(pickerMap.getZoom() || 0, 16));
            return true;
        },

        // Map click / marker drag / geolocation -> form fields.
        setCoords(lat, lng) {
            this.form.latitude = round7(lat);
            this.form.longitude = round7(lng);
            this.geoMessage = '';
            this.placeMarker(lat, lng);
        },

        placeMarker(lat, lng) {
            if (!pickerMarker) {
                pickerMarker = L.marker([lat, lng], { draggable: true }).addTo(pickerMap);
                pickerMarker.on('dragend', () => {
                    const p = pickerMarker.getLatLng();
                    this.setCoords(p.lat, p.lng);
                });
                pickerCircle = L.circle([lat, lng], {
                    radius: 1, color: TEAL, fillColor: TEAL, fillOpacity: 0.15, weight: 2, interactive: false,
                }).addTo(pickerMap);
            } else {
                pickerMarker.setLatLng([lat, lng]);
            }
            this.syncCircle();
        },

        removeMarker() {
            if (pickerMarker) { pickerMarker.remove(); pickerMarker = null; }
            if (pickerCircle) { pickerCircle.remove(); pickerCircle = null; }
        },

        syncCircle() {
            if (!pickerCircle || !pickerMarker) return;
            pickerCircle.setLatLng(pickerMarker.getLatLng());
            pickerCircle.setRadius(Math.max(1, parseInt(this.form.radius_meters, 10) || 1));
        },

        useMyLocation() {
            if (!navigator.geolocation) {
                this.geoMessage = 'Browser ini tidak mendukung geolokasi.';
                return;
            }
            this.geoLoading = true;
            this.geoMessage = '';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.geoLoading = false;
                    this.setCoords(pos.coords.latitude, pos.coords.longitude);
                    pickerMap.setView([pos.coords.latitude, pos.coords.longitude], 17);
                },
                (err) => {
                    this.geoLoading = false;
                    this.geoMessage = err.code === err.PERMISSION_DENIED
                        ? 'Izin lokasi ditolak browser. Isi koordinat manual atau klik peta.'
                        : 'Gagal mendapatkan lokasi: ' + err.message;
                },
                { enableHighAccuracy: true, timeout: 10000 },
            );
        },
    };
}
</script>
@endsection
