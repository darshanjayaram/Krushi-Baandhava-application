@php
    $locale = app()->getLocale();
    $adminLogo = \App\Models\SystemSetting::get('app_logo', '/icons/icon-192.svg');
    $appLogoUrl = asset($adminLogo) . '?v=' . (file_exists(public_path(ltrim($adminLogo, '/'))) ? filemtime(public_path(ltrim($adminLogo, '/'))) : '1');
    $appNameEn = \App\Models\SystemSetting::get('application_name', 'Krushi Baandhava');
    $appNameKn = \App\Models\SystemSetting::get('application_name_kn', 'ಕೃಷಿ ಬಾಂಧವ');
    
    // Dynamic Footer CMS values
    $footerDevName = \App\Models\SystemSetting::get('footer_developer_name', 'Darshan Jayaram');
    $footerDevUrl = \App\Models\SystemSetting::get('footer_developer_url', '#');
    $footerCopyrightText = \App\Models\SystemSetting::get('footer_copyright_text', '© ' . date('Y') . ' Krushi Baandhava. All rights reserved.');
    
    $footerTagline = $locale === 'kn'
        ? \App\Models\SystemSetting::get('footer_tagline_kn', 'ಕರ್ನಾಟಕದ ರೈತ ಮಾರುಕಟ್ಟೆ ಮಾಹಿತಿ ವೇದಿಕೆ')
        : \App\Models\SystemSetting::get('footer_tagline_en', 'Karnataka Farmer Market Intelligence Network');

    $footerDesc = $locale === 'kn'
        ? \App\Models\SystemSetting::get('footer_description_kn', 'ಕರ್ನಾಟಕದ ಸ್ವತಂತ್ರ ರೈತ ಮಾರುಕಟ್ಟೆ ಮಾಹಿತಿ ವೇದಿಕೆ — ನೈಜ ಸಮಯದ ಎಪಿಎಂಸಿ ದರಗಳು ಮತ್ತು ಬೆಳೆ ಮುನ್ಸೂಚನೆ.')
        : \App\Models\SystemSetting::get('footer_description_en', 'Karnataka agricultural intelligence network — real-time APMC trading prices, modal rates, and predictive crop guidance.');
        
    $footerTelemetryBadge = \App\Models\SystemSetting::get('footer_telemetry_badge', '31 Districts • 160+ APMCs');
    $footerTelemetrySource = \App\Models\SystemSetting::get('footer_telemetry_source', 'Sourced from KRAMA & Agmarknet Feeds');

    $footerDisclaimer = $locale === 'kn'
        ? \App\Models\SystemSetting::get('footer_disclaimer_kn', 'ದರಗಳು ಸಾರ್ವಜನಿಕ ಎಪಿಎಂಸಿ ದತ್ತಾಂಶವನ್ನು ಆಧರಿಸಿವೆ — ವ್ಯಾಪಾರದ ಮೊದಲು ಮಂಡಿಯಲ್ಲಿ ಪರಿಶೀಲಿಸಿ. ಕೃಷಿ ಬಾಂಧವ ಸ್ವತಂತ್ರ ರೈತ ಕಲ್ಯಾಣ ವೇದಿಕೆಯಾಗಿದ್ದು, ಯಾವುದೇ ಸರ್ಕಾರಿ ಸಂಸ್ಥೆಯನ್ನು ಪ್ರತಿನಿಧಿಸುವುದಿಲ್ಲ.')
        : \App\Models\SystemSetting::get('footer_disclaimer_en', 'Prices are indicative, sourced from public mandi data — verify before trading. Krushi Baandhava is an independent farmer welfare platform and does not represent any government entity.');
        
    $showTelemetry = (bool) \App\Models\SystemSetting::get('footer_show_telemetry', true);
    $showDisclaimer = (bool) \App\Models\SystemSetting::get('footer_show_disclaimer', true);

    // Dynamic Column 2 (Platform Hubs)
    $col2Title = $locale === 'kn'
        ? \App\Models\SystemSetting::get('footer_col2_title_kn', 'ವೇದಿಕೆ ಕೇಂದ್ರಗಳು')
        : \App\Models\SystemSetting::get('footer_col2_title_en', 'Platform Hubs');
    $rawCol2 = \App\Models\SystemSetting::get('footer_col2_links');
    $col2Links = is_array($rawCol2) ? $rawCol2 : (json_decode($rawCol2 ?? '', true) ?: []);

    // Dynamic Column 3 (Community & Help)
    $col3Title = $locale === 'kn'
        ? \App\Models\SystemSetting::get('footer_col3_title_kn', 'ಸಂಪರ್ಕ & ಸಹಾಯ')
        : \App\Models\SystemSetting::get('footer_col3_title_en', 'Community & Help');
    $rawCol3 = \App\Models\SystemSetting::get('footer_col3_links');
    $col3Links = is_array($rawCol3) ? $rawCol3 : (json_decode($rawCol3 ?? '', true) ?: []);

    // Filter visible links only (respects CMS hide/show toggle)
    $visibleCol2Links = array_values(array_filter($col2Links, fn($i) => ($i['is_visible'] ?? true) === true));
    $visibleCol3Links = array_values(array_filter($col3Links, fn($i) => ($i['is_visible'] ?? true) === true));

    // First 6 visible items strictly fill the 1st (Left) column from top to bottom.
    // Only after the 1st column is filled with 6 items, subsequent items (7th onwards) flow into the 2nd (Right) column.
    $col2Left = array_slice($visibleCol2Links, 0, 6);
    $col2Right = array_slice($visibleCol2Links, 6);
