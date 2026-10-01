@php
    $market = $group->market;
    $bestItem = $group->best_item;
    $hasMultipleVarieties = $group->variety_count > 1;
    $distName = $market->district ? ($activeLocale === 'en' ? $market->district->name : ($market->district->name_kn ?? $market->district->name)) : 'Karnataka';
    $isBestPrice = ($rank === 1);
    $cleanMarketName = preg_replace('/\s+APMC$/i', '', $market->name);
    $cleanMarketNameKn = $market->name_kn ? preg_replace('/\s*(?:ಎಪಿಎಂಸಿ|APMC)$/ui', '', $market->name_kn) : $cleanMarketName;
    $displayMarketName = $activeLocale === 'en' ? $cleanMarketName : ($cleanMarketNameKn ?: $cleanMarketName);
@endphp
<div class="bg-white border-2 border-[#1C5A2C] rounded-3xl p-5 sm:p-6 transition flex flex-col justify-between relative group hover:shadow-xl shadow-md overflow-hidden">
    
    <!-- Top Hanging Ribbon / Bookmark Badge (Exact Match with Generated Mockup) -->
    @if($isBestPrice)
        <div class="absolute top-0 right-6 sm:right-8 z-10 text-center flex flex-col items-center justify-center px-4 py-2.5 rounded-b-xl shadow-md min-w-[76px] sm:min-w-[84px]"
             style="background: linear-gradient(180deg, #F59E0B 0%, #EAB308 45%, #D97706 100%); box-shadow: 0 4px 8px -1px rgba(217, 119, 6, 0.45);">
            <span class="text-base sm:text-lg font-black leading-none font-sans text-stone-950">#1</span>
            <span class="text-[9.5px] sm:text-[10px] font-black uppercase tracking-wider leading-tight mt-0.5 text-stone-950 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Best Price' : 'ಉತ್ತಮ ದರ' }}
            </span>
        </div>
    @else
        <div class="absolute top-0 right-6 sm:right-8 z-10 text-center flex flex-col items-center justify-center px-3.5 py-2.5 rounded-b-xl shadow-xs min-w-[50px] sm:min-w-[56px]"
             style="background: linear-gradient(180deg, #E2E8F0 0%, #CBD5E1 100%);">
            <span class="text-base sm:text-lg font-black leading-none font-sans text-stone-700">#{{ $rank }}</span>
        </div>
    @endif

    <div>
        <!-- Card Header: Mandi Name & Distance Subtitle -->
        <div class="pr-20 sm:pr-24">
            <h3 class="font-black text-stone-900 text-lg sm:text-xl font-sans tracking-tight leading-snug">
                @if($boardMeta)
                    <span>{{ $market->name }}</span>
                @else
                    <a href="{{ route('farmer.markets.show', $market->code) }}" class="hover:text-emerald-700 transition">
                        {{ $displayMarketName }} {{ $activeLocale === 'en' ? 'Mandi' : 'ಮಂಡಿ' }}
                    </a>
                @endif
            </h3>

            <div class="flex items-center gap-1.5 text-xs sm:text-[13px] text-stone-500 font-semibold mt-1 font-sans flex-wrap">
                @if(isset($market->distance_km) && $market->distance_km < 1000)
                    <span class="inline-flex items-center gap-1 text-stone-600">
                        📍 {{ round($market->distance_km) }} km {{ $activeLocale === 'en' ? 'away' : 'ದೂರ' }}
                    </span>
                    <span class="text-stone-300">•</span>
                @endif
                <span>{{ $distName }}</span>
                @if($isTopNearest ?? false)
                    <span class="text-stone-300">•</span>
                    <span class="text-emerald-700 font-bold">{{ $activeLocale === 'en' ? 'Nearest to you' : 'ನಿಮಗೆ ಹತ್ತಿರ' }}</span>
                @endif
            </div>
        </div>

        <!-- Clean Thin Divider -->
        <div class="border-b border-stone-100 my-3.5"></div>

        @if($hasMultipleVarieties)
            <!-- Multi-Variety: Commodity Variety Ledger (Exact Match with Generated Mockup) -->
            <div>
                <div class="text-xs sm:text-sm font-extrabold text-stone-800 font-sans mb-2 flex items-center justify-between">
                    <span>
                        {{ $activeLocale === 'en' ? 'Commodity Variety Ledger' : 'ತಳಿ ಮತ್ತು ದರ ಪಟ್ಟಿ' }} 
                        <span class="text-emerald-800 font-black">'{{ $crop->name }}'</span>
                    </span>
                    <span class="text-[11px] font-bold text-stone-400">
                        {{ $group->variety_count }} {{ $activeLocale === 'en' ? 'Varieties' : 'ತಳಿಗಳು' }}
                    </span>
                </div>

                <!-- Classic Green Ledger Table with All 4 Columns -->
                <div class="overflow-x-auto rounded-xl border border-stone-200/80 shadow-2xs">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-white text-[11px] sm:text-xs font-bold" style="background-color: #1C5A2C;">
                                <th class="py-2.5 px-3 font-extrabold text-left">{{ $activeLocale === 'en' ? 'Variety' : 'ತಳಿ' }}</th>
                                <th class="py-2.5 px-2 font-extrabold text-center">{{ $activeLocale === 'en' ? 'Current Price' : 'ಇಂದಿನ ದರ' }}</th>
                                <th class="py-2.5 px-2 font-extrabold text-center">{{ $activeLocale === 'en' ? 'Range (Min - Max)' : 'ಶ್ರೇಣಿ (ಕನಿಷ್ಠ-ಗರಿಷ್ಠ)' }}</th>
                                <th class="py-2.5 px-3 font-extrabold text-right">{{ $activeLocale === 'en' ? 'Arrivals' : 'ಆವಕ' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 text-xs sm:text-sm bg-white font-sans">
                            @foreach($group->varieties as $vIndex => $vItem)
                                <tr class="hover:bg-emerald-50/40 transition {{ $vIndex === 0 ? 'bg-emerald-50/25' : '' }}">
                                    <!-- Variety Name -->
                                    <td class="py-2.5 px-3 font-extrabold text-stone-900">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span>{{ $vItem->variety ? $vItem->variety->displayName($activeLocale) : $crop->name }}</span>
                                            @if($vIndex === 0)
                                                <span class="text-[9px] font-black px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 uppercase tracking-tight">Top</span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Current Modal Price -->
                                    <td class="py-2.5 px-2 text-center font-black text-stone-900 text-sm sm:text-base font-sans whitespace-nowrap">
                                        ₹{{ number_format($vItem->modal_price, 0) }}
                                        @if($crop->isCoffeeBoard())
                                            <span class="text-[9.5px] font-bold text-amber-900 block font-sans">≈ ₹{{ number_format($vItem->modal_price / 2, 0) }}/Bag</span>
                                        @endif
                                    </td>

                                    <!-- Range (Min - Max) - ALWAYS VISIBLE -->
                                    <td class="py-2.5 px-2 text-center text-xs font-semibold text-stone-600 font-sans whitespace-nowrap">
                                        @if($vItem->min_price && $vItem->max_price)
                                            ₹{{ number_format($vItem->min_price, 0) }} – ₹{{ number_format($vItem->max_price, 0) }}
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <!-- Arrivals -->
                                    <td class="py-2.5 px-3 text-right font-bold text-stone-700 text-xs font-sans whitespace-nowrap">
                                        @if($vItem->arrival_quantity)
                                            {{ number_format($vItem->arrival_quantity, 0) }} {{ $vItem->arrival_unit ?? 'Quintals' }}
                                        @else
                                            <span class="text-stone-300 font-normal">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- Single-Variety: High Impact Clean Price Layout (Exact Match with Generated Mockup) -->
            <div>
                <div class="text-xs sm:text-sm font-extrabold text-stone-700 font-sans">
                    {{ $activeLocale === 'en' ? 'Top Commodity Price' : 'ಪ್ರಮುಖ ಮಾರುಕಟ್ಟೆ ದರ' }} 
                    @if($bestItem->variety)
                        <span class="text-stone-900 font-black">({{ $bestItem->variety->displayName($activeLocale) }})</span>
                    @endif
                </div>

                <!-- Giant Bold Price -->
                <div class="text-4xl sm:text-5xl font-black text-stone-900 tracking-tight font-sans my-3.5 flex items-baseline gap-2">
                    <span>₹{{ number_format($bestItem->modal_price, 0) }}</span>
                    <span class="text-xs sm:text-sm font-semibold text-stone-400 font-sans">
                        / {{ $bestItem->unit ?? 'Quintal' }}
                    </span>
                    @if($crop->isCoffeeBoard())
                        <span class="text-xs font-bold text-amber-900 bg-amber-100 px-2 py-0.5 rounded-md font-sans ml-1">
                            ≈ ₹{{ number_format($bestItem->modal_price / 2, 0) }}/50kg Bag
                        </span>
                    @endif
                </div>

                <!-- Amber/Gold Rounded Pill Chips (Guaranteed Vibrant Fill Colors from Mockup) -->
                <div class="flex items-center gap-2 sm:gap-2.5 flex-wrap">
                    <span class="inline-flex items-center justify-center px-3.5 sm:px-4 py-1.5 rounded-full font-bold text-xs sm:text-[13px] shadow-2xs whitespace-nowrap leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}"
                          style="background-color: #FEF3C7; color: #92400E; border: 1.5px solid #FCD34D;">
                        <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'Min:' : 'ಕನಿಷ್ಠ:' }} ₹{{ number_format($bestItem->min_price, 0) }}</span>
                    </span>

                    <span class="inline-flex items-center justify-center px-3.5 sm:px-4 py-1.5 rounded-full font-bold text-xs sm:text-[13px] shadow-2xs whitespace-nowrap leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}"
                          style="background-color: #FEF3C7; color: #92400E; border: 1.5px solid #FCD34D;">
                        <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'Max:' : 'ಗರಿಷ್ಠ:' }} ₹{{ number_format($bestItem->max_price, 0) }}</span>
                    </span>

                    @if($bestItem->arrival_quantity)
                        <span class="inline-flex items-center justify-center px-3.5 sm:px-4 py-1.5 rounded-full font-bold text-xs sm:text-[13px] shadow-2xs whitespace-nowrap leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}"
                              style="background-color: #FEF3C7; color: #92400E; border: 1.5px solid #FCD34D;">
                            <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'Arrivals:' : 'ಆವಕ:' }} {{ number_format($bestItem->arrival_quantity, 0) }} {{ $bestItem->arrival_unit ?? 'Quintals' }}</span>
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Card Footer: Minimalist Attribution & WhatsApp Share -->
    <div class="mt-4 pt-2.5 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500 font-sans">
        <div class="truncate pr-2">
            @if($group->total_arrivals > 0)
                <span>{{ $hasMultipleVarieties ? ($activeLocale === 'en' ? 'Total Arrivals: ' : 'ಒಟ್ಟು ಆವಕ: ') : ($activeLocale === 'en' ? 'Arrivals: ' : 'ಆವಕ: ') }}<strong class="text-stone-700 font-bold">{{ number_format($group->total_arrivals, 1) }}</strong> {{ $group->arrival_unit }}</span>
            @else
                <span>{{ $boardMeta ? ($activeLocale === 'en' ? 'Source: ' . ($group->dataSource ? $group->dataSource->name : $boardMeta['badge_en']) : 'ದರ ಮೂಲ: ' . ($group->dataSource ? $group->dataSource->name : $boardMeta['badge_en'])) : ($activeLocale === 'en' ? 'Mandi Feed: ' . ($group->dataSource ? $group->dataSource->name : 'Daily Mandi') : 'ಮಂಡಿ ಫೀಡ್: ' . ($group->dataSource ? $group->dataSource->name : 'ದೈನಂದಿನ ಮಂಡಿ')) }}</span>
            @endif
        </div>

        <!-- WhatsApp Share for this Mandi -->
        @php
            $cropNameDisplay = ($activeLocale === 'kn' && !empty($crop->name_kn)) ? $crop->name_kn : $crop->name;
            $mandiShare = ($activeLocale === 'en'
                ? "🌾 *Krushi Baandhava — Today's {$crop->name} Rates*\n"
                    . "🏛️ @" . $displayMarketName . " (" . $distName . "):\n"
                : "🌾 *ಕೃಷಿ ಬಾಂಧವ — ಇಂದಿನ {$cropNameDisplay} ದರಗಳು*\n"
                    . "🏛️ @" . $displayMarketName . " (" . $distName . "):\n");

            foreach($group->varieties as $v) {
                $vName = $v->variety ? $v->variety->displayName($activeLocale) : $cropNameDisplay;
                $mandiShare .= "• " . $vName . ": ₹" . number_format($v->modal_price, 0) . " / " . $v->unit;
                if ($v->min_price && $v->max_price) {
                    if ($activeLocale === 'en') {
                        $mandiShare .= " (Min: ₹" . number_format($v->min_price, 0) . " | Max: ₹" . number_format($v->max_price, 0) . ")";
                    } else {
                        $mandiShare .= " (ಕನಿಷ್ಠ: ₹" . number_format($v->min_price, 0) . " | ಗರಿಷ್ಠ: ₹" . number_format($v->max_price, 0) . ")";
                    }
                }
                $mandiShare .= "\n";
            }

            $mandiShare .= ($activeLocale === 'en' ? "📅 Date: " : "📅 ದಿನಾಂಕ: ") . \Carbon\Carbon::parse($group->price_date)->format('d M Y') . "\n";
            $mandiShare .= ($activeLocale === 'en' ? "👉 View full details: " : "👉 ಸಂಪೂರ್ಣ ವಿವರಗಳಿಗೆ: ") . url()->current();
        @endphp
        <a href="https://wa.me/?text={{ rawurlencode($mandiShare) }}" 
           target="_blank" 
           rel="noopener noreferrer" 
           class="text-xs font-bold text-emerald-800 hover:text-emerald-950 flex items-center gap-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} shrink-0">
            <span>💬</span>
            <span>{{ $activeLocale === 'en' ? 'Share' : 'ಶೇರ್ ಮಾಡಿ' }}</span>
        </a>
    </div>
</div>
