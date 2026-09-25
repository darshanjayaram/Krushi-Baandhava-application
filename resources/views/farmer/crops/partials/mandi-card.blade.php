@php
    $market = $group->market;
    $bestItem = $group->best_item;
    $hasMultipleVarieties = $group->variety_count > 1;
    $distName = $market->district ? ($activeLocale === 'en' ? $market->district->name : ($market->district->name_kn ?? $market->district->name)) : 'Karnataka';
    $isBestPrice = ($rank === 1);
@endphp
<div class="bg-white border {{ $isBestPrice ? 'border-emerald-600 ring-2 ring-emerald-500/20' : 'border-[#E8DFC8]' }} rounded-2xl p-4 sm:p-5 shadow-2xs hover:shadow-md transition flex flex-col justify-between relative group">
    <div>
        <!-- Top Bar: Rank Badge + Mandi / Centre Name + Best Price badge -->
        <div class="flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full {{ $isBestPrice ? 'bg-amber-400 text-stone-950 font-black' : 'bg-stone-100 text-stone-600 font-bold' }} flex items-center justify-center text-xs shrink-0 font-sans shadow-2xs">
                        #{{ $rank }}
                    </span>
                    <h3 class="font-black text-stone-900 text-base sm:text-lg font-sans truncate">
                        @if($boardMeta)
                            <span>{{ $market->name }}</span>
                        @else
                            <a href="{{ route('farmer.markets.show', $market->code) }}" class="hover:text-emerald-700 transition">
                                {{ str_ends_with(strtolower($market->name), 'apmc') ? $market->name : $market->name . ' APMC' }}
                            </a>
                        @endif
                    </h3>
                </div>
                <div class="flex items-center gap-2 text-xs text-stone-500 mt-1 pl-8 font-sans flex-wrap">
                    <span>📍 {{ $distName }}</span>
                    @if(isset($market->distance_km) && $market->distance_km < 1000)
                        <span>•</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full {{ ($isNearestCard ?? false) ? 'bg-emerald-100 text-emerald-900 font-bold' : 'bg-stone-100 text-stone-600 font-medium' }} text-[11px]">
                            🚗 {{ round($market->distance_km) }} km{{ ($isTopNearest ?? false) ? ($activeLocale === 'en' ? ' • Nearest Mandi' : ' • ಹತ್ತಿರದ ಮಂಡಿ') : '' }}
                        </span>
                    @endif
                    @if($hasMultipleVarieties)
                        <span>•</span>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200/80 font-bold text-[11px] font-sans">
                            {{ $group->variety_count }} {{ $activeLocale === 'en' ? 'Varieties Traded' : 'ತಳಿಗಳು ಲಭ್ಯ' }}
                        </span>
                    @elseif($bestItem->variety)
                        <span>•</span>
                        <span class="font-semibold text-emerald-800">{{ $bestItem->variety->displayName($activeLocale) }}</span>
                    @endif
                </div>
            </div>

            @if($boardMeta)
                <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full {{ $boardMeta['theme'] === 'coffee' ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-emerald-100 text-emerald-900 border-emerald-300' }} border font-sans shrink-0">
                    {{ $boardMeta['icon'] }} {{ $boardMeta['badge_en'] }}
                </span>
            @elseif($isBestPrice)
                <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-900 border border-emerald-300 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} shrink-0">
                    {{ $activeLocale === 'en' ? 'Best Price' : 'ಉತ್ತಮ ದರ' }}
                </span>
            @endif
        </div>

        @if(!$hasMultipleVarieties)
            <!-- Single Variety Clean Box -->
            <div class="mt-3.5 p-3.5 rounded-xl bg-stone-50 border border-stone-200/70">
                <div class="flex items-center justify-between">
                    <div class="text-[10px] uppercase font-bold text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'Modal Price' : 'ಮಾದರಿ ದರ' }}
                    </div>
                    @if($bestItem->variety)
                        <span class="text-xs font-bold text-emerald-800 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} bg-white px-2 py-0.5 rounded-md border border-stone-200/60 shadow-2xs">
                            {{ $bestItem->variety->displayName($activeLocale) }}
                        </span>
                    @endif
                </div>
                <div class="text-2xl sm:text-3xl font-black text-emerald-950 mt-1 tracking-tight flex flex-wrap items-baseline gap-1.5 font-sans">
                    <span>₹{{ number_format($bestItem->modal_price, 0) }}</span>
                    <span class="text-xs font-semibold text-stone-400 font-sans">/ {{ $bestItem->unit }}</span>
                    @if($crop->isCoffeeBoard())
                        <span class="text-xs font-bold text-amber-900 bg-amber-100 px-2 py-0.5 rounded-md font-sans">
                            ≈ ₹{{ number_format($bestItem->modal_price / 2, 0) }}/50kg Bag
                        </span>
                    @endif
                </div>

                <div class="mt-2.5 pt-2 border-t border-stone-200/70 flex items-center justify-between text-xs text-stone-600 font-sans">
                    <div>
                        <span class="text-stone-400 text-[10px] block {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Min' : 'ಕನಿಷ್ಠ' }}</span>
                        <span class="font-bold">{{ $bestItem->min_price ? '₹' . number_format($bestItem->min_price, 0) : '—' }}</span>
                    </div>
                    @if($bestItem->arrival_quantity)
                        <div class="text-center">
                            <span class="text-stone-400 text-[10px] block {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Arrivals' : 'ಆವಕ' }}</span>
                            <span class="font-semibold text-stone-700">{{ number_format($bestItem->arrival_quantity, 1) }} {{ $bestItem->arrival_unit ?? 'Qtl' }}</span>
                        </div>
                    @endif
                    <div class="text-right">
                        <span class="text-stone-400 text-[10px] block {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Max' : 'ಗರಿಷ್ಠ' }}</span>
                        <span class="font-bold">{{ $bestItem->max_price ? '₹' . number_format($bestItem->max_price, 0) : '—' }}</span>
                    </div>
                </div>
            </div>
        @else
            <!-- Multi-Variety Modern Clean Box -->
            <div class="mt-3.5 space-y-2">
                <div class="flex items-center justify-between text-[11px] font-bold text-stone-500 px-1 font-sans">
                    <span>{{ $activeLocale === 'en' ? 'Varieties Traded in this Mandi' : 'ಈ ಮಂಡಿಯಲ್ಲಿ ಲಭ್ಯವಿರುವ ತಳಿಗಳು & ದರಗಳು' }}</span>
                    <span class="text-emerald-800">
                        {{ $activeLocale === 'en' ? 'Best:' : 'ಅತ್ಯಧಿಕ:' }} ₹{{ number_format($group->best_modal, 0) }}/{{ $group->unit }}
                    </span>
                </div>

                <div class="space-y-2">
                    @foreach($group->varieties as $vIndex => $vItem)
                        <div class="p-3 rounded-xl {{ $vIndex === 0 ? 'bg-emerald-50/70 border border-emerald-300/80 shadow-2xs' : 'bg-stone-50 border border-stone-200/80' }} transition hover:bg-white hover:shadow-xs">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-black text-stone-900 text-sm font-sans">
                                            {{ $vItem->variety ? $vItem->variety->displayName($activeLocale) : $crop->name }}
                                        </span>
                                        @if($vIndex === 0)
                                            <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-emerald-200 text-emerald-950 font-sans">
                                                {{ $activeLocale === 'en' ? 'Top Rate' : 'ಗರಿಷ್ಠ ದರ' }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-stone-500 mt-1 font-sans">
                                        {{ $activeLocale === 'en' ? 'Range:' : 'ವ್ಯಾಪ್ತಿ:' }} 
                                        <strong class="text-stone-700">{{ $vItem->min_price ? '₹' . number_format($vItem->min_price, 0) : '—' }}</strong> - 
                                        <strong class="text-stone-700">{{ $vItem->max_price ? '₹' . number_format($vItem->max_price, 0) : '—' }}</strong>
                                        @if($vItem->arrival_quantity)
                                            <span class="text-stone-400 ml-1.5">• {{ $activeLocale === 'en' ? 'Arrivals:' : 'ಆವಕ:' }} {{ number_format($vItem->arrival_quantity, 1) }} {{ $vItem->arrival_unit ?? 'Qtl' }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <div class="text-base sm:text-lg font-black text-emerald-950 font-sans leading-tight">
                                        ₹{{ number_format($vItem->modal_price, 0) }}
                                    </div>
                                    <span class="text-[10px] font-medium text-stone-400 font-sans">/ {{ $vItem->unit }}</span>
                                    @if($crop->isCoffeeBoard())
                                        <div class="text-[10px] font-bold text-amber-900">
                                            ≈ ₹{{ number_format($vItem->modal_price / 2, 0) }}/Bag
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Card Footer -->
    <div class="mt-3.5 pt-2.5 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500 font-sans">
        <div>
            @if($group->total_arrivals > 0)
                <span>{{ $hasMultipleVarieties ? ($activeLocale === 'en' ? 'Total Arrivals: ' : 'ಒಟ್ಟು ಆವಕ: ') : ($activeLocale === 'en' ? 'Arrivals: ' : 'ಆವಕ: ') }}<strong>{{ number_format($group->total_arrivals, 1) }}</strong> {{ $group->arrival_unit }}</span>
            @else
                <span>{{ $boardMeta ? ($activeLocale === 'en' ? 'Source: ' . ($group->dataSource ? $group->dataSource->name : $boardMeta['badge_en']) : 'ದರ ಮೂಲ: ' . ($group->dataSource ? $group->dataSource->name : $boardMeta['badge_en'])) : ($activeLocale === 'en' ? 'Mandi Feed: ' . ($group->dataSource ? $group->dataSource->name : 'APMC') : 'ಮಂಡಿ ಫೀಡ್: ' . ($group->dataSource ? $group->dataSource->name : 'APMC')) }}</span>
            @endif
        </div>

        <!-- WhatsApp Share for this Mandi -->
        @php
            $cropNameDisplay = ($activeLocale === 'kn' && !empty($crop->name_kn)) ? $crop->name_kn : $crop->name;
            $mandiShare = ($activeLocale === 'en'
                ? "🌾 *Krushi Baandhava — Today's {$crop->name} Rates*\n"
                    . "🏛️ @" . $market->name . ($boardMeta ? '' : (str_ends_with(strtolower($market->name), 'apmc') ? '' : ' APMC')) . " (" . $distName . "):\n"
                : "🌾 *ಕೃಷಿ ಬಾಂಧವ — ಇಂದಿನ {$cropNameDisplay} ದರಗಳು*\n"
                    . "🏛️ @" . $market->name . ($boardMeta ? '' : (str_ends_with(strtolower($market->name), 'apmc') ? '' : ' APMC')) . " (" . $distName . "):\n");

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

            $mandiShare .= ($activeLocale === 'en' ? "📅 Date: " : "📅 ದಿನಾಂಕ: ") . $group->price_date->format('d M Y') . "\n";
            $mandiShare .= ($activeLocale === 'en' ? "👉 View full details: " : "👉 ಸಂಪೂರ್ಣ ವಿವರಗಳಿಗೆ: ") . url()->current();
        @endphp
        <a href="https://wa.me/?text={{ rawurlencode($mandiShare) }}" 
           target="_blank" 
           rel="noopener noreferrer" 
           class="text-xs font-bold text-emerald-800 hover:text-emerald-950 flex items-center gap-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span>💬</span>
            <span>{{ $activeLocale === 'en' ? 'Share' : 'ಶೇರ್ ಮಾಡಿ' }}</span>
        </a>
    </div>
</div>
