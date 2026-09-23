@extends('layouts.admin')

@section('title', 'Daily Market Prices')

@section('content')
<div class="space-y-6" x-data="{ syncModalOpen: false, selectedSource: '', targetDate: '{{ date('Y-m-d') }}' }">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-emerald-950 flex items-center gap-2">
                <span>💰</span> Daily Market Prices
                <span class="text-xs font-semibold px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full">ದೈನಂದಿನ ಬೆಲೆಗಳು</span>
            </h1>
            <p class="text-sm text-stone-500 font-medium">Canonicalized, deduplicated mandi price records from data.gov.in, Agmarknet, and commodity boards.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.sync-logs.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-bold text-stone-700 bg-white border border-stone-200 rounded-xl hover:bg-stone-50 transition shadow-xs">
                <span>📜</span> Sync Logs
            </a>
            <button @click="syncModalOpen = true" type="button" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold text-white bg-emerald-700 rounded-xl hover:bg-emerald-800 transition shadow-xs cursor-pointer">
                <span>⚡</span> Sync Prices Now
            </button>
        </div>
    </div>

    <!-- Metrics Summary Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-stone-200/80 shadow-xs">
            <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Total Canonical Records</span>
            <div class="text-2xl font-black text-stone-900 mt-1">{{ number_format($totalPrices) }}</div>
            <div class="text-xs font-semibold text-emerald-700 mt-1">Deduplicated at rest</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-stone-200/80 shadow-xs">
            <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Latest Date Records</span>
            <div class="text-2xl font-black text-emerald-700 mt-1">{{ number_format($latestCount) }}</div>
            <div class="text-xs font-semibold text-stone-500 mt-1">Date: {{ $latestDate }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-stone-200/80 shadow-xs">
            <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Active Mandis</span>
            <div class="text-2xl font-black text-blue-700 mt-1">{{ $activeMarketsCount }}</div>
            <div class="text-xs font-semibold text-stone-500 mt-1">Reporting on latest date</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-stone-200/80 shadow-xs">
            <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Avg Price Spread</span>
            <div class="text-2xl font-black text-amber-700 mt-1">₹{{ number_format($avgSpread, 0) }}</div>
            <div class="text-xs font-semibold text-stone-500 mt-1">Max - Min difference</div>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="bg-white p-4 rounded-2xl border border-stone-200/80 shadow-xs">
        <form method="GET" action="{{ route('admin.prices.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <!-- Search Text -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-500 mb-1">Search Crop / Mandi</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="e.g. Arecanut, Shimoga..."
                    class="w-full text-xs font-medium px-3 py-2 bg-stone-50 border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition">
            </div>

            <!-- Crop Filter -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-500 mb-1">Crop</label>
                <select name="crop_id" class="w-full text-xs font-medium px-2.5 py-2 bg-stone-50 border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition">
                    <option value="">All Crops</option>
                    @foreach($crops as $c)
                        <option value="{{ $c->id }}" {{ $cropId == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->name_kn }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- District Filter -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-500 mb-1">District</label>
                <select name="district_id" class="w-full text-xs font-medium px-2.5 py-2 bg-stone-50 border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition">
                    <option value="">All Districts</option>
                    @foreach($districts as $d)
                        <option value="{{ $d->id }}" {{ $districtId == $d->id ? 'selected' : '' }}>
                            {{ $d->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date Filter -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-500 mb-1">Date</label>
                <input type="date" name="date" value="{{ $date }}"
                    class="w-full text-xs font-medium px-2.5 py-2 bg-stone-50 border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition">
            </div>

            <!-- Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-3 py-2 text-xs font-bold text-white bg-emerald-700 rounded-xl hover:bg-emerald-800 transition">
                    Filter
                </button>
                @if($search || $cropId || $marketId || $districtId || $dataSourceId || $date)
                    <a href="{{ route('admin.prices.index') }}" class="px-2.5 py-2 text-xs font-bold text-stone-500 bg-stone-100 rounded-xl hover:bg-stone-200 transition" title="Clear Filters">
                        ✕
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Market Prices Table Card -->
    <div class="bg-white border border-stone-200/80 rounded-2xl overflow-hidden shadow-xs">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
            <h2 class="font-bold text-stone-900 flex items-center gap-2">
                <span>📊</span> Canonical Prices ({{ $prices->total() }})
            </h2>
            <div class="text-xs font-semibold text-stone-400">Unit: ₹ / Quintal</div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-stone-600">
                <thead class="bg-stone-50/80 text-stone-500 font-bold uppercase text-[11px] tracking-wider border-b border-stone-100">
                    <tr>
                        <th class="px-5 py-3.5">Date</th>
                        <th class="px-5 py-3.5">Crop & Variety</th>
                        <th class="px-5 py-3.5">APMC Mandi & District</th>
                        <th class="px-5 py-3.5 text-right">Modal Price</th>
                        <th class="px-5 py-3.5 text-right">Min - Max Range</th>
                        <th class="px-5 py-3.5 text-right">Arrivals</th>
                        <th class="px-5 py-3.5">Source Feed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse($prices as $price)
                        <tr class="hover:bg-emerald-50/30 transition">
                            <!-- Date -->
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="font-semibold text-stone-900">{{ $price->price_date->format('d M Y') }}</span>
                                <span class="block text-[11px] text-stone-400">{{ $price->price_date->format('D') }}</span>
                            </td>

                            <!-- Crop & Variety -->
                            <td class="px-5 py-3.5">
                                <div class="font-bold text-stone-900 flex items-center gap-1.5">
                                    <span>{{ $price->crop->name }}</span>
                                    <span class="text-[11px] font-normal text-emerald-800 bg-emerald-50 px-1.5 py-0.5 rounded">{{ $price->crop->name_kn }}</span>
                                </div>
                                <div class="text-xs text-stone-500 mt-0.5">
                                    {{ $price->variety ? $price->variety->name : 'All Varieties' }}
                                </div>
                            </td>

                            <!-- APMC Mandi & District -->
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-stone-900">
                                    {{ $price->market->name }} APMC
                                </div>
                                <div class="text-xs text-stone-400">
                                    {{ $price->market->district ? $price->market->district->name : 'Karnataka' }}
                                </div>
                            </td>

                            <!-- Modal Price -->
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <span class="text-base font-black text-emerald-800">
                                    ₹{{ number_format($price->modal_price, 0) }}
                                </span>
                                <span class="block text-[10px] text-stone-400 font-medium">/ {{ $price->unit }}</span>
                            </td>

                            <!-- Min - Max Range -->
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <div class="font-medium text-stone-700 text-xs">
                                    @if($price->min_price && $price->max_price)
                                        ₹{{ number_format($price->min_price, 0) }} - ₹{{ number_format($price->max_price, 0) }}
                                    @else
                                        <span class="text-stone-400">—</span>
                                    @endif
                                </div>
                                @if($price->price_spread > 0)
                                    <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded">
                                        Δ ₹{{ number_format($price->price_spread, 0) }}
                                    </span>
                                @endif
                            </td>

                            <!-- Arrivals -->
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                @if($price->arrival_quantity)
                                    <span class="font-semibold text-stone-800 text-xs">{{ number_format($price->arrival_quantity, 1) }}</span>
                                    <span class="text-[10px] text-stone-400 block">{{ $price->arrival_unit ?? 'Quintal' }}</span>
                                @else
                                    <span class="text-xs text-stone-400">—</span>
                                @endif
                            </td>

                            <!-- Source Feed Badge -->
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-stone-100 text-stone-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    {{ $price->dataSource ? $price->dataSource->name : 'System Feed' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-stone-400">
                                <div class="text-3xl mb-2">🌾</div>
                                <div class="text-base font-bold text-stone-700">No market price records found</div>
                                <p class="text-xs text-stone-500 mt-1">Try changing filters or run an on-demand sync from external feeds.</p>
                                <button @click="syncModalOpen = true" type="button" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-emerald-700 rounded-xl hover:bg-emerald-800 transition">
                                    <span>⚡</span> Run Ingestion Sync
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($prices->hasPages())
            <div class="px-5 py-4 border-t border-stone-100">
                {{ $prices->links() }}
            </div>
        @endif
    </div>

    <!-- On-Demand Sync Modal -->
    <div x-show="syncModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-stone-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="syncModalOpen = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-stone-100">
            <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                <h3 class="text-base font-bold text-stone-900 flex items-center gap-2">
                    <span>⚡</span> Trigger Price Ingestion
                </h3>
                <button @click="syncModalOpen = false" class="text-stone-400 hover:text-stone-600 text-lg font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('admin.prices.sync') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-stone-600 mb-1">Target Feed</label>
                    <select name="data_source_id" x-model="selectedSource" class="w-full text-sm px-3 py-2 bg-stone-50 border border-stone-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="">All Active Sources (Batch)</option>
                        @foreach($dataSources as $ds)
                            <option value="{{ $ds->id }}">{{ $ds->name }} ({{ $ds->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-stone-600 mb-1">Target Date</label>
                    <input type="date" name="target_date" x-model="targetDate" class="w-full text-sm px-3 py-2 bg-stone-50 border border-stone-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <div class="bg-emerald-50/70 p-3 rounded-xl text-xs text-emerald-900 font-medium leading-relaxed">
                    💡 Ingestion pipeline applies SHA-256 deduplication, resolves crop & mandi aliases, enforces sanity validation, and records an execution audit log.
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="syncModalOpen = false" class="px-4 py-2 text-xs font-bold text-stone-600 bg-stone-100 rounded-xl hover:bg-stone-200 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-emerald-700 rounded-xl hover:bg-emerald-800 transition shadow-xs">
                        Start Sync
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