@endphp

<!-- Full-Width Classic & Simple Footer (100% Viewport Background, Aligned Max-w-7xl Container) -->
<footer class="w-full mt-auto bg-[#EFEAE0] border-t-2 border-[#DDD3BE] text-stone-800 pb-24 md:pb-8"
        x-data="{
            exploreOpen: false,
            communityOpen: false
        }">
    
    <!-- Main Content Container: Perfectly matches Homepage max-w-7xl width and gutters -->
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10">

        <!-- 3-Column Responsive Flex Layout (Bulletproof: Immune to Grid Collapsing) -->
        <div class="flex flex-col md:flex-row gap-8 lg:gap-12 justify-between items-start">

            <!-- ============================================================== -->
            <!-- COLUMN 1: BRAND, TELEMETRY & ABOUT (5/12 desktop width)        -->
            <!-- ============================================================== -->
            <div class="w-full md:w-5/12 min-w-0 space-y-3.5">
                <!-- Brand Header -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white border border-[#DDD3BE] p-1.5 shrink-0 shadow-2xs flex items-center justify-center">
                        <img src="{{ $appLogoUrl }}" alt="Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-base font-black text-[#1C5A2C] tracking-tight">{{ $appNameEn }}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold font-kannada bg-[#E2DAC8] text-[#1C5A2C] border border-[#D0C6B4]">
                                {{ $appNameKn }}
                            </span>
                        </div>
                        <p class="text-[11px] text-stone-600 font-medium mt-0.5 leading-none {{ $locale === 'kn' ? 'font-kannada' : '' }}">
                            {{ $footerTagline }}
                        </p>
                    </div>
                </div>

                <!-- Mission Description -->
                <p class="text-xs text-stone-600 leading-relaxed {{ $locale === 'kn' ? 'font-kannada leading-normal' : '' }}">
                    {{ $footerDesc }}
                </p>

                <!-- Live APMC Feeds Telemetry Pill -->
                @if($showTelemetry)
                    <div class="inline-flex flex-wrap items-center gap-2 px-3 py-1.5 rounded-xl bg-[#E6DFD1] border border-[#D5CABB] text-xs">
                        <div class="flex items-center gap-1.5 font-bold text-stone-800">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-600"></span>
                            </span>
                            <span>{{ $footerTelemetryBadge }}</span>
                        </div>
                        <span class="text-stone-400 hidden sm:inline">•</span>
                        <span class="text-[11px] text-stone-600 font-mono">⚡ {{ $footerTelemetrySource }}</span>
                    </div>
                @endif

                <!-- Compliance Notice -->
                @if($showDisclaimer)
                    <p class="text-[11px] text-stone-500 leading-relaxed {{ $locale === 'kn' ? 'font-kannada' : '' }}">
                        <strong class="text-stone-700">🛡️ {{ $locale === 'kn' ? 'ಹಕ್ಕುತ್ಯಾಗ:' : 'Notice:' }}</strong>
                        {{ $footerDisclaimer }}
                    </p>
                @endif
            </div>

            <!-- ============================================================== -->
            <!-- COLUMN 2: DYNAMIC NAVIGATION HUBS (4/12 desktop width)         -->
            <!-- ============================================================== -->
            <div class="w-full md:w-4/12 min-w-0 space-y-2.5">
                <!-- Desktop Header (Always visible) -->
                <div class="hidden md:flex items-center justify-between pb-2 border-b border-[#DDD3BE]">
                    <h4 class="text-xs font-black text-stone-800 uppercase tracking-wider flex items-center gap-1.5">
                        <span>📂</span>
                        <span class="{{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $col2Title }}</span>
                    </h4>
                </div>

                <!-- DESKTOP 2-COLUMN DYNAMIC LINKS (Ordered Top to Bottom: First 6 on Left, 7+ on Right) -->
                @if(count($visibleCol2Links) > 0)
                    <div class="hidden md:grid {{ count($col2Right) > 0 ? 'md:grid-cols-2 gap-x-6' : 'md:grid-cols-1' }} gap-y-2 pt-1">
                        <!-- Sub-column Left (Items 1 to 6 Top to Bottom) -->
                        <div class="space-y-2">
                            @foreach($col2Left as $item)
                                @php
                                    $itemLabel = ($locale === 'kn' && !empty($item['label_kn'])) ? $item['label_kn'] : ($item['label_en'] ?? '');
                                    $itemIcon = $item['icon'] ?? '🔗';
                                    $itemUrl = $item['url'] ?? '#';
                                    $itemTarget = !empty($item['new_tab']) ? 'target="_blank" rel="noopener noreferrer"' : '';
                                    $itemStyle = $item['style'] ?? 'link';
                                @endphp
                                @if($itemStyle === 'button')
                                    <a href="{{ $itemUrl }}" {!! $itemTarget !!}
                                       class="inline-flex items-center justify-center gap-1.5 w-full px-2.5 py-1.5 rounded-xl bg-[#1C5A2C] hover:bg-[#154622] text-white text-xs font-bold transition shadow-2xs">
                                        <span>{{ $itemIcon }}</span>
                                        <span class="{{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $itemLabel }}</span>
                                        <span>→</span>
                                    </a>
                                @else
                                    <a href="{{ $itemUrl }}" {!! $itemTarget !!}
                                       class="flex items-center gap-1.5 text-xs text-stone-700 hover:text-[#1C5A2C] font-semibold transition hover:underline">
                                        <span>{{ $itemIcon }}</span>
                                        <span class="{{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $itemLabel }}</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>

                        <!-- Sub-column Right (Items 7 onwards Top to Bottom) -->
                        @if(count($col2Right) > 0)
                            <div class="space-y-2">
                                @foreach($col2Right as $item)
                                    @php
                                        $itemLabel = ($locale === 'kn' && !empty($item['label_kn'])) ? $item['label_kn'] : ($item['label_en'] ?? '');
                                        $itemIcon = $item['icon'] ?? '🔗';
                                        $itemUrl = $item['url'] ?? '#';
                                        $itemTarget = !empty($item['new_tab']) ? 'target="_blank" rel="noopener noreferrer"' : '';
                                        $itemStyle = $item['style'] ?? 'link';
                                    @endphp
                                    @if($itemStyle === 'button')
                                        <a href="{{ $itemUrl }}" {!! $itemTarget !!}
                                           class="inline-flex items-center justify-center gap-1.5 w-full px-2.5 py-1.5 rounded-xl bg-[#1C5A2C] hover:bg-[#154622] text-white text-xs font-bold transition shadow-2xs">
                                            <span>{{ $itemIcon }}</span>
                                            <span class="{{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $itemLabel }}</span>
                                            <span>→</span>
                                        </a>
                                    @else
                                        <a href="{{ $itemUrl }}" {!! $itemTarget !!}
                                           class="flex items-center gap-1.5 text-xs text-stone-700 hover:text-[#1C5A2C] font-semibold transition hover:underline">
                                            <span>{{ $itemIcon }}</span>
                                            <span class="{{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $itemLabel }}</span>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <div class="hidden md:block text-xs text-stone-500 italic pt-1">No links configured.</div>
                @endif

                <!-- MOBILE ACCORDION (Column 2) -->
                <div class="md:hidden space-y-2">
                    <button type="button" 
                            @click="exploreOpen = !exploreOpen"
                            class="w-full py-2.5 px-3.5 rounded-xl bg-white/70 border border-[#DDD3BE] flex items-center justify-between text-xs font-bold text-stone-800 active:scale-[0.99] transition cursor-pointer">
                        <span class="flex items-center gap-2">
                            <span>📂</span>
                            <span class="{{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $col2Title }}</span>
                        </span>
                        <span class="text-stone-500 transform transition-transform duration-200" :class="{ 'rotate-180': exploreOpen }">▼</span>
                    </button>

                    <div x-show="exploreOpen" x-cloak class="space-y-1.5 pt-1">
                        @forelse($visibleCol2Links as $item)
                            @php
                                $itemLabel = ($locale === 'kn' && !empty($item['label_kn'])) ? $item['label_kn'] : ($item['label_en'] ?? '');
                                $itemIcon = $item['icon'] ?? '🔗';
                                $itemUrl = $item['url'] ?? '#';
                                $itemTarget = !empty($item['new_tab']) ? 'target="_blank" rel="noopener noreferrer"' : '';
                            @endphp
                            <a href="{{ $itemUrl }}" {!! $itemTarget !!} class="flex items-center justify-between p-2 rounded-lg bg-white/80 border border-[#DDD3BE] text-xs font-semibold text-stone-800">
                                <span class="flex items-center gap-2">
                                    <span>{{ $itemIcon }}</span>
                                    <span class="{{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $itemLabel }}</span>
                                </span>
                                <span class="text-stone-400">›</span>
                            </a>
                        @empty
                            <div class="text-xs text-stone-500 italic p-2">No links available.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- COLUMN 3: COMMUNITY, ACTIONS & WHATSAPP (3/12 desktop width)   -->
            <!-- ============================================================== -->
            <div class="w-full md:w-3/12 min-w-0 space-y-2.5">
                <!-- Desktop Header (Always visible) -->
                <div class="hidden md:flex items-center justify-between pb-2 border-b border-[#DDD3BE]">
                    <h4 class="text-xs font-black text-stone-800 uppercase tracking-wider flex items-center gap-1.5">
                        <span>🤝</span>
                        <span class="{{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $col3Title }}</span>
                    </h4>
                </div>

                <!-- DESKTOP ACTIONS & BUTTONS -->
                <div class="hidden md:block space-y-2 pt-1">
                    @forelse($visibleCol3Links as $item)
                        @php
                            $itemLabel = ($locale === 'kn' && !empty($item['label_kn'])) ? $item['label_kn'] : ($item['label_en'] ?? '');
                            $itemSubtitle = ($locale === 'kn' && !empty($item['subtitle_kn'])) ? $item['subtitle_kn'] : ($item['subtitle_en'] ?? '');
                            $itemIcon = $item['icon'] ?? '💬';
                            $itemUrl = $item['url'] ?? '#';
                            $itemTarget = !empty($item['new_tab']) ? 'target="_blank" rel="noopener noreferrer"' : '';
                            $itemStyle = $item['style'] ?? 'link';
                        @endphp

                        @if($itemStyle === 'button')
                            <!-- 🟢 Green WhatsApp / CTA Button -->
                            <a href="{{ $itemUrl }}" {!! $itemTarget !!}
                               class="inline-flex items-center justify-between w-full px-3.5 py-2.5 rounded-xl bg-[#1C5A2C] hover:bg-[#154622] text-white text-xs font-bold transition shadow-2xs group">
                                <span class="flex items-center gap-2.5">
                                    <span class="text-base shrink-0">{{ $itemIcon }}</span>
                                    <span class="flex flex-col text-left">
                                        <span class="{{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $itemLabel }}</span>
                                        @if($itemSubtitle)
                                            <span class="text-[10px] font-normal text-emerald-100/90 {{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $itemSubtitle }}</span>
                                        @endif
                                    </span>
                                </span>
                                <span class="text-emerald-200 group-hover:translate-x-0.5 transition-transform text-sm font-bold">→</span>
                            </a>
                        @elseif($itemStyle === 'chip')
                            <!-- ✉️ Contact Chip -->
                            <div class="text-xs text-stone-600 flex items-center gap-1.5 p-2 rounded-xl bg-white/70 border border-[#DDD3BE]">
                                <span class="text-stone-500">{{ $itemIcon }}</span>
                                @if($itemSubtitle)
                                    <span class="text-stone-500 {{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $itemSubtitle }}:</span>
                                @endif
                                <a href="{{ $itemUrl }}" {!! $itemTarget !!} class="font-semibold text-stone-800 hover:text-[#1C5A2C] hover:underline truncate {{ $locale === 'kn' ? 'font-kannada' : '' }}">
                                    {{ $itemLabel }}
                                </a>
                            </div>
                        @elseif($itemStyle === 'alert')
                            <!-- 🟡 Alert Pill -->
                            <a href="{{ $itemUrl }}" {!! $itemTarget !!}
                               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-amber-500/10 border border-amber-600/20 text-xs font-semibold text-amber-900 hover:bg-amber-500/20 transition w-full {{ $locale === 'kn' ? 'font-kannada' : '' }}">
                                <span>{{ $itemIcon }}</span>
                                <span>{{ $itemLabel }}</span>
                            </a>
                        @else
                            <!-- 🔗 Standard Link -->
                            <a href="{{ $itemUrl }}" {!! $itemTarget !!}
                               class="flex items-center gap-1.5 text-xs text-stone-700 hover:text-[#1C5A2C] font-semibold transition hover:underline">
                                <span>{{ $itemIcon }}</span>
                                <span class="{{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $itemLabel }}</span>
                            </a>
                        @endif
                    @empty
                        <div class="text-xs text-stone-500 italic">No community actions configured.</div>
                    @endforelse
                </div>

                <!-- MOBILE ACCORDION (Column 3) -->
                <div class="md:hidden space-y-2">
                    <button type="button" 
                            @click="communityOpen = !communityOpen"
                            class="w-full py-2.5 px-3.5 rounded-xl bg-white/70 border border-[#DDD3BE] flex items-center justify-between text-xs font-bold text-stone-800 active:scale-[0.99] transition cursor-pointer">
                        <span class="flex items-center gap-2">
                            <span>🤝</span>
                            <span class="{{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $col3Title }}</span>
                        </span>
                        <span class="text-stone-500 transform transition-transform duration-200" :class="{ 'rotate-180': communityOpen }">▼</span>
                    </button>

                    <div x-show="communityOpen" x-cloak class="space-y-2 pt-1">
                        @forelse($visibleCol3Links as $item)
                            @php
                                $itemLabel = ($locale === 'kn' && !empty($item['label_kn'])) ? $item['label_kn'] : ($item['label_en'] ?? '');
                                $itemSubtitle = ($locale === 'kn' && !empty($item['subtitle_kn'])) ? $item['subtitle_kn'] : ($item['subtitle_en'] ?? '');
                                $itemIcon = $item['icon'] ?? '💬';
                                $itemUrl = $item['url'] ?? '#';
                                $itemTarget = !empty($item['new_tab']) ? 'target="_blank" rel="noopener noreferrer"' : '';
                                $itemStyle = $item['style'] ?? 'link';
                            @endphp

                            @if($itemStyle === 'button')
                                <a href="{{ $itemUrl }}" {!! $itemTarget !!}
                                   class="inline-flex items-center justify-between w-full px-3.5 py-2.5 rounded-xl bg-[#1C5A2C] hover:bg-[#154622] text-white text-xs font-bold transition">
                                    <span class="flex items-center gap-2">
                                        <span class="text-base">{{ $itemIcon }}</span>
                                        <span class="flex flex-col text-left">
                                            <span class="{{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $itemLabel }}</span>
                                            @if($itemSubtitle)
                                                <span class="text-[10px] font-normal text-emerald-100 {{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $itemSubtitle }}</span>
                                            @endif
                                        </span>
                                    </span>
                                    <span>→</span>
                                </a>
                            @elseif($itemStyle === 'chip')
                                <div class="text-xs text-stone-600 flex items-center gap-1.5 p-2 rounded-xl bg-white/80 border border-[#DDD3BE]">
                                    <span>{{ $itemIcon }}</span>
                                    @if($itemSubtitle)
                                        <span class="text-stone-500 {{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $itemSubtitle }}:</span>
                                    @endif
                                    <a href="{{ $itemUrl }}" {!! $itemTarget !!} class="font-semibold text-stone-800 hover:text-[#1C5A2C] hover:underline truncate {{ $locale === 'kn' ? 'font-kannada' : '' }}">
                                        {{ $itemLabel }}
                                    </a>
                                </div>
                            @elseif($itemStyle === 'alert')
                                <a href="{{ $itemUrl }}" {!! $itemTarget !!}
                                   class="flex items-center gap-1.5 p-2 rounded-xl bg-amber-500/10 border border-amber-600/20 text-xs font-semibold text-amber-900 {{ $locale === 'kn' ? 'font-kannada' : '' }}">
                                    <span>{{ $itemIcon }}</span>
                                    <span>{{ $itemLabel }}</span>
                                </a>
                            @else
                                <a href="{{ $itemUrl }}" {!! $itemTarget !!}
                                   class="flex items-center justify-between p-2 rounded-lg bg-white/80 border border-[#DDD3BE] text-xs font-semibold text-stone-800">
                                    <span class="flex items-center gap-2">
                                        <span>{{ $itemIcon }}</span>
                                        <span class="{{ $locale === 'kn' ? 'font-kannada' : '' }}">{{ $itemLabel }}</span>
                                    </span>
                                    <span class="text-stone-400">›</span>
                                </a>
                            @endif
                        @empty
                            <div class="text-xs text-stone-500 italic p-2">No actions available.</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>

        <!-- ============================================================== -->
        <!-- BOTTOM ATTRIBUTION & COPYRIGHT BAR                            -->
        <!-- ============================================================== -->
        <div class="mt-8 pt-5 border-t border-[#DDD3BE] flex flex-col md:flex-row items-center justify-between gap-3 text-xs text-stone-600">
            <!-- Left: Developed by -->
            <div class="w-full md:w-1/3 flex justify-center md:justify-start items-center gap-1.5 order-2 md:order-1">
                <span>Developed by</span>
                @if($footerDevUrl && $footerDevUrl !== '#')
                    <a href="{{ $footerDevUrl }}" target="_blank" rel="noopener noreferrer" class="font-bold text-[#1C5A2C] hover:underline">
                        {{ $footerDevName }}
                    </a>
                @else
                    <span class="font-bold text-[#1C5A2C]">{{ $footerDevName }}</span>
                @endif
            </div>

            <!-- Center: Made with ❤️ for Karnataka Farmers 🌱 -->
            <div class="w-full md:w-1/3 flex justify-center items-center text-center order-1 md:order-2">
                <span class="font-medium text-stone-700 flex items-center justify-center gap-1 {{ $locale === 'kn' ? 'font-kannada' : '' }}">
                    <span>🌾</span>
                    <span>{{ $locale === 'kn' ? 'ಕರ್ನಾಟಕದ ರೈತರಿಗಾಗಿ ರೂಪಿಸಲಾಗಿದೆ 🌱' : 'Made with ❤️ for Karnataka Farmers 🌱' }}</span>
                </span>
            </div>

            <!-- Right: Copyright notice -->
            <div class="w-full md:w-1/3 flex justify-center md:justify-end items-center text-center md:text-right order-3">
                <span>{{ $footerCopyrightText }}</span>
            </div>
        </div>

    </div>
</footer>
