@php
    $market = $group->market;
    $bestItem = $group->best_item;
    $hasMultipleVarieties = $group->variety_count > 1;
    $distName = $market->district ? ($activeLocale === 'en' ? $market->district->name : ($market->district->name_kn ?? $market->district->name)) : 'Karnataka';
    $isBestPrice = ($rank === 1);
    $cleanMarketName = preg_replace('/\s+APMC$/i', '', $market->name);
    $cleanMarketNameKn = $market->name_kn ? preg_replace('/\s*(?:ಎಪಿಎಂಸಿ|APMC)$/ui', '', $market->name_kn) : $cleanMarketName;
    $displayMarketName = $activeLocale === 'en' ? $cleanMarketName : ($cleanMarketNameKn ?: $cleanMarketName);

    // WhatsApp Share precomputation
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

<div class="relative group rounded-2xl p-4 sm:p-4.5 transition flex flex-col justify-between overflow-hidden {{ $isBestPrice ? 'bg-gradient-to-br from-[#FAF8F5] via-white to-[#F2F7F3] border-2 border-[#1C5A2C] shadow-sm hover:shadow-md' : 'bg-gradient-to-br from-[#FAF8F5] via-white to-[#F7F5F0] border-2 border-[#DDD2BE] hover:border-[#1C5A2C]/60 shadow-2xs hover:shadow-sm' }}">
    <!-- Top Decorative Border Accent -->
    <div class="absolute top-0 left-0 right-0 h-0.5 {{ $isBestPrice ? 'bg-gradient-to-r from-amber-400 via-emerald-600 to-emerald-800' : 'bg-gradient-to-r from-stone-200 via-[#DDD2BE] to-stone-200' }}"></div>

    <div>
        <!-- Compact Header: Left Market Info, Right Rank Badge -->
        <div class="flex items-start justify-between gap-2.5">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-md {{ $isBestPrice ? 'bg-emerald-100 text-[#1C5A2C]' : 'bg-[#EFEAE1] text-stone-700' }} text-[11px] shrink-0 font-sans shadow-2xs">
                        🏛️
                    </span>
                    <h3 class="font-black text-stone-900 text-base sm:text-lg font-sans tracking-tight leading-snug truncate">
                        @if($boardMeta)
                            <span>{{ $market->name }}</span>
                        @else
                            <a href="{{ route('farmer.markets.show', $market->code) }}" class="hover:text-emerald-700 transition">
                                {{ $displayMarketName }} {{ $activeLocale === 'en' ? 'Mandi' : 'ಮಂಡಿ' }}
                            </a>
                        @endif
                    </h3>
                </div>

                <div class="flex items-center gap-1.5 text-[11px] sm:text-xs text-stone-600 font-semibold mt-1 font-sans flex-wrap">
                    @if(isset($market->distance_km) && $market->distance_km < 1000)
                        <span class="inline-flex items-center gap-0.5 text-stone-600">
                            📍 {{ round($market->distance_km) }} km
                        </span>
                        <span class="text-stone-300">•</span>
                    @endif
                    <span>{{ $distName }}</span>
                    @if($isTopNearest ?? false)
                        <span class="text-stone-300">•</span>
                        <span class="text-emerald-700 font-bold">{{ $activeLocale === 'en' ? 'Nearest' : 'ಹತ್ತಿರ' }}</span>
                    @endif
                </div>
            </div>

            <!-- Compact Rank Ribbon / Badge -->
            @if($isBestPrice)
                <div class="shrink-0 flex items-center gap-1 px-2.5 py-1 rounded-xl shadow-xs border border-amber-400/60"
                     style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                    <span class="text-xs">👑</span>
                    <span class="text-xs sm:text-sm font-black text-stone-950 font-sans leading-none">#1</span>
                    <span class="text-[9px] font-black uppercase text-stone-950 leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'Best Price' : 'ಉತ್ತಮ ದರ' }}
                    </span>
                </div>
            @else
                <div class="shrink-0 flex items-center gap-1 px-2 py-0.5 rounded-lg bg-stone-100 border border-stone-200 text-stone-700">
                    <span class="text-xs font-black font-sans leading-none">#{{ $rank }}</span>
                </div>
            @endif
        </div>

        @if($hasMultipleVarieties)
            <!-- Multi-Variety: Compact Commodity Variety Ledger -->
            <div class="mt-2.5">
                <div class="text-[11px] font-extrabold text-stone-700 font-sans mb-1.5 flex items-center justify-between">
                    <span class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#1C5A2C]"></span>
                        <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Varieties Ledger' : 'ತಳಿ ಮತ್ತು ದರ ಪಟ್ಟಿ' }}</span>
                    </span>
                    <span class="text-[10px] font-bold text-stone-400">
                        {{ $group->variety_count }} {{ $activeLocale === 'en' ? 'Varieties' : 'ತಳಿಗಳು' }}
                    </span>
                </div>

                <div class="overflow-x-auto rounded-xl border border-[#DDD2BE] bg-white">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="text-white text-[10.5px] font-bold" style="background-color: #1C5A2C;">
                                <th class="py-1.5 px-2.5 text-left {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Variety' : 'ತಳಿ' }}</th>
                                <th class="py-1.5 px-2 text-center {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Price' : 'ದರ' }}</th>
                                <th class="py-1.5 px-2 text-center {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Min - Max' : 'ಕನಿಷ್ಠ-ಗರಿಷ್ಠ' }}</th>
                                <th class="py-1.5 px-2.5 text-right {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Arrivals' : 'ಆವಕ' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#EFEAE1] font-sans text-xs">
                            @foreach($group->varieties as $vIndex => $vItem)
                                <tr class="transition {{ $vIndex === 0 ? 'bg-emerald-50/40 font-semibold' : 'even:bg-[#FAF8F5]/50' }}">
                                    <td class="py-1.5 px-2.5 font-bold text-stone-900 truncate max-w-[120px]">
                                        {{ $vItem->variety ? $vItem->variety->displayName($activeLocale) : $crop->name }}
                                    </td>
                                    <td class="py-1.5 px-2 text-center font-black text-[#1C5A2C] whitespace-nowrap">
                                        ₹{{ number_format($vItem->modal_price, 0) }}
                                    </td>
                                    <td class="py-1.5 px-2 text-center text-[11px] text-stone-600 whitespace-nowrap">
                                        @if($vItem->min_price && $vItem->max_price)
                                            ₹{{ number_format($vItem->min_price, 0) }}–₹{{ number_format($vItem->max_price, 0) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="py-1.5 px-2.5 text-right font-bold text-stone-700 text-[11px] whitespace-nowrap">
                                        @if($vItem->arrival_quantity)
                                            {{ number_format($vItem->arrival_quantity, 0) }} <span class="text-[9.5px] text-stone-400">{{ $vItem->arrival_unit ?? 'Q' }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- Single-Variety: Super Clean, Beautiful & Compact Rate Bar -->
            <div class="mt-3 pt-2.5 border-t border-[#E8DFC8]/70">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <!-- Price Callout -->
                    <div>
                        <div class="text-[10.5px] font-bold text-stone-500 uppercase tracking-wide {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'Modal Price' : 'ಇಂದಿನ ಸರಾಸರಿ ದರ' }}
                            @if($bestItem->variety)
                                <span class="text-stone-800 font-extrabold">({{ $bestItem->variety->displayName($activeLocale) }})</span>
                            @endif
                        </div>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <span class="text-2xl sm:text-3xl font-black text-[#1C5A2C] tracking-tight font-sans leading-none">
                                ₹{{ number_format($bestItem->modal_price, 0) }}
                            </span>
                            <span class="text-[11px] sm:text-xs font-semibold text-stone-500 font-sans">
                                / {{ $bestItem->unit ?? 'Quintal' }}
                            </span>
                            @if($crop->isCoffeeBoard())
                                <span class="text-[10px] font-bold text-amber-900 bg-amber-100 border border-amber-200 px-1.5 py-0.5 rounded font-sans ml-1">
                                    ≈ ₹{{ number_format($bestItem->modal_price / 2, 0) }}/Bag
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Compact Harmonic Stat Pills: Min, Max, Arrivals -->
                    <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                        <!-- Min -->
                        <div class="px-2.5 py-1 rounded-lg bg-stone-100/90 border border-stone-200 text-center">
                            <span class="text-[9.5px] font-bold text-stone-500 block uppercase leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Min' : 'ಕನಿಷ್ಠ' }}</span>
                            <span class="text-xs font-black text-stone-800 font-sans leading-none mt-0.5 block">₹{{ number_format($bestItem->min_price, 0) }}</span>
                        </div>

                        <!-- Max -->
                        <div class="px-2.5 py-1 rounded-lg bg-emerald-50/90 border border-emerald-200 text-center">
                            <span class="text-[9.5px] font-bold text-emerald-700 block uppercase leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Max' : 'ಗರಿಷ್ಠ' }}</span>
                            <span class="text-xs font-black text-emerald-900 font-sans leading-none mt-0.5 block">₹{{ number_format($bestItem->max_price, 0) }}</span>
                        </div>

                        <!-- Arrivals -->
                        @if($bestItem->arrival_quantity)
                            <div class="px-2.5 py-1 rounded-lg bg-amber-50/90 border border-amber-200 text-center">
                                <span class="text-[9.5px] font-bold text-amber-800 block uppercase leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Arrivals' : 'ಆವಕ' }}</span>
                                <span class="text-xs font-black text-amber-950 font-sans leading-none mt-0.5 block">{{ number_format($bestItem->arrival_quantity, 0) }} <span class="text-[9px] font-normal text-amber-700">{{ $bestItem->arrival_unit ?? 'Qtl' }}</span></span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Compact Footer: Attribution + Tactile Action Buttons -->
    <div class="mt-2.5 pt-2 border-t border-[#E8DFC8]/70 flex items-center justify-between text-[11px] text-stone-500 font-sans gap-2 flex-wrap">
        <div class="truncate text-[10.5px] sm:text-[11px]">
            @if($group->total_arrivals > 0)
                <span class="inline-flex items-center gap-1">
                    <span>📦</span>
                    <span>{{ $activeLocale === 'en' ? 'Arrivals: ' : 'ಆವಕ: ' }}<strong class="text-stone-800 font-bold">{{ number_format($group->total_arrivals, 1) }}</strong> {{ $group->arrival_unit }}</span>
                </span>
            @else
                <span class="inline-flex items-center gap-1 truncate">
                    <span>🌾</span>
                    <span class="truncate">{{ $boardMeta ? ($activeLocale === 'en' ? 'Source: ' . ($group->dataSource ? $group->dataSource->name : $boardMeta['badge_en']) : 'ದರ ಮೂಲ: ' . ($group->dataSource ? $group->dataSource->name : $boardMeta['badge_en'])) : ($activeLocale === 'en' ? 'Mandi Feed: ' . ($group->dataSource ? $group->dataSource->name : 'Daily Mandi') : 'ಮಂಡಿ ಫೀಡ್: ' . ($group->dataSource ? $group->dataSource->name : 'ದೈನಂದಿನ ಮಂಡಿ')) }}</span>
                </span>
            @endif
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
            @if(!$boardMeta)
                <a href="{{ route('farmer.markets.show', $market->code) }}" 
                   class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-[#FAF8F5] hover:bg-stone-100 text-stone-700 border border-[#DDD2BE] font-bold text-[11px] transition active:scale-95 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span>🗺️</span>
                    <span>{{ $activeLocale === 'en' ? 'Mandi' : 'ಮಂಡಿ' }}</span>
                </a>
            @endif

            <a href="https://wa.me/?text={{ rawurlencode($mandiShare) }}" 
               target="_blank" 
               rel="noopener noreferrer" 
               class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#E8F8EE] hover:bg-[#D5F2DF] text-[#128C7E] border border-[#A6E4BA] font-bold text-[11px] transition active:scale-95 shadow-2xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span>💬</span>
                <span>{{ $activeLocale === 'en' ? 'Share' : 'ಶೇರ್' }}</span>
            </a>
        </div>
    </div>
</div>
