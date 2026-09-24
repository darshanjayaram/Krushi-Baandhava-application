@props([
    'allDistricts' => null,
    'activeDistrict' => null,
])

<div x-data="locationModalHandler()"
     x-show="isOpen"
     x-cloak
     @open-location-modal.window="openModal()"
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="modal-title" role="dialog" aria-modal="true"
     style="display: none;">

    <!-- Backdrop -->
    <div x-show="isOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeModal()"
         class="fixed inset-0 bg-stone-900/60 backdrop-blur-sm transition-opacity"></div>

    <div class="flex min-h-full items-end sm:items-center justify-center p-3 sm:p-4 text-center">
        <!-- Modal Card -->
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
             @click.away="closeModal()"
             class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all w-full max-w-lg border border-stone-200">
            
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-[#1C5A2C] to-[#2E7D32] px-5 py-4 text-white flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center text-lg shadow-inner">
                        📍
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-white tracking-tight" id="modal-title">
                            ಸ್ಥಳ ಆಯ್ಕೆಮಾಡಿ (Select Location)
                        </h3>
                        <p class="text-xs text-emerald-100/90 font-medium">ನಿಮ್ಮ ಜಿಲ್ಲೆಯ ಅಧಿಕೃತ APMC ದರಗಳನ್ನು ವೀಕ್ಷಿಸಿ</p>
                    </div>
                </div>
                <button type="button" 
                        @click="closeModal()"
                        class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition active:scale-90 text-sm font-bold cursor-pointer">
                    ✕
                </button>
            </div>

            <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto">
                <!-- 1. GPS Auto-Detect Button Card -->
                <div class="bg-emerald-50/70 border-2 border-dashed border-emerald-300/80 rounded-2xl p-4 transition hover:bg-emerald-50">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#1C5A2C] text-white flex items-center justify-center shrink-0 shadow-sm text-lg">
                            <span :class="isDetecting ? 'animate-spin' : ''">🛰️</span>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-extrabold text-stone-900 text-sm">ಸ್ವಯಂಚಾಲಿತ ಜಿಪಿಎಸ್ ಪತ್ತೆ (Auto-Detect GPS)</h4>
                            <p class="text-xs text-stone-600 mt-0.5">ನಿಮ್ಮ ಪ್ರಸ್ತುತ ಸ್ಥಳವನ್ನು ಪತ್ತೆಹಚ್ಚಿ ಹತ್ತಿರದ ಜಿಲ್ಲೆಯನ್ನು ಹೊಂದಿಸಿ.</p>

                            <!-- Detecting Status Indicator -->
                            <div x-show="isDetecting" class="mt-2.5 flex items-center gap-2 text-xs font-bold text-emerald-800">
                                <svg class="animate-spin h-4 w-4 text-[#1C5A2C]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="detectingMessage">ಸ್ಥಳ ಪತ್ತೆಹಚ್ಚಲಾಗುತ್ತಿದೆ...</span>
                            </div>

                            <!-- Error Message Notice -->
                            <div x-show="gpsError" class="mt-2.5 p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-medium space-y-1">
                                <div class="font-bold flex items-center gap-1">
                                    <span>⚠️</span>
                                    <span x-text="gpsError"></span>
                                </div>
                                <div class="text-[11px] text-amber-800">ದಯವಿಟ್ಟು ಕೆಳಗಿನ ಪಟ್ಟಿಯಿಂದ ನಿಮ್ಮ ಜಿಲ್ಲೆಯನ್ನು ನೇರವಾಗಿ ಆಯ್ಕೆಮಾಡಿ.</div>
                            </div>

                            <button type="button" 
                                    @click="detectGPSLocation()"
                                    :disabled="isDetecting"
                                    class="mt-3 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#1C5A2C] hover:bg-[#154622] text-white text-xs font-bold shadow-sm transition active:scale-95 cursor-pointer disabled:opacity-50">
                                <span>📍 ಪ್ರಸ್ತುತ ಸ್ಥಳ ಪತ್ತೆಹಚ್ಚಿ</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Separator -->
                <div class="relative flex py-1 items-center">
                    <div class="flex-grow border-t border-stone-200"></div>
                    <span class="flex-shrink mx-3 text-stone-400 text-xs font-bold uppercase tracking-wider">ಅಥವಾ ಜಿಲ್ಲೆ ಆರಿಸಿ (Or Select District)</span>
                    <div class="flex-grow border-t border-stone-200"></div>
                </div>

                <!-- 2. Search Box -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text"
                           x-model="searchFilter"
                           placeholder="ಜಿಲ್ಲೆ ಹುಡುಕಿ (Search district, e.g. Shivamogga, Hassan...)"
                           class="w-full text-xs pl-9 pr-4 py-2.5 bg-stone-50 border border-stone-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1C5A2C] transition">
                </div>

                <!-- 3. Districts Grid -->
                <div class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto pr-1">
                    <template x-for="dist in filteredDistricts" :key="dist.id">
                        <button type="button"
                                @click="selectDistrict(dist)"
                                class="p-3 rounded-xl border text-left text-xs font-bold transition flex items-center justify-between group cursor-pointer"
                                :class="activeDistrictId == dist.id ? 'bg-[#1C5A2C]/10 border-[#1C5A2C] text-[#1C5A2C]' : 'border-stone-200 hover:border-emerald-600 hover:bg-stone-50 text-stone-700'">
                            <div>
                                <span class="font-kannada text-xs font-bold block" x-text="dist.name_kn || dist.name"></span>
                                <span class="text-[10px] font-medium text-stone-500 block" x-text="dist.name"></span>
                            </div>
                            <span x-show="activeDistrictId == dist.id" class="w-2.5 h-2.5 rounded-full bg-[#1C5A2C] shrink-0"></span>
                        </button>
                    </template>
                </div>

                <div x-show="filteredDistricts.length === 0" class="text-center py-4 text-xs text-stone-400 font-medium">
                    ಯಾವುದೇ ಜಿಲ್ಲೆ ಕಂಡುಬಂದಿಲ್ಲ (No districts found)
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-stone-50 px-5 py-3 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500">
                <span>ಕರ್ನಾಟಕದ ಎಲ್ಲಾ 31 ಜಿಲ್ಲೆಗಳು ಲಭ್ಯವಿದೆ</span>
                <button type="button" @click="closeModal()" class="font-bold text-stone-700 hover:text-stone-900">
                    ಮುಚ್ಚಿ (Close)
                </button>
            </div>
        </div>
    </div>
</div>

@php
    $districtsList = (isset($allDistricts) && $allDistricts && $allDistricts->isNotEmpty())
        ? $allDistricts
        : \Illuminate\Support\Facades\Cache::remember('karnataka_districts_list', 3600, function () {
            return \App\Models\District::whereHas('state', fn ($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'name_kn', 'latitude', 'longitude', 'code']);
        });

    if (!$districtsList instanceof \Illuminate\Support\Collection) {
        $districtsList = collect($districtsList);
    }

    $districtsPayload = $districtsList->map(function ($d) {
        return [
            'id' => is_array($d) ? $d['id'] : $d->id,
            'name' => is_array($d) ? $d['name'] : $d->name,
            'name_kn' => is_array($d) ? ($d['name_kn'] ?? $d['name']) : ($d->name_kn ?? $d->name),
            'lat' => (float) (is_array($d) ? ($d['latitude'] ?? 0) : ($d->latitude ?? 0)),
            'lon' => (float) (is_array($d) ? ($d['longitude'] ?? 0) : ($d->longitude ?? 0)),
        ];
    })->values();

    $currentActiveId = null;
    if (isset($activeDistrict) && $activeDistrict) {
        $currentActiveId = is_array($activeDistrict) ? $activeDistrict['id'] : $activeDistrict->id;
    } else {
        $cookieOrSession = request()->query('district') ?? request()->cookie('selected_district_id') ?? session('selected_district_id');
        $currentActiveId = $cookieOrSession ? (int) $cookieOrSession : ($districtsPayload->first()['id'] ?? 1);
    }
@endphp

<script>
function locationModalHandler() {
    return {
        isOpen: false,
        isDetecting: false,
        detectingMessage: 'ಸ್ಥಳ ಪತ್ತೆಹಚ್ಚಲಾಗುತ್ತಿದೆ...',
        gpsError: null,
        searchFilter: '',
        activeDistrictId: {{ $currentActiveId ?? 'null' }},
        districts: {!! json_encode($districtsPayload) !!},

        get filteredDistricts() {
            if (!this.searchFilter.trim()) {
                return this.districts;
            }
            const q = this.searchFilter.toLowerCase().trim();
            return this.districts.filter(d => 
                (d.name && d.name.toLowerCase().includes(q)) || 
                (d.name_kn && d.name_kn.toLowerCase().includes(q))
            );
        },

        openModal() {
            this.isOpen = true;
            this.gpsError = null;
        },

        closeModal() {
            this.isOpen = false;
        },

        selectDistrict(dist) {
            this.saveAndRedirect(dist.id, dist.name);
        },

        detectGPSLocation() {
            this.isDetecting = true;
            this.gpsError = null;
            this.detectingMessage = 'ಬ್ರೌಸರ್ ಜಿಪಿಎಸ್ ಸಂಪರ್ಕಿಸಲಾಗುತ್ತಿದೆ...';

            if (!navigator.geolocation) {
                this.fallbackToIP();
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.matchNearestDistrict(position.coords.latitude, position.coords.longitude);
                },
                (error) => {
                    console.warn('GPS failed or timed out, trying IP geolocation fallback...', error);
                    this.fallbackToIP();
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        },

        fallbackToIP() {
            this.detectingMessage = 'ಇಂಟರ್ನೆಟ್ ಮೂಲಕ ಸ್ಥಳ ಪತ್ತೆಹಚ್ಚಲಾಗುತ್ತಿದೆ...';
            fetch('https://ipapi.co/json/')
                .then(res => res.json())
                .then(data => {
                    if (data && data.latitude && data.longitude) {
                        this.matchNearestDistrict(parseFloat(data.latitude), parseFloat(data.longitude));
                    } else {
                        throw new Error('No IP coordinates');
                    }
                })
                .catch(() => {
                    fetch('https://freeipapi.com/api/json')
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.latitude && data.longitude) {
                                this.matchNearestDistrict(parseFloat(data.latitude), parseFloat(data.longitude));
                            } else {
                                throw new Error('No IP coordinates');
                            }
                        })
                        .catch(() => {
                            this.isDetecting = false;
                            this.gpsError = 'ಸ್ವಯಂಚಾಲಿತ ಸ್ಥಳ ಪತ್ತೆಹಚ್ಚಲು ಸಾಧ್ಯವಾಗಲಿಲ್ಲ. ದಯವಿಟ್ಟು ಕೆಳಗಿನ ಪಟ್ಟಿಯಿಂದ ನಿಮ್ಮ ಜಿಲ್ಲೆಯನ್ನು ನೇರವಾಗಿ ಆಯ್ಕೆಮಾಡಿ.';
                        });
                });
        },

        matchNearestDistrict(userLat, userLon) {
            this.detectingMessage = 'ಹತ್ತಿರದ ಕರ್ನಾಟಕದ ಮಂಡಿ ಜಿಲ್ಲೆ ಹೊಂದಿಸಲಾಗುತ್ತಿದೆ...';
            let nearest = null;
            let minDistanceKm = Infinity;

            for (const d of this.districts) {
                if (d.lat && d.lon) {
                    const distKm = this.getHaversineKm(userLat, userLon, d.lat, d.lon);
                    if (distKm < minDistanceKm) {
                        minDistanceKm = distKm;
                        nearest = d;
                    }
                }
            }

            if (nearest) {
                this.saveAndRedirect(nearest.id, nearest.name, userLat, userLon);
            } else {
                const fallback = this.districts[0] || { id: 1, name: 'Shivamogga' };
                this.saveAndRedirect(fallback.id, fallback.name);
            }
        },

        getHaversineKm(lat1, lon1, lat2, lon2) {
            const R = 6371; // Earth radius in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon / 2) * Math.sin(dLon / 2);
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        },

        saveAndRedirect(districtId, districtName, lat = null, lon = null) {
            // Persist in cookie (1 year)
            document.cookie = "selected_district_id=" + districtId + "; path=/; max-age=31536000; SameSite=Lax";
            if (window.localStorage) {
                localStorage.setItem('krushi_district_id', districtId);
                localStorage.setItem('krushi_district_name', districtName);
            }

            const payload = { district_id: districtId };
            if (lat && lon) {
                payload.latitude = lat;
                payload.longitude = lon;
            }

            // Post to backend to persist in session
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            fetch('{{ route('set-location') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfMeta ? csrfMeta.getAttribute('content') : ''
                },
                body: JSON.stringify(payload)
            }).catch(() => {
                // Ignore failure, cookie is already saved
            }).finally(() => {
                // Navigate or reload
                const url = new URL(window.location.href);
                url.searchParams.set('district', districtId);
                window.location.href = url.toString();
            });
        }
    };
}
</script>
