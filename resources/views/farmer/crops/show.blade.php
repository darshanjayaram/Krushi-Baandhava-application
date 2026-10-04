@extends('layouts.farmer')

@php
    $activeLocale = $activeLocale ?? (request()->query('lang') ?: (session('locale') ?: (request()->cookie('locale') ?: app()->getLocale())));
@endphp

@section('title', ($activeLocale === 'en' 
    ? $crop->name . " — Today's Market Prices & Forecast" 
    : (($crop->name_kn ? $crop->name_kn . ' (' . $crop->name . ')' : $crop->name) . ' — ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು & ಮುನ್ಸೂಚನೆ')
))

@section('content')
@php
    $selectedMarketPrices = $selectedMarketPrices ?? collect();
    $activePriceItem = $activePriceItem ?? (
        $selectedMarketPrices->isNotEmpty()
            ? ($varietyId 
                ? ($selectedMarketPrices->firstWhere('variety_id', $varietyId) ?? $selectedMarketPrices->first())
                : ($selectedMarketPrices->where('price_date', $latestDate)->sortByDesc('modal_price')->first() ?? $selectedMarketPrices->first()))
            : $mandiPrices->first()
    );

    $displayModal = $activePriceItem ? (float) $activePriceItem->modal_price : ($stats['avg_modal'] > 0 ? (float) $stats['avg_modal'] : 0);
    $rawMktName = $selectedMarket ? $selectedMarket->name : ($activePriceItem ? $activePriceItem->market->name : null);
    $rawMktKn = $selectedMarket ? $selectedMarket->name_kn : ($activePriceItem ? $activePriceItem->market->name_kn : null);
    $displayMarketName = $rawMktName 
        ? ($activeLocale === 'en' ? $rawMktName : ($rawMktKn ?? $rawMktName)) 
        : ($activeLocale === 'en' ? 'State Average (Karnataka)' : 'ಕರ್ನಾಟಕ ಸರಾಸರಿ');
    $displayMarketDistrict = $selectedMarket?->district?->name ?? ($activePriceItem?->market?->district?->name ?? 'Karnataka');
    $isStandardQuintal = ($crop->standard_unit === 'Quintal' || !$crop->standard_unit);
    $perKgPrice = ($isStandardQuintal && $displayModal > 0) ? round($displayModal / 100, 1) : null;

    // Determine Market Advisory Sentiment from forecast
    $firstHorizon = !empty($forecast['horizons']) ? ($forecast['horizons'][1] ?? $forecast['horizons'][0]) : null;
    $forecastDir = $firstHorizon['direction'] ?? 'neutral';

    $priceDateRaw = $activePriceItem?->price_date ?? $latestDate;
    $priceCarbon = $priceDateRaw ? \Carbon\Carbon::parse($priceDateRaw) : null;
    $isPriceToday = $priceCarbon ? $priceCarbon->isToday() : false;
    $asOfText = $priceCarbon 
        ? ($isPriceToday 
            ? ($activeLocale === 'en' ? 'as of now' : 'ಇಂದಿನವರೆಗೆ') 
            : ($activeLocale === 'en' ? 'as of ' . $priceCarbon->format('d M') : 'ದಿನಾಂಕ: ' . $priceCarbon->format('d M')))
        : ($activeLocale === 'en' ? 'as of now' : 'ಇಂದಿನವರೆಗೆ');
@endphp

<script>
    window.cropMarketBoxConfig = {
        activeSort: @json($defaultMarketSort ?? 'nearest_first'),
        showAllRadius: {{ (!empty($selectedMarket) && empty($selectedMarket->is_within_radius)) ? 'true' : 'false' }},
        showAllGrades: true,
        isMarketLoading: false,
        selectedMarketId: @json($selectedMarket?->id ?? null),
        selectedMarketName: @json($displayMarketName),
        selectedGradeVarietyId: @json($activeVarietyId ?? ($activePriceItem?->variety_id ?? null)),
        selectedGradeName: @json($activePriceItem?->grade ?? null),
        isMarketExplicitlySelected: {{ (!empty($marketParam) && !empty($isMarketParamMatched)) ? 'true' : 'false' }},
        priceData: @json($priceData),
        gradesList: @json($gradesList),
        whereToSellUrl: @json($whereToSellUrl),
        resetUrl: @json($resetUrl),
        cropName: @json($activeLocale === 'en' ? $crop->name : ($crop->name_kn ?: $crop->name)),
        brandName: @json($activeLocale === 'en' ? 'Krushi Baandhava — ' : 'ಕೃಷಿ ಬಾಂಧವ — '),
        mktPrefix: @json($boardMeta ? ($activeLocale === 'en' ? 'Centre: ' : 'ಕೇಂದ್ರ: ') : ($activeLocale === 'en' ? 'Market: ' : 'ಮಾರುಕಟ್ಟೆ: ')),
        ratePrefix: @json($activeLocale === 'en' ? "Today's Modal Rate: " : 'ಇಂದಿನ ಮಾದರಿ ದರ: '),
        kgPrefix: @json($activeLocale === 'en' ? '⚖️ Approx per kg: ' : '⚖️ ಪ್ರತಿ ಕೆ.ಜಿ ಗೆ: '),
        dtPrefix: @json($activeLocale === 'en' ? '📅 Date: ' : '📅 ದಿನಾಂಕ: '),
        linkPrefix: @json($activeLocale === 'en' ? '👉 View Full Rate & Forecast: ' : '👉 ಸಂಪೂರ್ಣ ದರ & ಮುನ್ಸೂಚನೆ ವೀಕ್ಷಿಸಿ: '),
        stateAverageText: @json($activeLocale === 'en' ? 'State Average (Karnataka)' : 'ಕರ್ನಾಟಕ ಸರಾಸರಿ')
    };

    function cropMarketBox(config) {
        return {
            activeSort: config.activeSort || 'nearest_first',
            showAllRadius: config.showAllRadius || false,
            showAllGrades: config.showAllGrades !== undefined ? config.showAllGrades : true,
            isMarketLoading: false,
            selectedMarketId: config.selectedMarketId,
            selectedMarketName: config.selectedMarketName,
            isMarketExplicitlySelected: config.isMarketExplicitlySelected,
            priceData: config.priceData || {},
            gradesList: config.gradesList || [],
            whereToSellUrl: config.whereToSellUrl || '#',
            resetUrl: config.resetUrl || '#',
            cropName: config.cropName || '',
            brandName: config.brandName || '',
            mktPrefix: config.mktPrefix || '',
            ratePrefix: config.ratePrefix || '',
            kgPrefix: config.kgPrefix || '',
            dtPrefix: config.dtPrefix || '',
            linkPrefix: config.linkPrefix || '',
            stateAverageText: config.stateAverageText || '',

                        isMandiSelected(id) {
                if (this.selectedMarketId === null || this.selectedMarketId === undefined) return false;
                return Number(this.selectedMarketId) === Number(id);
            },

            isGradeSelected(g) {
                if (!g) return false;
                if (this.selectedGradeVarietyId !== null && this.selectedGradeVarietyId !== undefined) {
                    const existsInCurrentMarket = Array.isArray(this.gradesList) && this.gradesList.some(item => Number(item.variety_id) === Number(this.selectedGradeVarietyId));
                    if (existsInCurrentMarket) {
                        const sameVar = Number(this.selectedGradeVarietyId) === Number(g.variety_id);
                        const sameGrade = (this.selectedGradeName || '') === (g.grade || '');
                        return sameVar && sameGrade;
                    }
                }
                return !!g.is_selected;
            },

            async switchGradeAsync(gradeObj, targetUrl, pushHistory = true) {
                if (this.isGradeSelected(gradeObj) && !this.isMarketLoading) return;

                this.selectedGradeVarietyId = Number(gradeObj.variety_id);
                this.selectedGradeName = gradeObj.grade || null;

                if (this.gradesList && Array.isArray(this.gradesList)) {
                    this.gradesList.forEach(item => {
                        item.is_selected = (Number(item.variety_id) === Number(gradeObj.variety_id) && (item.grade || '') === (gradeObj.grade || ''));
                    });
                }

                this.isMarketLoading = true;
                const startTime = Date.now();

                if (pushHistory && window.history && window.history.pushState) {
                    window.history.pushState({ 
                        varietyId: gradeObj.variety_id, 
                        grade: gradeObj.grade, 
                        market: this.selectedMarketName, 
                        marketId: this.selectedMarketId 
                    }, '', targetUrl);
                }

                try {
                    const res = await fetch(targetUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Market-Switch': '1',
                            'Accept': 'application/json'
                        }
                    });

                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();

                    if (data && data.success) {
                        if (data.price_item) this.priceData = data.price_item;
                        if (data.grades) this.gradesList = data.grades;
                        if (data.where_to_sell_url) this.whereToSellUrl = data.where_to_sell_url;
                        if (data.reset_url) this.resetUrl = data.reset_url;

                        const advisoryContent = document.getElementById('crop-advisory-content');
                        if (advisoryContent && data.advisory_html) {
                            advisoryContent.innerHTML = data.advisory_html;
                        }

                        const forecastContent = document.getElementById('crop-forecast-content');
                        if (forecastContent && data.forecast_html) {
                            forecastContent.innerHTML = data.forecast_html;
                        }

                        const seasonalContent = document.getElementById('crop-seasonal-content');
                        if (seasonalContent && data.seasonal_html) {
                            seasonalContent.innerHTML = data.seasonal_html;
                            if (typeof window.animateSeasonalBars === 'function') {
                                window.animateSeasonalBars();
                            }
                        }

                        window.dispatchEvent(new CustomEvent('market-changed', {
                            detail: {
                                marketId: this.selectedMarketId,
                                marketName: this.selectedMarketName,
                                varietyId: gradeObj.variety_id
                            }
                        }));
                    }
                } catch (err) {
                    console.error('[GradeSwitch] Async fetch failed, falling back:', err);
                    window.location.href = targetUrl;
                    return;
                } finally {
                    const elapsed = Date.now() - startTime;
                    const waitTime = Math.max(0, 480 - elapsed);
                    setTimeout(() => {
                        this.isMarketLoading = false;
                    }, waitTime);
                }
            },
scrollToSelectedMandi() {
                const tryScroll = (attempts = 0) => {
                    const container = this.$refs.mandiScrollBox;
                    if (!container) return;
                    const selectedPill = container.querySelector('.mandi-pill-active') || container.querySelector('.mandi-pill-selected');
                    if (selectedPill) {
                        const containerRect = container.getBoundingClientRect();
                        const pillRect = selectedPill.getBoundingClientRect();
                        const isAbove = pillRect.top < containerRect.top;
                        const isBelow = pillRect.bottom > containerRect.bottom;
                        if (isBelow) {
                            container.scrollBy({ top: (pillRect.bottom - containerRect.bottom) + 20, behavior: 'smooth' });
                        } else if (isAbove) {
                            container.scrollBy({ top: (pillRect.top - containerRect.top) - 20, behavior: 'smooth' });
                        }
                    } else if (attempts < 5) {
                        setTimeout(() => tryScroll(attempts + 1), 80);
                    }
                };
                setTimeout(() => tryScroll(0), 120);
            },

            async switchMarketAsync(marketName, marketId, targetUrl, pushHistory = true) {
                if (this.selectedMarketId === marketId && !this.isMarketLoading) return;

                this.selectedMarketId = marketId;
                this.selectedMarketName = marketName;
                this.isMarketExplicitlySelected = true;
                this.showAllRadius = true;
                this.isMarketLoading = true;
                const startTime = Date.now();

                if (pushHistory && window.history && window.history.pushState) {
                    window.history.pushState({ market: marketName, marketId: marketId }, '', targetUrl);
                }

                this.$nextTick(() => {
                    this.scrollToSelectedMandi();
                });

                try {
                    const res = await fetch(targetUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Market-Switch': '1',
                            'Accept': 'application/json'
                        }
                    });

                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();

                    if (data && data.success) {
                        if (data.price_item) this.priceData = data.price_item;
                        if (data.grades && Array.isArray(data.grades)) {
                            this.gradesList = data.grades;
                            const activeGrade = data.grades.find(item => item.is_selected) || data.grades[0];
                            if (activeGrade) {
                                this.selectedGradeVarietyId = Number(activeGrade.variety_id);
                                this.selectedGradeName = activeGrade.grade || null;
                            } else {
                                this.selectedGradeVarietyId = null;
                                this.selectedGradeName = null;
                            }
                        }
                        if (data.where_to_sell_url) this.whereToSellUrl = data.where_to_sell_url;
                        if (data.reset_url) this.resetUrl = data.reset_url;

                        const advisoryContent = document.getElementById('crop-advisory-content');
                        if (advisoryContent && data.advisory_html) {
                            advisoryContent.innerHTML = data.advisory_html;
                        }

                        const forecastContent = document.getElementById('crop-forecast-content');
                        if (forecastContent && data.forecast_html) {
                            forecastContent.innerHTML = data.forecast_html;
                        }

                        const seasonalContent = document.getElementById('crop-seasonal-content');
                        if (seasonalContent && data.seasonal_html) {
                            seasonalContent.innerHTML = data.seasonal_html;
                            if (typeof window.animateSeasonalBars === 'function') {
                                window.animateSeasonalBars();
                            }
                        }

                        window.dispatchEvent(new CustomEvent('market-changed', {
                            detail: {
                                marketId: data.market ? data.market.id : marketId,
                                marketName: data.market ? data.market.display_name : marketName
                            }
                        }));
                    }
                } catch (err) {
                    console.error('[MarketSwitch] Async fetch failed, falling back:', err);
                    window.location.href = targetUrl;
                    return;
                } finally {
                    const elapsed = Date.now() - startTime;
                    const waitTime = Math.max(0, 480 - elapsed);
                    setTimeout(() => {
                        this.isMarketLoading = false;
                    }, waitTime);
                }
            },

            async resetMarketAsync(targetResetUrl) {
                this.isMarketExplicitlySelected = false;
                this.selectedMarketId = null;
                this.selectedMarketName = this.stateAverageText;
                this.isMarketLoading = true;
                const startTime = Date.now();

                if (window.history && window.history.pushState) {
                    window.history.pushState({ market: null, marketId: null }, '', targetResetUrl);
                }

                try {
                    const res = await fetch(targetResetUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Market-Switch': '1',
                            'Accept': 'application/json'
                        }
                    });

                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();

                    if (data && data.success) {
                        if (data.price_item) this.priceData = data.price_item;
                        if (data.grades && Array.isArray(data.grades)) {
                            this.gradesList = data.grades;
                            const activeGrade = data.grades.find(item => item.is_selected) || data.grades[0];
                            if (activeGrade) {
                                this.selectedGradeVarietyId = Number(activeGrade.variety_id);
                                this.selectedGradeName = activeGrade.grade || null;
                            } else {
                                this.selectedGradeVarietyId = null;
                                this.selectedGradeName = null;
                            }
                        }
                        if (data.where_to_sell_url) this.whereToSellUrl = data.where_to_sell_url;
                        if (data.market) {
                            this.selectedMarketId = data.market.id;
                            this.selectedMarketName = data.market.display_name;
                        }

                        const advisoryContent = document.getElementById('crop-advisory-content');
                        if (advisoryContent && data.advisory_html) {
                            advisoryContent.innerHTML = data.advisory_html;
                        }

                        const forecastContent = document.getElementById('crop-forecast-content');
                        if (forecastContent && data.forecast_html) {
                            forecastContent.innerHTML = data.forecast_html;
                        }

                        const seasonalContent = document.getElementById('crop-seasonal-content');
                        if (seasonalContent && data.seasonal_html) {
                            seasonalContent.innerHTML = data.seasonal_html;
                            if (typeof window.animateSeasonalBars === 'function') {
                                window.animateSeasonalBars();
                            }
                        }

                        window.dispatchEvent(new CustomEvent('market-changed', {
                            detail: {
                                marketId: data.market ? data.market.id : null,
                                marketName: data.market ? data.market.display_name : ''
                            }
                        }));
                    }
                } catch (err) {
                    window.location.href = targetResetUrl;
                    return;
                } finally {
                    const elapsed = Date.now() - startTime;
                    const waitTime = Math.max(0, 480 - elapsed);
                    setTimeout(() => {
                        this.isMarketLoading = false;
                        this.$nextTick(() => this.scrollToSelectedMandi());
                    }, waitTime);
                }
            },

            get whatsappShareUrl() {
                const mName = (this.priceData.display_market_name || '').replace(/\s+APMC$/i, '');
                let text = '🌾 *' + this.brandName + this.cropName + '*\n'
                    + '📍 ' + this.mktPrefix + mName + '\n'
                    + '💰 ' + this.ratePrefix + (this.priceData.modal_formatted || '—') + ' / ' + (this.priceData.unit_label || 'Quintal') + '\n';

                if (this.priceData.per_kg_formatted) {
                    text += this.kgPrefix + this.priceData.per_kg_formatted + '\n';
                }
                text += this.dtPrefix + (this.priceData.date_formatted || '—') + '\n'
                    + this.linkPrefix + window.location.href;

                return 'https://wa.me/?text=' + encodeURIComponent(text);
            },

            init() {
                this.scrollToSelectedMandi();
                window.addEventListener('popstate', (e) => {
                    if (e.state && e.state.varietyId) {
                        this.selectedGradeVarietyId = Number(e.state.varietyId);
                        this.selectedGradeName = e.state.grade || null;
                    }
                    if (e.state && e.state.market) {
                        this.switchMarketAsync(e.state.market, e.state.marketId, window.location.href, false);
                    } else {
                        this.resetMarketAsync(this.resetUrl);
                    }
                });
            }
        };
    }
    window.cropMarketBox = cropMarketBox;
    if (window.Alpine) {
        window.Alpine.data('cropMarketBox', cropMarketBox);
    } else {
        document.addEventListener('alpine:init', () => {
            window.Alpine.data('cropMarketBox', cropMarketBox);
        });
    }
</script>

<div class="space-y-6" x-data="cropMarketBox(window.cropMarketBoxConfig)" x-init="init()">

    <!-- 1. Top Breadcrumb & Back Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('home') }}" 
           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white border border-[#E8DFC8] text-stone-700 hover:text-stone-950 font-bold text-xs shadow-2xs hover:bg-stone-50 transition active:scale-95">
            <span class="text-sm leading-none">&lsaquo;</span>
            <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Back' : 'ಹಿಂದಕ್ಕೆ' }}</span>
        </a>

        <div class="flex items-center gap-2 text-xs font-semibold text-stone-500">
            <a href="{{ route('farmer.crops.index') }}" class="px-2.5 py-0.5 rounded-full bg-white border border-[#E8DFC8] text-stone-700 hover:text-emerald-800 transition">
                {{ $crop->category ? ($activeLocale === 'en' ? $crop->category->name : ($crop->category->name_kn ?? $crop->category->name)) : ($activeLocale === 'en' ? 'Crops' : 'ಬೆಳೆಗಳು') }}
            </a>
            <span>&bull;</span>
            <span class="text-stone-900 font-bold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? $crop->name : ($crop->name_kn ?? $crop->name) }}
            </span>
        </div>
    </div>

    <style>
        .custom-mandi-scroll {
            scroll-behavior: smooth;
        }
        .custom-mandi-scroll::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .custom-mandi-scroll::-webkit-scrollbar-track {
            background: #F5EFE6;
            border-radius: 4px;
        }
        .custom-mandi-scroll::-webkit-scrollbar-thumb {
            background: #1C5A2C;
            border-radius: 4px;
        }

        /* ── Exact Weather-Widget Shimmer Engine (Mirrored from Homepage) ── */
        @keyframes kbWeatherShimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .kb-crop-shimmer {
            position: relative;
            overflow: hidden;
        }
        .kb-crop-shimmer::after {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            transform: translateX(-100%);
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.40) 35%,
                rgba(255, 255, 255, 0.85) 50%,
                rgba(255, 255, 255, 0.40) 65%,
                rgba(255, 255, 255, 0) 100%
            );
            animation: kbWeatherShimmer 1.5s infinite ease-in-out;
            content: '';
        }

        .kb-crop-shimmer-dark {
            position: relative;
            overflow: hidden;
        }
        .kb-crop-shimmer-dark::after {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            transform: translateX(-100%);
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(52, 211, 153, 0.12) 35%,
                rgba(167, 243, 208, 0.35) 50%,
                rgba(52, 211, 153, 0.12) 65%,
                rgba(255, 255, 255, 0) 100%
            );
            animation: kbWeatherShimmer 1.5s infinite ease-in-out;
            content: '';
        }

        /* Base Mandi Pill */
        .mandi-pill {
            background-color: #ffffff;
            border-color: #e7e5e4;
            color: #44403c;
            transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .mandi-pill:hover {
            background-color: #fafaf9;
            border-color: #d6d3d1;
        }
        .mandi-pill .mandi-pill-title {
            color: #44403c;
        }
        .mandi-pill .mandi-pill-dist {
            color: #a8a29e;
        }
        .mandi-pill .mandi-pill-price {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #d1fae5;
        }

        /* Active / Selected Mandi Pill - Guaranteed High Specificity */
        .mandi-pill.mandi-pill-active {
            background-color: #1C5A2C !important;
            border-color: #1C5A2C !important;
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(28, 90, 44, 0.38) !important;
        }
        .mandi-pill.mandi-pill-active .mandi-pill-star {
            color: #fcd34d !important;
            display: inline-block !important;
        }
        .mandi-pill.mandi-pill-active .mandi-pill-title {
            color: #ffffff !important;
        }
        .mandi-pill.mandi-pill-active .mandi-pill-dist {
            color: #a7f3d0 !important;
        }
        .mandi-pill.mandi-pill-active .mandi-pill-price {
            background-color: rgba(255, 255, 255, 0.24) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.45) !important;
        }</style>

    <!-- 2. Hero Showcase: Classic Two-Box Aligned Architecture with Responsive Mobile Flow -->
    <div class="grid grid-cols-1 lg:grid-cols-12 lg:grid-rows-[1fr_auto] gap-5 items-stretch">

        <!-- ========================================================================= -->
        <!-- ELEMENT 1: CROP SPECIMEN PHOTO CARD                                      -->
        <!-- Mobile: Order 1 | Desktop: Left Column Row 1 (Cols 1-5)                   -->
        <!-- ========================================================================= -->
        <div class="order-1 lg:order-none lg:col-span-5 lg:col-start-1 lg:row-start-1 bg-stone-900 rounded-3xl overflow-hidden border-2 border-[#D9CEB8] shadow-sm relative flex flex-col justify-between p-5 text-white min-h-[280px] sm:min-h-[320px] h-full">
            <!-- Full Height Image -->
            <img src="{{ $crop->photo_url }}" 
                 alt="{{ $crop->name }}" 
                 class="w-full h-full absolute inset-0 object-cover opacity-90">
            
            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-black/20"></div>

            <!-- Top Left Floating Badges -->
            <div class="relative z-10 flex items-center justify-between">
                @if($boardMeta)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full {{ $boardMeta['theme'] === 'coffee' ? 'bg-amber-950/90 text-amber-200 border-amber-800' : 'bg-emerald-950/90 text-emerald-200 border-emerald-800' }} backdrop-blur-md text-xs font-black shadow-sm border font-sans">
                        <span>{{ $boardMeta['icon'] }}</span>
                        <span>{{ $boardMeta['badge_en'] }}</span>
                        @if($activeLocale === 'kn')
                            <span class="text-[10px] opacity-90 font-kannada font-normal">• {{ $boardMeta['badge_kn'] }}</span>
                        @endif
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white text-stone-900 font-extrabold text-xs shadow-sm border border-stone-200/60 font-sans">
                        <span class="w-2 h-2 rounded-full bg-[#1C5A2C] animate-pulse"></span>
                        <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Reliable' : 'ಅಧಿಕೃತ' }}</span>
                        @if($activeLocale === 'kn')
                            <span class="text-[10px] text-stone-500 font-sans font-normal">• Reliable</span>
                        @endif
                    </span>
                @endif

                @if($crop->is_major)
                    <span class="px-2.5 py-0.5 rounded-full bg-amber-400 text-stone-950 font-black text-[10px] uppercase tracking-wider shadow-2xs font-sans">
                        Major Crop
                    </span>
                @endif
            </div>

            <!-- Bottom Left Crop Name & Details -->
            <div class="relative z-10 mt-auto space-y-1 text-white">
                <div class="text-[11px] font-black uppercase tracking-widest text-emerald-300 font-sans">
                    {{ strtoupper($crop->category ? ($activeLocale === 'en' ? $crop->category->name : ($crop->category->name_kn ?? $crop->category->name)) : 'COMMODITY') }}
                </div>
                @if($activeLocale === 'kn' && $crop->name_kn)
                    <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-white font-kannada drop-shadow-sm">
                        {{ $crop->name_kn }}
                    </h1>
                    <div class="text-base font-semibold text-emerald-200/90 font-sans">
                        {{ $crop->name }}
                    </div>
                @else
                    <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-white font-sans drop-shadow-sm">
                        {{ $crop->name }}
                    </h1>
                    @if($crop->name_kn)
                        <div class="text-base font-semibold text-amber-200/70 font-kannada">
                            {{ $crop->name_kn }}
                        </div>
                    @endif
                @endif
                <div class="pt-1 flex items-center gap-2 text-xs text-white/80 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span>{{ $activeLocale === 'en' ? 'Standard Unit:' : 'ಪ್ರಮಾಣಿತ ಘಟಕ:' }} <strong class="text-white font-bold">{{ $activeLocale === 'kn' ? ($crop->standard_unit === 'Quintal' ? 'ಕ್ವಿಂಟಾಲ್' : ($crop->standard_unit ?? 'ಕ್ವಿಂಟಾಲ್')) : ($crop->standard_unit ?? 'Quintal') }}</strong></span>
                    @if($crop->scientific_name)
                        <span>•</span>
                        <span class="italic text-white/70">{{ $crop->scientific_name }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- ELEMENT 2: BOX 1 (CURRENT PRICE, PICK YOUR GRADE, VIEW DIFFERENT MARKET) -->
        <!-- Mobile: Order 2 | Desktop: Right Column Rows 1-2 (Cols 6-12)              -->
        <!-- ========================================================================= -->
        <div class="order-2 lg:order-none lg:col-span-7 lg:col-start-6 lg:row-start-1 lg:row-span-2 bg-white rounded-3xl p-5 sm:p-7 border-2 border-[#D9CEB8] shadow-sm flex flex-col justify-between space-y-4 h-full">
            
            <!-- Top Content Section: Price & Grades with Zero Layout Shift Shimmer Overlay -->
            <div class="space-y-4 relative">
                
                <!-- Shimmer Glass Overlay (Exact Weather-Widget Shimmer Engine with Dynamic Multi-Row Grade Matching) -->
                <div x-show="isMarketLoading" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-250"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="absolute -inset-2 bg-white/98 backdrop-blur-xs z-20 flex flex-col justify-start rounded-2xl p-4 sm:p-5 pointer-events-none select-none"
                     aria-hidden="true">
                    
                    <div class="space-y-3.5 w-full">
                        <!-- Top Row: CURRENT PRICE label -->
                        <div class="flex items-center justify-between w-full">
                            <div class="h-3.5 w-24 bg-stone-200 rounded-md kb-crop-shimmer"></div>
                            <div class="h-5 w-24 bg-emerald-100/60 rounded-full kb-crop-shimmer"></div>
                        </div>

                        <!-- Price Row: Big Price + Unit + Trend Pill -->
                        <div class="flex items-baseline gap-3">
                            <div class="h-11 sm:h-12 w-40 sm:w-48 bg-stone-200 rounded-xl kb-crop-shimmer"></div>
                            <div class="h-5 w-20 bg-stone-200/80 rounded-md kb-crop-shimmer"></div>
                            <div class="h-5 w-24 bg-emerald-100/60 rounded-md kb-crop-shimmer"></div>
                        </div>

                        <!-- Market/Unit Details Row: Mandi name, Day range, Distance, As of Date -->
                        <div class="flex items-center gap-2 pt-0.5 flex-wrap">
                            <div class="h-4 w-36 bg-stone-200/90 rounded-md kb-crop-shimmer"></div>
                            <div class="h-4 w-28 bg-stone-200/70 rounded-md kb-crop-shimmer"></div>
                            <div class="h-5 w-24 bg-stone-200/70 rounded-full kb-crop-shimmer"></div>
                            <div class="h-4 w-20 bg-stone-200/60 rounded-md kb-crop-shimmer"></div>
                        </div>

                        <!-- Dynamic Pick Your Grade Skeleton (Matches exact 1, 2, or multi-row count) -->
                        <div class="pt-3 border-t border-stone-100 space-y-2" x-show="gradesList && gradesList.length > 0">
                            <!-- Single Grade Market Skeleton -->
                            <template x-if="gradesList && gradesList.length === 1">
                                <div class="inline-flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-emerald-50/70 border-2 border-emerald-300/40 kb-crop-shimmer">
                                    <div class="space-y-1.5">
                                        <div class="h-2.5 w-24 bg-stone-300/60 rounded-md"></div>
                                        <div class="h-4 w-20 bg-stone-300/80 rounded-md"></div>
                                    </div>
                                </div>
                            </template>

                            <!-- Multi-Grade Market Skeleton (1:1 mapping with active grades across single or multiple rows) -->
                            <template x-if="gradesList && gradesList.length > 1">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <div class="h-3 w-36 bg-stone-200/80 rounded-md kb-crop-shimmer"></div>
                                        <div class="h-3 w-28 bg-stone-100 rounded-md kb-crop-shimmer hidden sm:block"></div>
                                    </div>

                                    <div class="flex flex-wrap gap-2 text-xs">
                                        <template x-for="(g, idx) in (showAllGrades ? gradesList : gradesList.slice(0, Math.min(gradesList.length, 4)))" :key="'shim_g_' + idx">
                                            <div class="px-3.5 py-2 rounded-2xl border-2 kb-crop-shimmer flex flex-col items-start gap-1.5 min-w-[96px] sm:min-w-[108px]"
                                                 :class="isGradeSelected(g) ? 'bg-emerald-50/70 border-emerald-300/50' : 'bg-stone-50 border-stone-200/80'">
                                                <div class="h-2.5 w-16 bg-stone-300/60 rounded-md"></div>
                                                <div class="h-3.5 w-14 bg-stone-300/80 rounded-md"></div>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Shimmer for Show More/Fewer toggle button -->
                                    <template x-if="gradesList.length > 4">
                                        <div class="pt-0.5">
                                            <div class="h-3 w-32 bg-stone-200/60 rounded-md kb-crop-shimmer"></div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Live Data View (Maintains exact layout bounds with zero shift, clean cross-fade) -->
                <div :class="isMarketLoading ? 'opacity-0 select-none pointer-events-none' : 'opacity-100'" 
                     class="transition-opacity duration-200 space-y-4">
                
                <!-- 1. Current Price Section -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-extrabold tracking-wider text-stone-400 uppercase text-[11px] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'CURRENT PRICE' : 'ಇಂದಿನ ದರ' }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-300 text-emerald-900 font-bold text-[11px] sm:text-xs shadow-2xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                            <span class="text-emerald-700 font-semibold">{{ $activeLocale === 'en' ? 'Updated:' : 'ನವೀಕರಿಸಲಾಗಿದೆ:' }}</span>
                            <span class="font-black text-[#1C5A2C]" x-text="priceData.date_formatted">{{ ($activePriceItem?->price_date || $latestDate) ? \Carbon\Carbon::parse($activePriceItem->price_date ?? $latestDate)->format('d M Y') : '—' }}</span>
                        </span>
                    </div>

                    <div class="flex flex-wrap items-baseline gap-3">
                        <div class="text-4xl sm:text-5xl font-black text-stone-900 tracking-tight font-sans" x-text="priceData.modal_formatted">
                            {{ $displayModal > 0 ? '₹' . number_format($displayModal, 0) : '—' }}
                        </div>

                        <template x-if="priceData.per_kg_formatted">
                            <div class="text-base sm:text-lg font-bold text-stone-500 font-sans" x-text="priceData.per_kg_formatted">
                                ≈ ₹{{ $perKgPrice }}/kg
                            </div>
                        </template>
                        <template x-if="!priceData.per_kg_formatted">
                            <div class="text-sm font-semibold text-stone-400" x-text="'/ ' + priceData.unit_label">
                                / {{ $activeLocale === 'kn' ? ($crop->standard_unit === 'Quintal' ? 'ಕ್ವಿಂಟಾಲ್' : ($crop->standard_unit ?? 'ಕ್ವಿಂಟಾಲ್')) : strtolower($crop->standard_unit ?? 'quintal') }}
                            </div>
                        </template>

                        <template x-if="priceData.daily_trend === 'rise'">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-emerald-50 text-emerald-800 text-xs font-bold font-sans border border-emerald-200">
                                <span>↑</span>
                                <span x-text="'+₹' + Math.abs(Math.round(priceData.daily_change || 0))"></span>
                                <span class="text-[11px] font-semibold text-emerald-600" x-text="'(+' + Math.abs(priceData.daily_change_percent || 0) + '%)'"></span>
                            </span>
                        </template>
                        <template x-if="priceData.daily_trend === 'drop'">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-red-50 text-red-700 text-xs font-bold font-sans border border-red-200">
                                <span>↓</span>
                                <span x-text="'-₹' + Math.abs(Math.round(priceData.daily_change || 0))"></span>
                                <span class="text-[11px] font-semibold text-red-600" x-text="'(' + (priceData.daily_change_percent || 0) + '%)'"></span>
                            </span>
                        </template>
                        <template x-if="priceData.daily_trend === 'stable' && priceData.daily_change !== null">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-stone-100 text-stone-700 text-xs font-bold font-sans border border-stone-200">
                                <span>→</span>
                                <span>₹0</span>
                                <span class="text-[11px] font-semibold text-stone-500">({{ $activeLocale === 'en' ? 'Stable' : 'ಸ್ಥಿರ' }})</span>
                            </span>
                        </template>
                    </div>

                    <!-- Active Mandi / Centre Details Bar -->
                    <div class="flex flex-wrap items-center gap-2 text-xs text-stone-600 pt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span class="font-bold text-stone-800 uppercase" x-text="priceData.unit_label + ' • @ ' + (priceData.display_market_name || '').toUpperCase()">
                            {{ $activeLocale === 'kn' ? ($crop->standard_unit === 'Quintal' ? 'ಕ್ವಿಂಟಾಲ್' : ($crop->standard_unit ?? 'ಕ್ವಿಂಟಾಲ್')) : ($crop->standard_unit ?? 'Quintal') }} • @ {{ strtoupper($displayMarketName) }}
                        </span>

                        <template x-if="priceData.spread_formatted">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="text-stone-300">•</span>
                                <span class="text-stone-600 font-semibold" title="{{ $activeLocale === 'en' ? 'Day auction min-max range' : 'ದೈನಂದಿನ ಹರಾಜು ಕನಿಷ್ಠ-ಗರಿಷ್ಠ ವ್ಯಾಪ್ತಿ' }}">
                                    {{ $activeLocale === 'en' ? 'Day Range:' : 'ಶ್ರೇಣಿ:' }} <span x-text="priceData.spread_formatted"></span>
                                </span>
                            </span>
                        </template>

                        <template x-if="priceData.distance_km && priceData.is_nearest">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="text-stone-300">•</span>
                                <span class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full bg-[#fff4e5] text-[#9a5b00] border border-[#ffe0b2] text-[11px] font-extrabold whitespace-nowrap shadow-2xs leading-none"
                                      title="{{ $activeLocale === 'en' ? 'Closest mandi to your location' : 'ನಿಮ್ಮ ಸ್ಥಳಕ್ಕೆ ಅತ್ಯಂತ ಸಮೀಪದ ಮಾರುಕಟ್ಟೆ' }}">
                                    <span class="inline-flex items-center leading-none">📍</span>
                                    <span class="inline-flex items-center leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">{{ $activeLocale === 'en' ? 'nearest market' : 'ಹತ್ತಿರದ ಮಾರುಕಟ್ಟೆ' }} • <span x-text="Math.round(priceData.distance_km)"></span> km</span>
                                </span>
                            </span>
                        </template>
                        <template x-if="priceData.distance_km && !priceData.is_nearest">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="text-stone-300">•</span>
                                <span class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-[11px] font-bold whitespace-nowrap leading-none">
                                    <span class="inline-flex items-center leading-none">📍</span>
                                    <span class="inline-flex items-center leading-none"><span x-text="Math.round(priceData.distance_km)"></span> km {{ $activeLocale === 'en' ? 'away' : 'ದೂರ' }}</span>
                                </span>
                            </span>
                        </template>

                        <template x-if="priceData.as_of_text">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="text-stone-300">•</span>
                                <span class="text-stone-700 font-bold" title="{{ $activeLocale === 'en' ? 'Trading session date' : 'ವಹಿವಾಟು ನಡೆದ ದಿನಾಂಕ' }}">
                                    <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}" x-text="priceData.as_of_text">
                                        {{ $asOfText }}
                                    </span>
                                </span>
                            </span>
                        </template>

                        @if($boardMeta)
                            <span class="text-amber-900 font-bold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} text-[11px]">({{ $activeLocale === 'en' ? $boardMeta['badge_en'] : $boardMeta['badge_kn'] }})</span>
                        @endif
                    </div>
                </div>

                <!-- 2. "PICK YOUR GRADE" Section -->
                <div class="space-y-2 pt-2 border-t border-stone-100" x-show="gradesList && gradesList.length > 0">
                    <template x-if="gradesList && gradesList.length === 1">
                        <div class="inline-flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-[#1C5A2C] text-white shadow-xs border-2 border-[#1C5A2C]">
                            <div>
                                <div class="text-[11px] font-extrabold tracking-wide uppercase text-emerald-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}" x-text="gradesList[0].label"></div>
                                <div class="text-base font-black text-white font-sans tracking-tight" x-text="gradesList[0].modal_formatted"></div>
                            </div>
                        </div>
                    </template>

                    <template x-if="gradesList && gradesList.length > 1">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="text-[11px] font-extrabold text-stone-400 uppercase tracking-wider flex items-center gap-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                    <span>{{ $activeLocale === 'en' ? 'PICK YOUR GRADE' : 'ಗ್ರೇಡ್ ಆಯ್ಕೆಮಾಡಿ' }}</span>
                                    <span class="text-[10px] text-stone-500 font-bold" x-text="'(' + gradesList.length + ' {{ $activeLocale === 'en' ? 'VARIETIES' : 'ತಳಿಗಳು' }})'"></span>
                                </div>
                                <span class="text-[11px] text-stone-500 font-medium hidden sm:inline">
                                    {{ $activeLocale === 'en' ? 'Active Market Trades' : 'ಸಕ್ರಿಯ ಮಾರುಕಟ್ಟೆ ವಹಿವಾಟು' }}
                                </span>
                            </div>

                            <div class="flex flex-wrap gap-2 text-xs">
                                <template x-for="(g, idx) in gradesList" :key="g.variety_id + '_' + (g.grade || '')">
                                    <a :href="g.url"
                                       @click.prevent="switchGradeAsync(g, g.url)"
                                       data-no-loader="true"
                                       x-show="showAllGrades || (idx < 4 || isGradeSelected(g))"
                                       :class="isGradeSelected(g) ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-sm' : 'bg-white text-stone-700 hover:bg-stone-50 border-stone-200'"
                                       class="px-3.5 py-2 rounded-2xl font-bold transition border-2 flex flex-col items-start gap-0.5 cursor-pointer tap-feedback active:scale-95">
                                        <span class="text-[11px]" :class="isGradeSelected(g) ? 'text-white' : 'text-stone-600'" x-text="g.label"></span>
                                        <span class="text-sm font-black font-sans" :class="isGradeSelected(g) ? 'text-emerald-200' : 'text-[#1C5A2C]'" x-text="g.modal_formatted"></span>
                                    </a>
                                </template>
                            </div>

                            <template x-if="gradesList.length > 4">
                                <div class="pt-0.5">
                                    <button type="button" 
                                            @click="showAllGrades = !showAllGrades" 
                                            class="text-[11px] font-bold text-[#1C5A2C] hover:underline transition inline-flex items-center gap-1 cursor-pointer">
                                        <span x-text="showAllGrades ? '▲ {{ $activeLocale === 'en' ? 'Show fewer grades' : 'ಕಡಿಮೆ ತಳಿಗಳನ್ನು ತೋರಿಸಿ' }}' : '+ {{ $activeLocale === 'en' ? 'Show' : 'ತೋರಿಸಿ' }} ' + (gradesList.length - 4) + ' {{ $activeLocale === 'en' ? 'more grades' : 'ಹೆಚ್ಚಿನ ತಳಿಗಳು' }} ▾'"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
            </div>

            <!-- 3. "VIEW DIFFERENT MARKET / CENTRE" -->
            <div class="space-y-2.5 pt-2 border-t border-stone-100">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="text-[11px] font-extrabold text-stone-400 uppercase tracking-wider {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            @if($boardMeta)
                                {{ $activeLocale === 'en' ? 'VIEW DIFFERENT CENTRE' : $boardMeta['centre_label_kn'] }}
                            @else
                                {{ $activeLocale === 'en' ? 'VIEW DIFFERENT MARKET (All Mandis)' : 'ಮಾರುಕಟ್ಟೆ ಬದಲಿಸಿ (ಕರ್ನಾಟಕ ಮಂಡಿಗಳು)' }}
                            @endif
                            @if(!empty($marketRadiusKm))
                                <span class="text-[10px] font-semibold text-stone-400 normal-case">({{ $marketRadiusKm }} km)</span>
                            @endif
                        </div>

                        @if(!empty($allowUserSortToggle))
                            <div class="inline-flex items-center bg-stone-100 p-0.5 rounded-lg border border-stone-200 text-[10px] font-bold">
                                <button type="button" 
                                        @click="activeSort = 'nearest_first'; $nextTick(() => scrollToSelectedMandi())"
                                        :class="activeSort === 'nearest_first' ? 'bg-white text-emerald-800 shadow-2xs' : 'text-stone-500 hover:text-stone-800'"
                                        class="px-2 py-0.5 rounded transition flex items-center gap-1 cursor-pointer">
                                    <span>📍</span>
                                    <span>{{ $activeLocale === 'en' ? 'Nearest' : 'ಹತ್ತಿರ' }}</span>
                                </button>
                                <button type="button" 
                                        @click="activeSort = 'highest_price_first'; $nextTick(() => scrollToSelectedMandi())"
                                        :class="activeSort === 'highest_price_first' ? 'bg-white text-emerald-800 shadow-2xs' : 'text-stone-500 hover:text-stone-800'"
                                        class="px-2 py-0.5 rounded transition flex items-center gap-1 cursor-pointer">
                                    <span>🔥</span>
                                    <span>{{ $activeLocale === 'en' ? 'Top Rate' : 'ಹೆಚ್ಚಿನ ಬೆಲೆ' }}</span>
                                </button>
                            </div>
                        @endif
                    </div>

                    <template x-if="isMarketExplicitlySelected">
                        <button type="button" 
                                data-no-loader="true"
                                @click="resetMarketAsync(resetUrl)"
                                class="text-xs text-stone-500 hover:text-stone-800 font-bold flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-stone-100 hover:bg-stone-200 border border-stone-200 transition cursor-pointer" 
                                title="{{ $activeLocale === 'en' ? 'Reset to nearest market' : 'ಹತ್ತಿರದ ಮಾರುಕಟ್ಟೆಗೆ ಮರುಹೊಂದಿಸಿ' }}">
                            <span>✕</span>
                            <span>{{ $activeLocale === 'en' ? 'Reset' : 'ಮರುಹೊಂದಿಸಿ' }}</span>
                        </button>
                    </template>
                </div>

                <!-- Mandi Pills with Scrollbar -->
                @php
                    $beyondRadiusCount = 0;
                @endphp
                <div x-ref="mandiScrollBox" class="flex flex-wrap gap-2 text-xs max-h-56 overflow-y-auto pr-1 custom-mandi-scroll">
                    @foreach($availableMarkets as $am)
                        @php
                            $isMktSelected = ($selectedMarket && $selectedMarket->id === $am->id);
                            $amTitle = $activeLocale === 'en' ? $am->name : ($am->name_kn ?? $am->name);
                            $isWithin = !empty($am->is_within_radius);
                            if (!$isWithin && !$isMktSelected) {
                                $beyondRadiusCount++;
                            }
                        @endphp
                        <a href="{{ route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'market' => $am->name])) }}"
                           data-no-loader="true"
                           @click.prevent="switchMarketAsync('{{ $am->name }}', {{ $am->id }}, $el.href)"
                           x-show="showAllRadius || {{ ($isWithin || $isMktSelected) ? 'true' : 'false' }}"
                           :style="activeSort === 'highest_price_first' ? 'order: {{ $am->price_rank ?? 999 }}' : 'order: {{ $am->distance_rank ?? 999 }}'"
                           :class="isMandiSelected({{ $am->id }}) ? 'mandi-pill-active ring-2 ring-emerald-600/40' : ''"
                           class="mandi-pill inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full font-bold transition border-2 cursor-pointer tap-feedback active:scale-95">
                            <template x-if="isMandiSelected({{ $am->id }})">
                                <span class="mandi-pill-star text-amber-300">★</span>
                            </template>
                            <span class="mandi-pill-title uppercase text-[12px] font-extrabold tracking-wide {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $amTitle }}</span>
                            @if(isset($am->distance_km) && $am->distance_km < 1000)
                                <span class="mandi-pill-dist text-[10px] font-medium">({{ round($am->distance_km) }}km)</span>
                            @endif

                            @if(!empty($enableSmartBadges))
                                <template x-if="!isMandiSelected({{ $am->id }})">
                                    <span class="inline-flex items-center gap-1">
                                        @if(!empty($am->is_nearest))
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-sky-100 text-sky-800 border border-sky-200">
                                                📍 {{ $activeLocale === 'en' ? 'Nearest' : 'ಹತ್ತಿರ' }}
                                            </span>
                                        @endif
                                        @if(!empty($am->is_top_rate))
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-200">
                                                🔥 {{ $activeLocale === 'en' ? 'Top Rate' : 'ಅತ್ಯಧಿಕ' }}
                                            </span>
                                        @endif
                                    </span>
                                </template>
                            @endif

                            @if(isset($am->today_modal_price) && $am->today_modal_price > 0)
                                <span class="mandi-pill-price px-2 py-0.5 rounded-md text-[11px] font-black font-sans">
                                    ₹{{ number_format($am->today_modal_price, 0) }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>

                @if($beyondRadiusCount > 0)
                    <div class="pt-0.5">
                        <button type="button" 
                                @click="showAllRadius = !showAllRadius; $nextTick(() => scrollToSelectedMandi())" 
                                class="text-[11px] font-bold text-[#1C5A2C] hover:underline transition inline-flex items-center gap-1 cursor-pointer">
                            <span x-text="showAllRadius ? '▲ {{ $activeLocale === 'en' ? 'Hide distant mandis beyond' : 'ದೂರದ ಮಂಡಿಗಳನ್ನು ಮರೆಮಾಡಿ' }} {{ $marketRadiusKm }} km' : '+ {{ $activeLocale === 'en' ? 'Show' : 'ತೋರಿಸಿ' }} {{ $beyondRadiusCount }} {{ $activeLocale === 'en' ? 'more mandis beyond' : 'ಹೆಚ್ಚಿನ ಮಂಡಿಗಳು' }} {{ $marketRadiusKm }} km ▾'"></span>
                        </button>
                    </div>
                @endif
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- ELEMENT 3: BOX 2 (ADVISORY & ACTION BUTTONS)                              -->
        <!-- Mobile: Order 3 (Directly Below Box 1) | Desktop: Left Column Row 2       -->
        <!-- ========================================================================= -->
        <div class="order-3 lg:order-none lg:col-span-5 lg:col-start-1 lg:row-start-2 bg-white rounded-2xl sm:rounded-3xl border-2 border-[#D9CEB8] shadow-sm overflow-hidden">

            <!-- Market Advisory / Sentiment Banner -->
            <div id="crop-advisory-wrapper" class="relative overflow-hidden">
                <div id="crop-advisory-content" :class="isMarketLoading ? 'opacity-25 select-none transition-opacity duration-150' : 'opacity-100 transition-opacity duration-150'">
                    @include('farmer.crops.partials.advisory_banner')
                </div>

                <!-- Shimmer Glass Overlay (Exact Weather-Widget Shimmer Engine) -->
                <div x-show="isMarketLoading"
                     x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-250"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="absolute inset-0 bg-white/95 backdrop-blur-xs z-20 flex items-start gap-2.5 px-3.5 py-2.5 sm:px-4 sm:py-3 pointer-events-none select-none"
                     aria-hidden="true">
                    <!-- Accent stripe skeleton -->
                    <div class="w-1 self-stretch rounded-full bg-stone-300 kb-crop-shimmer shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <!-- Icon & Title skeleton -->
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 rounded-md bg-stone-200 kb-crop-shimmer"></div>
                            <div class="w-48 sm:w-60 h-4 rounded-md bg-stone-200 kb-crop-shimmer"></div>
                        </div>
                        <!-- Advisory text skeleton -->
                        <div class="w-full h-3 rounded-md bg-stone-200/80 kb-crop-shimmer"></div>
                        <div class="w-4/5 h-3 rounded-md bg-stone-200/60 kb-crop-shimmer"></div>
                        <!-- Forecast pills skeleton -->
                        <div class="flex items-center gap-2 pt-1">
                            <div class="w-28 h-6 rounded-full bg-emerald-100/70 border border-emerald-200/50 kb-crop-shimmer"></div>
                            <div class="w-24 h-6 rounded-full bg-slate-200/70 border border-slate-300/50 kb-crop-shimmer"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="h-px bg-[#EAE3D2]"></div>

            <!-- Action Buttons: WhatsApp Share & Where to Sell Simulator -->
            @php
                $cleanDisplayMarket = preg_replace('/\s+APMC$/i', '', $displayMarketName);
                $sharePriceText = "🌾 *" . ($activeLocale === 'en' ? 'Krushi Baandhava — ' : 'ಕೃಷಿ ಬಾಂಧವ — ') . ($activeLocale === 'en' ? $crop->name : ($crop->name_kn ?: $crop->name)) . "*\n"
                    . "📍 " . ($boardMeta ? ($activeLocale === 'en' ? 'Centre: ' : 'ಕೇಂದ್ರ: ') : ($activeLocale === 'en' ? 'Market: ' : 'ಮಾರುಕಟ್ಟೆ: ')) . $cleanDisplayMarket . "\n"
                    . "💰 " . ($activeLocale === 'en' ? "Today's Modal Rate: ₹" : 'ಇಂದಿನ ಮಾದರಿ ದರ: ₹') . number_format($displayModal, 0) . " / " . ($activeLocale === 'en' ? ($crop->standard_unit ?? 'Quintal') : ($crop->standard_unit === 'Quintal' ? 'ಕ್ವಿಂಟಾಲ್' : ($crop->standard_unit ?? 'ಕ್ವಿಂಟಾಲ್'))) . "\n"
                    . ($perKgPrice ? ($activeLocale === 'en' ? "⚖️ Approx per kg: ≈ ₹" : "⚖️ ಪ್ರತಿ ಕೆ.ಜಿ ಗೆ: ≈ ₹") . $perKgPrice . "/kg\n" : "")
                    . "📅 " . ($activeLocale === 'en' ? 'Date: ' : 'ದಿನಾಂಕ: ') . $stats['date_formatted'] . "\n"
                    . "👉 " . ($activeLocale === 'en' ? 'View Full Rate & Forecast: ' : 'ಸಂಪೂರ್ಣ ದರ & ಮುನ್ಸೂಚನೆ ವೀಕ್ಷಿಸಿ: ') . url()->current();
                $whatsappDetailUrl = "https://wa.me/?text=" . rawurlencode($sharePriceText);
            @endphp

            <div class="grid grid-cols-2 p-2 sm:p-2.5 gap-2">
                <!-- WhatsApp Share Button -->
                <a href="{{ $whatsappDetailUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="flex items-center justify-center gap-1.5 py-2 sm:py-2.5 px-2.5 rounded-xl sm:rounded-2xl bg-[#25D366] hover:bg-[#1db954] active:scale-95 text-white font-extrabold text-xs shadow-2xs transition-all cursor-pointer">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.118 1.524 5.847L.057 23.882a.5.5 0 00.613.612l6.101-1.463A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.9a9.866 9.866 0 01-5.03-1.378l-.36-.214-3.733.896.927-3.63-.235-.374A9.867 9.867 0 012.1 12c0-5.464 4.436-9.9 9.9-9.9 5.464 0 9.9 4.436 9.9 9.9 0 5.464-4.436 9.9-9.9 9.9z"/>
                    </svg>
                    <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Share Price' : 'ದರ ಶೇರ್ ಮಾಡಿ' }}</span>
                </a>

                <!-- Where to Sell Button -->
                @php
                    $whereToSellParams = array_filter([
                        'crop' => $crop->slug,
                        'variety_id' => $activeVarietyId ?? ($activePriceItem?->variety_id ?? null),
                        'market_id' => $selectedMarket?->id,
                        'district_id' => $selectedMarket?->district_id ?? ($userDistrict?->id ?? null),
                        'from_crop' => 1,
                    ]);
                @endphp
                <a :href="whereToSellUrl"
                   href="{{ route('farmer.decision.where-to-sell', $whereToSellParams) }}"
                   class="flex items-center justify-center gap-1.5 py-2 sm:py-2.5 px-2.5 rounded-xl sm:rounded-2xl bg-[#1C5A2C] hover:bg-[#154622] active:scale-95 text-white font-extrabold text-xs shadow-2xs transition-all cursor-pointer"
                   title="{{ $activeLocale === 'en' ? 'Simulate take-home profit for ' . ($crop->name) . ' across Karnataka mandis' : 'ಕರ್ನಾಟಕದ ಮಂಡಿಗಳಲ್ಲಿ ' . ($crop->name_kn ?: $crop->name) . ' ಬೆಳೆಯ ನಿವ್ವಳ ಲಾಭವನ್ನು ಲೆಕ್ಕಹಾಕಿ' }}">
                    <span class="text-xs sm:text-sm leading-none">⚖️</span>
                    <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Where to Sell?' : 'ಎಲ್ಲಿ ಮಾರಾಟ?' }}</span>
                    <span class="text-xs opacity-80">&rarr;</span>
                </a>
            </div>

        </div>


    </div>

    <!-- 3. 4-Metric State Overview Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Highest Rate -->
        <div class="bg-white p-4 rounded-2xl border border-[#E8DFC8] shadow-2xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} block">
                {{ $activeLocale === 'en' ? ($boardMeta ? 'Highest Rate' : 'State Highest Rate') : ($boardMeta ? 'ಅತ್ಯಧಿಕ ದರ' : 'ರಾಜ್ಯದ ಗರಿಷ್ಠ ದರ') }}
            </span>
            <div class="text-xl sm:text-2xl font-black text-emerald-950 mt-1 font-sans">
                {{ $stats['highest_modal'] > 0 ? '₹' . number_format($stats['highest_modal'], 0) : '—' }}
            </div>
            <div class="text-xs font-semibold text-emerald-700 truncate mt-0.5 font-sans">
                {{ preg_replace('/\s+APMC$/i', '', $stats['highest_market']) }}
            </div>
        </div>

        <!-- Lowest Rate -->
        <div class="bg-white p-4 rounded-2xl border border-[#E8DFC8] shadow-2xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} block">
                {{ $activeLocale === 'en' ? 'Lowest Rate' : 'ಕನಿಷ್ಠ ದರ' }}
            </span>
            <div class="text-xl sm:text-2xl font-black text-stone-800 mt-1 font-sans">
                {{ $stats['lowest_modal'] > 0 ? '₹' . number_format($stats['lowest_modal'], 0) : '—' }}
            </div>
            <div class="text-xs font-semibold text-stone-500 truncate mt-0.5 font-sans">
                {{ preg_replace('/\s+APMC$/i', '', $stats['lowest_market']) }}
            </div>
        </div>

        <!-- State/Board Average -->
        <div class="bg-white p-4 rounded-2xl border border-[#E8DFC8] shadow-2xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} block">
                {{ $activeLocale === 'en' ? ($boardMeta ? 'Board Average' : 'State Average') : ($boardMeta ? 'ಮಂಡಳಿ ಸರಾಸರಿ' : 'ರಾಜ್ಯ ಸರಾಸರಿ') }}
            </span>
            <div class="text-xl sm:text-2xl font-black text-stone-900 mt-1 font-sans">
                {{ $stats['avg_modal'] > 0 ? '₹' . number_format($stats['avg_modal'], 0) : '—' }}
            </div>
            <div class="text-xs font-semibold text-stone-500 mt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $stats['total_mandis'] }} {{ $activeLocale === 'en' ? ($boardMeta ? 'centres' : 'mandis') : ($boardMeta ? 'ಕೇಂದ್ರಗಳಿಂದ' : 'ಮಂಡಿಗಳಿಂದ') }}
            </div>
        </div>

        <!-- Arrivals -->
        <div class="bg-white p-4 rounded-2xl border border-[#E8DFC8] shadow-2xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} block">
                {{ $activeLocale === 'en' ? 'Total Arrivals' : 'ಒಟ್ಟು ಆವಕ' }}
            </span>
            <div class="text-xl sm:text-2xl font-black text-stone-900 mt-1 font-sans">
                {{ number_format($stats['total_arrivals'], 1) }}
            </div>
            <div class="text-xs font-semibold text-stone-500 mt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Quintals' : 'ಕ್ವಿಂಟಾಲ್' }}
            </div>
        </div>
    </div>

    <!-- 4. "What's next" Forecast Horizons (Dynamic Async with Shimmer Overlay) -->
    <div id="crop-forecast-wrapper" class="relative">
        <div id="crop-forecast-content" :class="isMarketLoading ? 'opacity-35 select-none transition-opacity duration-200' : 'opacity-100 transition-opacity duration-200'">
            @include('farmer.crops.partials.forecast_card')
        </div>
        <div x-show="isMarketLoading" x-cloak 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-out duration-250"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-white/95 backdrop-blur-md z-30 flex flex-col justify-start rounded-2xl sm:rounded-3xl p-5 sm:p-6 pointer-events-none select-none space-y-4 shadow-sm"
             aria-hidden="true">
            
            <!-- Skeleton Forecast Header -->
            <div class="flex items-center justify-between pb-3 border-b-2 border-[#F0EAE1]">
                <div class="flex items-center gap-2.5">
                    <div class="w-1.5 h-8 rounded-full bg-[#1C5A2C]"></div>
                    <div class="space-y-1.5">
                        <div class="h-5 w-52 rounded-md bg-stone-200 kb-crop-shimmer"></div>
                        <div class="h-3.5 w-64 rounded-md bg-stone-200/60 kb-crop-shimmer"></div>
                    </div>
                </div>
                <div class="h-6 w-28 rounded-full bg-emerald-100/80 border border-emerald-200/60 kb-crop-shimmer"></div>
            </div>

            <!-- 4 Horizon Card Skeletons -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3.5">
                <div class="p-3.5 rounded-2xl border-2 border-stone-200/80 bg-[#FAF6EE] space-y-3">
                    <div class="flex justify-between items-center">
                        <div class="h-4 w-16 rounded-md bg-stone-200 kb-crop-shimmer"></div>
                        <div class="h-4 w-12 rounded-full bg-emerald-100 kb-crop-shimmer"></div>
                    </div>
                    <div class="h-8 w-28 rounded-xl bg-stone-200 kb-crop-shimmer"></div>
                    <div class="h-2 w-full rounded-full bg-stone-200 kb-crop-shimmer"></div>
                </div>
                <div class="p-3.5 rounded-2xl border-2 border-stone-200/80 bg-[#FAF6EE] space-y-3">
                    <div class="flex justify-between items-center">
                        <div class="h-4 w-16 rounded-md bg-stone-200 kb-crop-shimmer"></div>
                        <div class="h-4 w-12 rounded-full bg-emerald-100 kb-crop-shimmer"></div>
                    </div>
                    <div class="h-8 w-28 rounded-xl bg-stone-200 kb-crop-shimmer"></div>
                    <div class="h-2 w-full rounded-full bg-stone-200 kb-crop-shimmer"></div>
                </div>
                <div class="p-3.5 rounded-2xl border-2 border-stone-200/80 bg-[#FAF6EE] space-y-3">
                    <div class="flex justify-between items-center">
                        <div class="h-4 w-16 rounded-md bg-stone-200 kb-crop-shimmer"></div>
                        <div class="h-4 w-12 rounded-full bg-emerald-100 kb-crop-shimmer"></div>
                    </div>
                    <div class="h-8 w-28 rounded-xl bg-stone-200 kb-crop-shimmer"></div>
                    <div class="h-2 w-full rounded-full bg-stone-200 kb-crop-shimmer"></div>
                </div>
                <div class="p-3.5 rounded-2xl border-2 border-stone-200/80 bg-[#FAF6EE] space-y-3">
                    <div class="flex justify-between items-center">
                        <div class="h-4 w-16 rounded-md bg-stone-200 kb-crop-shimmer"></div>
                        <div class="h-4 w-12 rounded-full bg-emerald-100 kb-crop-shimmer"></div>
                    </div>
                    <div class="h-8 w-28 rounded-xl bg-stone-200 kb-crop-shimmer"></div>
                    <div class="h-2 w-full rounded-full bg-stone-200 kb-crop-shimmer"></div>
                </div>
            </div>

            <!-- Skeleton Disclaimer bar -->
            <div class="h-9 w-full rounded-xl bg-stone-100 border border-stone-200 kb-crop-shimmer"></div>
        </div>
    </div>

    <!-- 5. Best Months to Sell — Krushi Harvest Calendar (Dynamic Async with Shimmer Overlay) -->
    <div id="crop-seasonal-wrapper" class="relative">
        <div id="crop-seasonal-content" :class="isMarketLoading ? 'opacity-35 select-none transition-opacity duration-200' : 'opacity-100 transition-opacity duration-200'">
            @include('farmer.crops.partials.seasonal_card')
        </div>
        <div x-show="isMarketLoading" x-cloak 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-out duration-250"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 rounded-3xl p-5 sm:p-6 text-white z-30 flex flex-col justify-start pointer-events-none select-none space-y-4 shadow-xl"
             style="background: linear-gradient(148deg,#081A0F 0%,#0F2A1A 38%,#1A4428 65%,#0D2318 100%); border: 1px solid rgba(255,255,255,0.09);"
             aria-hidden="true">
            
            <!-- Skeleton Header -->
            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                <div class="space-y-1.5">
                    <div class="h-3 w-28 rounded-md bg-emerald-400/20 kb-crop-shimmer-dark"></div>
                    <div class="h-6 w-56 rounded-md bg-white/15 kb-crop-shimmer-dark"></div>
                </div>
                <div class="h-6 w-24 rounded-full bg-white/10 border border-white/10 kb-crop-shimmer-dark"></div>
            </div>

            <!-- Skeleton Month Chips -->
            <div class="flex gap-2">
                <div class="h-7 w-24 rounded-xl bg-amber-500/20 border border-amber-500/30 kb-crop-shimmer-dark"></div>
                <div class="h-7 w-24 rounded-xl bg-amber-500/20 border border-amber-500/30 kb-crop-shimmer-dark"></div>
            </div>

            <!-- Skeleton 12-Month Bar Chart Tracks -->
            <div class="flex items-end gap-2 h-28 pt-4 pb-2 px-2 border-b border-white/5">
                <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end">
                    <div class="w-full rounded-t-md bg-emerald-500/20 kb-crop-shimmer-dark" style="height: 45%;"></div>
                    <div class="h-2.5 w-4 rounded bg-white/10 kb-crop-shimmer-dark"></div>
                </div>
                <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end">
                    <div class="w-full rounded-t-md bg-emerald-500/20 kb-crop-shimmer-dark" style="height: 55%;"></div>
                    <div class="h-2.5 w-4 rounded bg-white/10 kb-crop-shimmer-dark"></div>
                </div>
                <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end">
                    <div class="w-full rounded-t-md bg-emerald-500/20 kb-crop-shimmer-dark" style="height: 70%;"></div>
                    <div class="h-2.5 w-4 rounded bg-white/10 kb-crop-shimmer-dark"></div>
                </div>
                <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end">
                    <div class="w-full rounded-t-md bg-amber-400/30 kb-crop-shimmer-dark" style="height: 92%;"></div>
                    <div class="h-2.5 w-4 rounded bg-white/10 kb-crop-shimmer-dark"></div>
                </div>
                <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end">
                    <div class="w-full rounded-t-md bg-amber-400/35 kb-crop-shimmer-dark" style="height: 100%;"></div>
                    <div class="h-2.5 w-4 rounded bg-white/10 kb-crop-shimmer-dark"></div>
                </div>
                <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end">
                    <div class="w-full rounded-t-md bg-amber-400/30 kb-crop-shimmer-dark" style="height: 85%;"></div>
                    <div class="h-2.5 w-4 rounded bg-white/10 kb-crop-shimmer-dark"></div>
                </div>
                <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end">
                    <div class="w-full rounded-t-md bg-emerald-500/20 kb-crop-shimmer-dark" style="height: 60%;"></div>
                    <div class="h-2.5 w-4 rounded bg-white/10 kb-crop-shimmer-dark"></div>
                </div>
                <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end">
                    <div class="w-full rounded-t-md bg-emerald-500/20 kb-crop-shimmer-dark" style="height: 50%;"></div>
                    <div class="h-2.5 w-4 rounded bg-white/10 kb-crop-shimmer-dark"></div>
                </div>
                <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end">
                    <div class="w-full rounded-t-md bg-rose-500/20 kb-crop-shimmer-dark" style="height: 40%;"></div>
                    <div class="h-2.5 w-4 rounded bg-white/10 kb-crop-shimmer-dark"></div>
                </div>
                <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end">
                    <div class="w-full rounded-t-md bg-rose-500/20 kb-crop-shimmer-dark" style="height: 35%;"></div>
                    <div class="h-2.5 w-4 rounded bg-white/10 kb-crop-shimmer-dark"></div>
                </div>
                <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end">
                    <div class="w-full rounded-t-md bg-emerald-500/20 kb-crop-shimmer-dark" style="height: 42%;"></div>
                    <div class="h-2.5 w-4 rounded bg-white/10 kb-crop-shimmer-dark"></div>
                </div>
                <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end">
                    <div class="w-full rounded-t-md bg-emerald-500/20 kb-crop-shimmer-dark" style="height: 50%;"></div>
                    <div class="h-2.5 w-4 rounded bg-white/10 kb-crop-shimmer-dark"></div>
                </div>
            </div>

            <!-- Skeleton Legend -->
            <div class="flex justify-between items-center pt-1 text-xs">
                <div class="h-3.5 w-40 rounded-md bg-emerald-400/20 kb-crop-shimmer-dark"></div>
                <div class="h-3.5 w-32 rounded-md bg-white/10 kb-crop-shimmer-dark"></div>
            </div>
        </div>
    </div>

    <!-- 6. Historical Analytics & Interactive Price Trends -->
    @php
        $sumArrivals = !empty($dailyTrends['arrivals']) ? array_sum(array_filter($dailyTrends['arrivals'], fn($v) => is_numeric($v) && $v > 0)) : 0;
        $trendDir = $statisticalSummary['trend_direction'] ?? 'stable';
        $changePct = (float) ($statisticalSummary['price_change_percent'] ?? 0);
        $firstPrice = (float) ($statisticalSummary['first_price'] ?? 0);
        $lastPrice = (float) ($statisticalSummary['last_price'] ?? 0);
        $avgPrice = (float) ($statisticalSummary['avg_price'] ?? 0);
        $minPrice = (float) ($statisticalSummary['min_price'] ?? 0);
        $maxPrice = (float) ($statisticalSummary['max_price'] ?? 0);
        $priceSpread = max(0, $maxPrice - $minPrice);
        $volRating = $statisticalSummary['volatility_rating'] ?? 'ಕಡಿಮೆ (Low)';
        $volColor = $statisticalSummary['volatility_color'] ?? 'emerald';
        $volPercent = $statisticalSummary['volatility_percent'] ?? 0;
        $displayVolRating = $activeLocale === 'en'
            ? ($statisticalSummary['volatility_rating_en'] ?? 'Stable / Low Volatility')
            : ($statisticalSummary['volatility_rating_kn'] ?? $volRating);

        $initialChartData = [
            'labels' => $dailyTrends['labels'] ?? [],
            'modalPrices' => $dailyTrends['modal_prices'] ?? [],
            'minPrices' => $dailyTrends['min_prices'] ?? [],
            'maxPrices' => $dailyTrends['max_prices'] ?? [],
            'arrivals' => $dailyTrends['arrivals'] ?? [],
            'has_data' => !empty($dailyTrends['has_data']),
            'locale' => $activeLocale,
        ];

        $initialInsightText = '';
        if ($activeLocale === 'en') {
            if ($trendDir === 'up') {
                $initialInsightText = "Over the last {$rangeDays} days, modal rates rose from <strong>₹" . number_format($firstPrice) . "</strong> to <strong>₹" . number_format($lastPrice) . "</strong> <strong>(+{$changePct}%)</strong>. Market demand remains strong with favorable selling momentum.";
            } elseif ($trendDir === 'down') {
                $initialInsightText = "Over the last {$rangeDays} days, modal rates softened from <strong>₹" . number_format($firstPrice) . "</strong> to <strong>₹" . number_format($lastPrice) . "</strong> <strong>(-" . abs($changePct) . "%)</strong>. Local arrivals may be elevated; check the price forecast before committing volume.";
            } else {
                $initialInsightText = "Over the last {$rangeDays} days, prices held steady with an average of <strong>₹" . number_format($avgPrice) . "/quintal</strong>. Trading spread between high and low is <strong>₹" . number_format($priceSpread) . "</strong>.";
            }
        } else {
            if ($trendDir === 'up') {
                $initialInsightText = "ಕಳೆದ {$rangeDays} ದಿನಗಳಲ್ಲಿ ಬೆಲೆಯು <strong>₹" . number_format($firstPrice) . "</strong> ರಿಂದ <strong>₹" . number_format($lastPrice) . "</strong> ಕ್ಕೆ <strong>(+{$changePct}%) ಏರಿಕೆಯಾಗಿದೆ</strong>. ಮಾರುಕಟ್ಟೆಯಲ್ಲಿ ಬೇಡಿಕೆ ಉತ್ತಮವಾಗಿದ್ದು ಮಾರಾಟಕ್ಕೆ ಅನುಕೂಲಕರ ಪ್ರವೃತ್ತಿಯಿದೆ.";
            } elseif ($trendDir === 'down') {
                $initialInsightText = "ಕಳೆದ {$rangeDays} ದಿನಗಳಲ್ಲಿ ಬೆಲೆಯು <strong>₹" . number_format($firstPrice) . "</strong> ರಿಂದ <strong>₹" . number_format($lastPrice) . "</strong> ಕ್ಕೆ <strong>(-" . abs($changePct) . "%) ಇಳಿಕೆಯಾಗಿದೆ</strong>. ಸ್ಥಳೀಯ ಆವಕ ಹೆಚ್ಚಾಗಿರಬಹುದು, ಬೆಲೆ ಮುನ್ಸೂಚನೆ ಗಮನಿಸಿ ಮಾರಾಟ ನಿರ್ಧರಿಸಿ.";
            } else {
                $initialInsightText = "ಕಳೆದ {$rangeDays} ದಿನಗಳಲ್ಲಿ ದರವು ಸರಾಸರಿ <strong>₹" . number_format($avgPrice) . "/ಕ್ವಿಂಟಾಲ್</strong> ನೊಂದಿಗೆ ಸ್ಥಿರವಾಗಿದೆ. ಗರಿಷ್ಠ ಮತ್ತು ಕನಿಷ್ಠ ದರದ ಅಂತರ <strong>₹" . number_format($priceSpread) . "</strong> ಆಗಿದೆ.";
            }
        }

        $initialMetrics = [
            'max_price' => $maxPrice,
            'min_price' => $minPrice,
            'avg_price' => $avgPrice,
            'price_spread' => $priceSpread,
            'diff_high_avg' => max(0, $maxPrice - $avgPrice),
            'diff_avg_low' => max(0, $avgPrice - $minPrice),
            'observations_count' => $statisticalSummary['observations_count'] ?? count($dailyTrends['labels'] ?? []),
            'trend_dir' => $trendDir,
            'change_pct' => $changePct,
            'abs_change_pct' => abs($changePct),
            'vol_rating' => $displayVolRating,
            'vol_percent' => $volPercent,
            'vol_color' => $volColor,
            'sum_arrivals' => $sumArrivals,
            'insight_text' => $initialInsightText,
        ];
    @endphp

    <script>
        /**
         * Alpine.js Reactive Component for Seamless AJAX Historical Price Trends & Shimmer Effects
         */
        function historicalPriceTrend(config) {
            const rangeMap = {
                '7d': 7,
                '15d': 15,
                '30d': 30,
                '90d': 90,
                '365d': 365,
                '1y': 365
            };

            return {
                cropSlug: config.cropSlug || '',
                marketId: config.marketId || '',
                marketName: config.marketName || @json(preg_replace('/\s+APMC$/i', '', $displayMarketName)),
                varietyId: config.varietyId || '',
                activeRange: config.activeRange || '30d',
                activeLocale: config.activeLocale || 'kn',
                standardUnit: config.standardUnit || 'Quintal',
                rangeDays: rangeMap[config.activeRange] || 30,
                isLoading: false,
                metrics: config.initialMetrics || {},
                chartData: config.initialChartData || {},

                init() {
                    this.renderChart();
                    window.addEventListener('market-changed', (e) => {
                        if (e.detail) {
                            this.marketId = e.detail.marketId || '';
                            this.marketName = (e.detail.marketName || '').replace(/\s+APMC$/i, '');
                            this.selectRange(this.activeRange, true);
                        }
                    });
                },

                renderChart() {
                    let attempts = 0;
                    const tryRender = () => {
                        if (this.chartData && this.chartData.has_data && typeof window.initPriceTrendChart === 'function') {
                            window.initPriceTrendChart('priceTrendCanvas', this.chartData);
                        } else if (attempts < 25) {
                            attempts++;
                            setTimeout(tryRender, 80);
                        }
                    };
                    this.$nextTick(() => {
                        tryRender();
                    });
                },

                formatCurrency(val) {
                    if (val === null || val === undefined || isNaN(val) || val <= 0) return '—';
                    return '₹' + Math.round(Number(val)).toLocaleString('en-IN');
                },

                formatNumber(val) {
                    if (val === null || val === undefined || isNaN(val) || val <= 0) return '0';
                    return Math.round(Number(val)).toLocaleString('en-IN');
                },

                async selectRange(rangeKey, force = false) {
                    if ((this.activeRange === rangeKey && !force) || this.isLoading) return;

                    this.activeRange = rangeKey;
                    this.rangeDays = rangeMap[rangeKey] || 30;
                    this.isLoading = true;

                    try {
                        const ajaxUrl = new URL('{{ route('farmer.crops.trend-ajax', ['slug' => $crop->slug]) }}', window.location.origin);
                        ajaxUrl.searchParams.set('range', rangeKey);
                        if (this.marketId) ajaxUrl.searchParams.set('market_id', this.marketId);
                        if (this.varietyId) ajaxUrl.searchParams.set('variety', this.varietyId);
                        ajaxUrl.searchParams.set('lang', this.activeLocale);

                        const res = await fetch(ajaxUrl.toString(), {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const data = await res.json();

                        if (data && data.success) {
                            this.metrics = data.metrics || {};
                            this.chartData = data.chart_data || {};
                            this.$nextTick(() => {
                                this.renderChart();
                            });
                        }
                    } catch (err) {
                        console.error('[HistoricalPriceTrend] Failed to load trend data:', err);
                    } finally {
                        setTimeout(() => {
                            this.isLoading = false;
                        }, 120);
                    }
                }
            };
        }
        window.priceTrendInitialConfig = {
            cropSlug: @json($crop->slug),
            marketId: @json($selectedMarket?->id ?? ''),
            marketName: @json(preg_replace('/\s+APMC$/i', '', $displayMarketName)),
            varietyId: @json($activeVarietyId ?? ''),
            activeRange: @json($rangeParam),
            activeLocale: @json($activeLocale),
            standardUnit: @json($crop->standard_unit ?? 'Quintal'),
            initialChartData: @json($initialChartData),
            initialMetrics: @json($initialMetrics)
        };
        window.historicalPriceTrend = historicalPriceTrend;
        if (window.Alpine) {
            window.Alpine.data('historicalPriceTrend', historicalPriceTrend);
        } else {
            document.addEventListener('alpine:init', () => {
                window.Alpine.data('historicalPriceTrend', historicalPriceTrend);
            });
        }
    </script>

    <div x-data="historicalPriceTrend(window.priceTrendInitialConfig)"
        x-init="init()"
        class="bg-white border-2 border-[#D9CEB8] rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 shadow-sm space-y-3 sm:space-y-4 relative overflow-hidden transition-all duration-300">

        <!-- Subtle Ambient Background Accent -->
        <div class="absolute -top-24 -right-24 w-72 h-72 bg-[#1C5A2C]/5 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Section Header Row -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-2.5 sm:gap-3 relative z-10 pb-2.5 sm:pb-3 border-b border-[#F0EAE1]">
            <div class="flex items-start gap-2.5">
                <span class="w-1.5 h-8 sm:h-9 rounded-full bg-[#1C5A2C] shrink-0 mt-0.5"></span>
                <div>
                    <div class="flex items-center gap-2 sm:gap-2.5 flex-wrap">
                        <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'Price Trend & Market History' : 'ದರ ಪ್ರವೃತ್ತಿ & ಮಾರುಕಟ್ಟೆ ಇತಿಹಾಸ' }}
                        </h2>

                        <!-- Dynamic Trend Momentum Pill (Reactive via Alpine) -->
                        <template x-if="isLoading">
                            <span class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full text-[10.5px] bg-stone-100 border border-stone-200 text-stone-400 animate-pulse font-sans leading-none">
                                <span class="w-1.5 h-1.5 rounded-full bg-stone-300 shrink-0"></span>
                                <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'Updating...' : 'ನವೀಕರಿಸಲಾಗುತ್ತಿದೆ...' }}</span>
                            </span>
                        </template>
                        <template x-if="!isLoading">
                            <span>
                                <template x-if="metrics.trend_dir === 'up'">
                                    <span class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] sm:text-[11px] font-black bg-emerald-50 text-[#16803C] border border-emerald-300 shadow-2xs leading-none">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#16803C] animate-pulse shrink-0"></span>
                                        <span class="inline-flex items-center leading-none font-sans">▲ +<span x-text="metrics.abs_change_pct"></span>%</span>
                                        <span class="inline-flex items-center leading-none {{ $activeLocale === 'kn' ? 'font-kannada text-[10px]' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Rising' : 'ಏರಿಕೆ' }}</span>
                                    </span>
                                </template>
                                <template x-if="metrics.trend_dir === 'down'">
                                    <span class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] sm:text-[11px] font-black bg-rose-50 text-[#C0392B] border border-rose-300 shadow-2xs leading-none">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#C0392B] animate-pulse shrink-0"></span>
                                        <span class="inline-flex items-center leading-none font-sans">▼ -<span x-text="metrics.abs_change_pct"></span>%</span>
                                        <span class="inline-flex items-center leading-none {{ $activeLocale === 'kn' ? 'font-kannada text-[10px]' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Falling' : 'ಇಳಿಕೆ' }}</span>
                                    </span>
                                </template>
                                <template x-if="metrics.trend_dir === 'stable'">
                                    <span class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] sm:text-[11px] font-black bg-amber-50 text-[#B45309] border border-amber-300 shadow-2xs leading-none">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                        <span class="inline-flex items-center leading-none font-sans">⟷ 0%</span>
                                        <span class="inline-flex items-center leading-none {{ $activeLocale === 'kn' ? 'font-kannada text-[10px]' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Stable' : 'ಸ್ಥಿರ' }}</span>
                                    </span>
                                </template>
                            </span>
                        </template>
                    </div>

                    <div class="flex items-center gap-1.5 mt-0.5 text-xs text-stone-500 font-medium {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex-wrap">
                        <template x-if="marketId && marketName">
                            <span class="font-bold text-stone-700">
                                📍 <span x-text="marketName"></span>
                            </span>
                        </template>
                        <template x-if="!marketId || !marketName">
                            <span class="font-bold text-stone-700">
                                🌐 {{ $activeLocale === 'en' ? ($boardMeta ? 'Karnataka Board Average' : 'Karnataka State Average') : ('ಕರ್ನಾಟಕ ' . ($boardMeta ? 'ಮಂಡಳಿ' : 'ರಾಜ್ಯ') . ' ಸರಾಸರಿ') }}
                            </span>
                        </template>
                        <span>
                            — <span x-text="rangeDays"></span>{{ $activeLocale === 'en' ? '-day modal auctions & arrival volume' : ' ದಿನಗಳ ಹರಾಜು ದರಗಳು & ಆವಕ ದಾಖಲೆ' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Timeframe Filter Chips with Smooth AJAX Toggle (No Full Page Reload) -->
            <div class="flex items-center gap-1 bg-[#F4EFE6] p-1 rounded-xl border border-[#D9CEB8] self-start lg:self-auto font-sans shadow-2xs overflow-x-auto no-scrollbar">
                @php
                    $ranges = [
                        '7d' => ['kn' => '7 ದಿನ', 'en' => '7D', 'full_en' => '7 Days'],
                        '15d' => ['kn' => '15 ದಿನ', 'en' => '15D', 'full_en' => '15 Days'],
                        '30d' => ['kn' => '30 ದಿನ', 'en' => '30D', 'full_en' => '30 Days'],
                        '90d' => ['kn' => '3 ತಿಂಗಳು', 'en' => '90D', 'full_en' => '3 Months'],
                        '365d' => ['kn' => '1 ವರ್ಷ', 'en' => '1Y', 'full_en' => '1 Year'],
                    ];
                @endphp
                @foreach($ranges as $rKey => $rMeta)
                    <button type="button"
                            @click="selectRange('{{ $rKey }}')"
                            :class="activeRange === '{{ $rKey }}' ? 'bg-[#1C5A2C] text-white shadow-xs font-black' : 'text-stone-700 hover:text-stone-950 hover:bg-white/90 font-bold'"
                            class="px-2.5 sm:px-3 py-1 rounded-lg transition-all flex items-center gap-1 text-[11px] sm:text-xs whitespace-nowrap cursor-pointer tap-feedback active:scale-95">
                        <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? $rMeta['en'] : $rMeta['kn'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- 4 Classic Metric Intelligence Cards in High-Contrast Compact Grid with Shimmer Skeletons -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5">
            <!-- 1. Period High -->
            <div class="bg-[#FAF8F5] rounded-xl p-2.5 sm:p-3 border-2 border-[#E5DECE] hover:border-[#16803C] shadow-2xs transition-all relative overflow-hidden group flex flex-col justify-between">
                <div class="absolute top-0 left-0 right-0 h-1 bg-[#16803C]"></div>
                <!-- Shimmer Overlay during AJAX fetch -->
                <div x-show="isLoading" class="absolute inset-0 bg-[#FAF8F5]/85 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity">
                    <div class="w-full h-full p-2.5 space-y-2 animate-pulse">
                        <div class="h-3 w-16 bg-stone-200 rounded"></div>
                        <div class="h-5 w-24 bg-stone-200 rounded"></div>
                        <div class="h-2.5 w-20 bg-stone-200 rounded"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[9.5px] sm:text-[10.5px] font-black uppercase tracking-wider text-[#16803C] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                            <span class="text-[11px] leading-none">▲</span>
                            <span>{{ $activeLocale === 'en' ? 'Period High' : 'ಅವಧಿಯ ಗರಿಷ್ಠ' }}</span>
                        </span>
                        <span class="w-1.5 h-1.5 rounded-full bg-[#16803C]"></span>
                    </div>
                    <div class="flex items-baseline gap-1 font-sans">
                        <span class="text-lg sm:text-xl font-black text-stone-950 tracking-tight" x-text="formatCurrency(metrics.max_price)">
                            {{ $maxPrice > 0 ? '₹' . number_format($maxPrice, 0) : '—' }}
                        </span>
                        <span class="text-[10px] font-bold text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">/{{ $activeLocale === 'en' ? strtolower($crop->standard_unit ?? 'quintal') : 'ಕ್ವಿಂ' }}</span>
                    </div>
                </div>
                <div class="pt-1.5 border-t border-[#EAE3D2] mt-1.5">
                    <template x-if="metrics.max_price > 0 && metrics.avg_price > 0">
                        <div class="text-[10px] sm:text-[10.5px] font-bold text-[#16803C] flex items-center gap-1 font-sans">
                            <span class="font-black">+₹<span x-text="formatNumber(metrics.diff_high_avg)"></span></span>
                            <span class="text-stone-400 font-semibold {{ $activeLocale === 'kn' ? 'font-kannada text-[9.5px]' : '' }}">{{ $activeLocale === 'en' ? 'above average' : 'ಸರಾಸರಿಗಿಂತ ಹೆಚ್ಚು' }}</span>
                        </div>
                    </template>
                    <template x-if="!(metrics.max_price > 0 && metrics.avg_price > 0)">
                        <div class="text-[10px] text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Peak recorded rate' : 'ಗರಿಷ್ಠ ದಾಖಲಾದ ದರ' }}</div>
                    </template>
                </div>
            </div>

            <!-- 2. Period Low -->
            <div class="bg-[#FAF8F5] rounded-xl p-2.5 sm:p-3 border-2 border-[#E5DECE] hover:border-[#C0392B] shadow-2xs transition-all relative overflow-hidden group flex flex-col justify-between">
                <div class="absolute top-0 left-0 right-0 h-1 bg-[#C0392B]"></div>
                <!-- Shimmer Overlay -->
                <div x-show="isLoading" class="absolute inset-0 bg-[#FAF8F5]/85 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity">
                    <div class="w-full h-full p-2.5 space-y-2 animate-pulse">
                        <div class="h-3 w-16 bg-stone-200 rounded"></div>
                        <div class="h-5 w-24 bg-stone-200 rounded"></div>
                        <div class="h-2.5 w-20 bg-stone-200 rounded"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[9.5px] sm:text-[10.5px] font-black uppercase tracking-wider text-[#C0392B] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                            <span class="text-[11px] leading-none">▼</span>
                            <span>{{ $activeLocale === 'en' ? 'Period Low' : 'ಅವಧಿಯ ಕನಿಷ್ಠ' }}</span>
                        </span>
                        <span class="w-1.5 h-1.5 rounded-full bg-[#C0392B]"></span>
                    </div>
                    <div class="flex items-baseline gap-1 font-sans">
                        <span class="text-lg sm:text-xl font-black text-stone-950 tracking-tight" x-text="formatCurrency(metrics.min_price)">
                            {{ $minPrice > 0 ? '₹' . number_format($minPrice, 0) : '—' }}
                        </span>
                        <span class="text-[10px] font-bold text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">/{{ $activeLocale === 'en' ? strtolower($crop->standard_unit ?? 'quintal') : 'ಕ್ವಿಂ' }}</span>
                    </div>
                </div>
                <div class="pt-1.5 border-t border-[#EAE3D2] mt-1.5">
                    <template x-if="metrics.min_price > 0 && metrics.avg_price > 0">
                        <div class="text-[10px] sm:text-[10.5px] font-bold text-[#C0392B] flex items-center gap-1 font-sans">
                            <span class="font-black">-₹<span x-text="formatNumber(metrics.diff_avg_low)"></span></span>
                            <span class="text-stone-400 font-semibold {{ $activeLocale === 'kn' ? 'font-kannada text-[9.5px]' : '' }}">{{ $activeLocale === 'en' ? 'below average' : 'ಸರಾಸರಿಗಿಂತ ಕಡಿಮೆ' }}</span>
                        </div>
                    </template>
                    <template x-if="!(metrics.min_price > 0 && metrics.avg_price > 0)">
                        <div class="text-[10px] text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Lowest recorded rate' : 'ಕನಿಷ್ಠ ದಾಖಲಾದ ದರ' }}</div>
                    </template>
                </div>
            </div>

            <!-- 3. Period Average -->
            <div class="bg-[#FAF8F5] rounded-xl p-2.5 sm:p-3 border-2 border-[#E5DECE] hover:border-[#1C5A2C] shadow-2xs transition-all relative overflow-hidden group flex flex-col justify-between">
                <div class="absolute top-0 left-0 right-0 h-1 bg-[#1C5A2C]"></div>
                <!-- Shimmer Overlay -->
                <div x-show="isLoading" class="absolute inset-0 bg-[#FAF8F5]/85 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity">
                    <div class="w-full h-full p-2.5 space-y-2 animate-pulse">
                        <div class="h-3 w-16 bg-stone-200 rounded"></div>
                        <div class="h-5 w-24 bg-stone-200 rounded"></div>
                        <div class="h-2.5 w-20 bg-stone-200 rounded"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[9.5px] sm:text-[10.5px] font-black uppercase tracking-wider text-stone-700 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                            <span class="text-[11px] leading-none">⚖️</span>
                            <span>{{ $activeLocale === 'en' ? 'Period Average' : 'ಅವಧಿಯ ಸರಾಸರಿ' }}</span>
                        </span>
                        <span class="w-1.5 h-1.5 rounded-full bg-[#1C5A2C]"></span>
                    </div>
                    <div class="flex items-baseline gap-1 font-sans">
                        <span class="text-lg sm:text-xl font-black text-stone-950 tracking-tight" x-text="formatCurrency(metrics.avg_price)">
                            {{ $avgPrice > 0 ? '₹' . number_format($avgPrice, 0) : '—' }}
                        </span>
                        <span class="text-[10px] font-bold text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">/{{ $activeLocale === 'en' ? strtolower($crop->standard_unit ?? 'quintal') : 'ಕ್ವಿಂ' }}</span>
                    </div>
                </div>
                <div class="pt-1.5 border-t border-[#EAE3D2] mt-1.5">
                    <div class="text-[10px] sm:text-[10.5px] font-bold text-stone-600 flex items-center gap-1 font-sans">
                        <span class="font-black text-stone-900" x-text="metrics.observations_count"></span>
                        <span class="text-stone-400 font-semibold {{ $activeLocale === 'kn' ? 'font-kannada text-[9.5px]' : '' }}">{{ $activeLocale === 'en' ? 'days logged' : 'ದಿನಗಳ ದಾಖಲೆ' }}</span>
                    </div>
                </div>
            </div>

            <!-- 4. Volatility & Spread -->
            <div class="bg-[#FAF8F5] rounded-xl p-2.5 sm:p-3 border-2 border-[#E5DECE] hover:border-stone-400 shadow-2xs transition-all relative overflow-hidden group flex flex-col justify-between">
                <div class="absolute top-0 left-0 right-0 h-1"
                     :class="metrics.vol_color === 'rose' ? 'bg-[#C0392B]' : (metrics.vol_color === 'amber' ? 'bg-amber-600' : 'bg-[#16803C]')"></div>
                <!-- Shimmer Overlay -->
                <div x-show="isLoading" class="absolute inset-0 bg-[#FAF8F5]/85 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity">
                    <div class="w-full h-full p-2.5 space-y-2 animate-pulse">
                        <div class="h-3 w-16 bg-stone-200 rounded"></div>
                        <div class="h-5 w-24 bg-stone-200 rounded"></div>
                        <div class="h-2.5 w-20 bg-stone-200 rounded"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[9.5px] sm:text-[10.5px] font-black uppercase tracking-wider text-stone-700 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full"
                                  :class="metrics.vol_color === 'rose' ? 'bg-[#C0392B]' : (metrics.vol_color === 'amber' ? 'bg-amber-600' : 'bg-[#16803C]')"></span>
                            <span>{{ $activeLocale === 'en' ? 'Volatility' : 'ಏರಿಳಿತ' }}</span>
                        </span>
                        <span class="text-[9.5px] font-black font-sans px-1 py-0.2 rounded bg-[#E5DECE] text-stone-700">
                            <span x-text="metrics.vol_percent"></span>%
                        </span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-xs sm:text-sm font-black text-stone-950 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} tracking-tight line-clamp-1 leading-snug"
                              x-text="metrics.vol_rating">
                            {{ $displayVolRating }}
                        </span>
                    </div>
                </div>
                <div class="pt-1.5 border-t border-[#EAE3D2] mt-1.5">
                    <div class="text-[10px] sm:text-[10.5px] font-bold text-stone-600 flex items-center gap-1 font-sans">
                        <span class="text-stone-400 font-semibold {{ $activeLocale === 'kn' ? 'font-kannada text-[9.5px]' : '' }}">{{ $activeLocale === 'en' ? 'Spread:' : 'ಅಂತರ:' }}</span>
                        <span class="font-black text-stone-900" x-text="formatCurrency(metrics.price_spread)"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actionable Farmer Market Intelligence Callout Strip -->
        <div x-show="chartData.has_data" class="bg-[#FAF6EE] border-2 border-[#D9CEB8] rounded-xl sm:rounded-2xl p-2.5 sm:p-3.5 flex flex-col md:flex-row md:items-center justify-between gap-2.5 shadow-2xs relative overflow-hidden {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <!-- Shimmer Bar -->
            <div x-show="isLoading" class="absolute inset-0 bg-[#FAF6EE]/90 backdrop-blur-[1px] flex items-center px-4 z-10 transition-opacity">
                <div class="w-full space-y-2 animate-pulse">
                    <div class="h-3 w-32 bg-amber-200/80 rounded"></div>
                    <div class="h-3 w-3/4 bg-amber-200/50 rounded"></div>
                </div>
            </div>
            <div class="flex items-start gap-2 sm:gap-2.5">
                <div class="w-6 h-6 rounded-lg bg-amber-100 border border-amber-300 flex items-center justify-center shrink-0 text-xs shadow-2xs">
                    💡
                </div>
                <div class="text-xs text-stone-800 leading-snug space-y-0.5">
                    <div class="font-black text-stone-950 flex items-center gap-1.5 text-[11.5px] sm:text-xs">
                        <span>{{ $activeLocale === 'en' ? 'Market Movement Analysis' : 'ದರ ಪ್ರವೃತ್ತಿ ವಿಶ್ಲೇಷಣೆ' }}</span>
                    </div>
                    <p class="text-[11px] sm:text-[11.5px] text-stone-700 font-medium" x-html="metrics.insight_text">
                        @if($activeLocale === 'en')
                            @if($trendDir === 'up')
                                Over the last {{ $rangeDays }} days, modal rates rose from <strong>₹{{ number_format($firstPrice) }}</strong> to <strong>₹{{ number_format($lastPrice) }}</strong> <strong>(+{{ abs($changePct) }}%)</strong>. Market demand remains strong with favorable selling momentum.
                            @elseif($trendDir === 'down')
                                Over the last {{ $rangeDays }} days, modal rates softened from <strong>₹{{ number_format($firstPrice) }}</strong> to <strong>₹{{ number_format($lastPrice) }}</strong> <strong>(-{{ abs($changePct) }}%)</strong>. Local arrivals may be elevated; check the price forecast before committing volume.
                            @else
                                Over the last {{ $rangeDays }} days, prices held steady with an average of <strong>₹{{ number_format($avgPrice) }}/{{ strtolower($crop->standard_unit ?? 'quintal') }}</strong>. Trading spread between high and low is <strong>₹{{ number_format($priceSpread) }}</strong>.
                            @endif
                        @else
                            @if($trendDir === 'up')
                                ಕಳೆದ {{ $rangeDays }} ದಿನಗಳಲ್ಲಿ ಬೆಲೆಯು <strong>₹{{ number_format($firstPrice) }}</strong> ರಿಂದ <strong>₹{{ number_format($lastPrice) }}</strong> ಕ್ಕೆ <strong>(+{{ abs($changePct) }}%) ಏರಿಕೆಯಾಗಿದೆ</strong>. ಮಾರುಕಟ್ಟೆಯಲ್ಲಿ ಬೇಡಿಕೆ ಉತ್ತಮವಾಗಿದ್ದು ಮಾರಾಟಕ್ಕೆ ಅನುಕೂಲಕರ ಪ್ರವೃತ್ತಿಯಿದೆ.
                            @elseif($trendDir === 'down')
                                ಕಳೆದ {{ $rangeDays }} ದಿನಗಳಲ್ಲಿ ಬೆಲೆಯು <strong>₹{{ number_format($firstPrice) }}</strong> ರಿಂದ <strong>₹{{ number_format($lastPrice) }}</strong> ಕ್ಕೆ <strong>(-{{ abs($changePct) }}%) ಇಳಿಕೆಯಾಗಿದೆ</strong>. ಸ್ಥಳೀಯ ಆವಕ ಹೆಚ್ಚಾಗಿರಬಹುದು, ಬೆಲೆ ಮುನ್ಸೂಚನೆ ಗಮನಿಸಿ ಮಾರಾಟ ನಿರ್ಧರಿಸಿ.
                            @else
                                ಕಳೆದ {{ $rangeDays }} ದಿನಗಳಲ್ಲಿ ದರವು ಸರಾಸರಿ <strong>₹{{ number_format($avgPrice) }}/ಕ್ವಿಂಟಾಲ್</strong> ನೊಂದಿಗೆ ಸ್ಥಿರವಾಗಿದೆ. ಗರಿಷ್ಠ ಮತ್ತು ಕನಿಷ್ಠ ದರದ ಅಂತರ <strong>₹{{ number_format($priceSpread) }}</strong> ಆಗಿದೆ.
                            @endif
                        @endif
                    </p>
                </div>
            </div>
            <div x-show="metrics.sum_arrivals > 0" class="shrink-0 flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border-2 border-[#D9CEB8] text-[10.5px] sm:text-[11px] font-extrabold text-stone-800 self-start md:self-auto shadow-2xs font-sans">
                <span class="text-[#1C5A2C]">📦</span>
                <span>{{ $activeLocale === 'en' ? 'Arrivals: ' : 'ಆವಕ: ' }}<strong><span x-text="formatNumber(metrics.sum_arrivals)"></span> {{ $activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂ' }}</strong></span>
            </div>
        </div>

        <!-- Chart Canvas Container with Modern Classic Framing -->
        <div class="rounded-xl sm:rounded-2xl bg-[#FAF8F5] border-2 border-[#E5DECE] p-2.5 sm:p-3.5 space-y-2.5 relative">
            <!-- Toolbar above Chart -->
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs px-1">
                <div class="flex items-center gap-3.5 flex-wrap">
                    <div class="flex items-center gap-1.5 text-stone-900 font-extrabold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span class="w-3 h-1.5 rounded-full bg-[#16803C] inline-block"></span>
                        @if($activeLocale === 'en')
                            <span>Modal Rate (₹)</span>
                        @else
                            <span>ಮಾದರಿ ದರ (₹)</span>
                        @endif
                    </div>
                    <div x-show="metrics.sum_arrivals > 0" class="flex items-center gap-1.5 text-stone-600 font-bold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span class="w-2.5 h-2.5 rounded-xs bg-slate-300 inline-block border border-slate-400/50"></span>
                        @if($activeLocale === 'en')
                            <span>Daily Arrivals (Qtl)</span>
                        @else
                            <span>ದೈನಂದಿನ ಆವಕ (ಕ್ವಿಂಟಾಲ್)</span>
                        @endif
                    </div>
                </div>
                <div class="text-[10.5px] font-semibold text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                    <span>👆 {{ $activeLocale === 'en' ? 'Hover or tap chart points for details' : 'ಗ್ರಾಫ್ ಮೇಲೆ ಸ್ಪರ್ಶಿಸಿ ವಿವರ ನೋಡಿ' }}</span>
                </div>
            </div>

            <!-- Canvas Wrapper (Compact Height: h-64 sm:h-72 md:h-80) with Subtle Shimmer Loader -->
            <div class="relative w-full h-64 sm:h-72 md:h-80 bg-white rounded-xl p-2 border border-[#EAE3D2] shadow-2xs overflow-hidden">
                <!-- Loading Shimmer Effect for Chart -->
                <div x-show="isLoading" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="absolute inset-0 bg-white/80 backdrop-blur-[2px] z-20 flex flex-col items-center justify-center p-6 space-y-3">
                    <div class="w-10 h-10 rounded-full border-3 border-[#1C5A2C]/20 border-t-[#1C5A2C] animate-spin"></div>
                    <div class="text-xs font-bold text-stone-600 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'Updating price trends...' : 'ದರ ಇತಿಹಾಸ ನವೀಕರಿಸಲಾಗುತ್ತಿದೆ...' }}
                    </div>
                </div>

                <div x-show="chartData.has_data" class="w-full h-full">
                    <canvas id="priceTrendCanvas"></canvas>
                </div>

                <div x-show="!chartData.has_data && !isLoading" class="h-full flex flex-col items-center justify-center text-center p-6 text-stone-400">
                    <span class="text-4xl mb-2">📊</span>
                    <span class="font-bold text-stone-700 text-sm {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'Insufficient price history recorded for this period' : 'ಈ ಅವಧಿಗೆ ಸಾಕಷ್ಟು ದರ ಇತಿಹಾಸ ದಾಖಲಾಗಿಲ್ಲ' }}
                    </span>
                    <span class="text-xs text-stone-400 mt-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} max-w-sm">
                        {{ $activeLocale === 'en' ? 'As more trading days are recorded by mandis, trend and arrival charts will activate automatically.' : 'ಮಾರುಕಟ್ಟೆಯಲ್ಲಿ ಹೆಚ್ಚಿನ ದಿನಗಳ ವಹಿವಾಟು ದಾಖಲಾದಂತೆ ಪ್ರವೃತ್ತಿ ಮತ್ತು ಆವಕ ನಕ್ಷೆ ಸಕ್ರಿಯಗೊಳ್ಳುತ್ತದೆ.' }}
                    </span>
                </div>
            </div>

            <!-- Chart Footer Meta -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 text-[10.5px] text-stone-500 px-1 pt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} border-t border-[#EAE3D2]">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="font-medium">{{ $activeLocale === 'en' ? '🟢 Green curve: Modal rate (₹/Qtl)' : '🟢 ಹಸಿರು ಗೆರೆ: ದರ (₹/ಕ್ವಿಂಟಾಲ್)' }}</span>
                    <template x-if="metrics.sum_arrivals > 0">
                        <span class="inline-flex items-center gap-1.5">
                            <span>&bull;</span>
                            <span class="font-medium">{{ $activeLocale === 'en' ? '🩶 Grey bars: Daily arrival volume' : '🩶 ಬೂದು ಬಾರ್: ಮಾರುಕಟ್ಟೆ ಆವಕ' }}</span>
                        </span>
                    </template>
                </div>
                <div class="text-stone-400 font-sans text-[10px] font-semibold">
                    {{ $activeLocale === 'en' ? 'Source: Mandi Daily Ingestion / Agmarknet Karnataka' : 'ಮೂಲ: ಮಂಡಿ ದೈನಂದಿನ ದರ / Agmarknet Karnataka' }}
                </div>
            </div>
        </div>
    </div>



    <!-- 7. Mandi / Board Rates Comparison List (Ranked Highest to Lowest) -->
    <div class="space-y-3 sm:space-y-4" x-data="{ showAllMandis: false }">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="w-1.5 h-8 sm:h-9 rounded-full {{ $boardMeta ? ($boardMeta['theme'] === 'coffee' ? 'bg-amber-800' : 'bg-emerald-700') : 'bg-[#1C5A2C]' }} shrink-0 mt-0.5"></span>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-xl sm:text-2xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            @if($boardMeta)
                                {{ $activeLocale === 'en' ? $boardMeta['rates_heading_en'] : $boardMeta['rates_heading_kn'] }}
                            @else
                                {{ $activeLocale === 'en' ? 'Where to Sell Today? — Mandi Rates' : 'ಇಂದು ಎಲ್ಲಿ ಮಾರಬೇಕು? — ಮಂಡಿ ದರಗಳು' }}
                            @endif
                        </h2>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-stone-500 font-medium mt-0.5 flex-wrap {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        @if($boardMeta)
                            <span>{{ $boardMeta['authority'] }} {{ $activeLocale === 'en' ? 'Official Centres Near You' : 'ನಿಮ್ಮ ಹತ್ತಿರದ ಅಧಿಕೃತ ಕೇಂದ್ರಗಳ ದರ ಹೋಲಿಕೆ' }}</span>
                        @else
                            <span>{{ $activeLocale === 'en' ? 'Ranked by highest modal price near your location' : 'ನಿಮ್ಮ ಸಮೀಪದ ಮಾರುಕಟ್ಟೆಗಳಲ್ಲಿ ಇಂದಿನ ಗರಿಷ್ಠ ದರಗಳ ಆಧಾರದಲ್ಲಿ' }}</span>
                            @if(isset($displayMarketName))
                                <span x-data="{ currentMarketDisplay: '{{ $displayMarketName }}' }"
                                      x-init="window.addEventListener('market-changed', (e) => { if (e.detail && e.detail.marketName) currentMarketDisplay = e.detail.marketName; })"
                                      class="inline-flex items-center gap-1">
                                    <span class="text-stone-300">•</span>
                                    <span class="inline-flex items-center gap-1 font-bold text-stone-700 bg-[#FAF8F5] px-2 py-0.5 rounded-md border border-[#D9CEB8] text-[11px]">
                                        📍 {{ $activeLocale === 'en' ? 'Currently: ' : 'ಪ್ರಸ್ತುತ: ' }}<span x-text="currentMarketDisplay">{{ $displayMarketName }}</span>
                                    </span>
                                </span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            <span class="inline-flex items-center justify-center leading-none text-xs font-bold text-stone-700 bg-[#FAF8F5] px-3.5 py-1.5 rounded-full border border-[#DDD2BE] shadow-2xs shrink-0 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span class="inline-flex items-center leading-none">📅 {{ $activeLocale === 'en' ? 'Date: ' : 'ದಿನಾಂಕ: ' }}{{ $stats['date_formatted'] }}</span>
            </span>
        </div>

        @php
            $mandiGroups = $mandiGroups ?? ($mandiPrices->isNotEmpty() ? $mandiPrices->groupBy('market_id')->map(function ($prices) {
                $bestRecord = $prices->sortByDesc('modal_price')->first();
                return (object) [
                    'market' => $bestRecord->market,
                    'best_item' => $bestRecord,
                    'best_modal' => (float) $bestRecord->modal_price,
                    'total_arrivals' => (float) $prices->sum('arrival_quantity'),
                    'arrival_unit' => $bestRecord->arrival_unit ?? 'Qtl',
                    'unit' => $bestRecord->unit ?? 'Quintal',
                    'price_date' => $bestRecord->price_date,
                    'dataSource' => $bestRecord->dataSource,
                    'varieties' => $prices->sortByDesc('modal_price')->values(),
                    'variety_count' => $prices->count(),
                    'distance_km' => $bestRecord->market->distance_km ?? 9999,
                    'is_same_district' => $bestRecord->market->is_same_district ?? false,
                ];
            })->sortByDesc('best_modal')->values() : collect());

            $nearestTwoGroups = $nearestTwoGroups ?? $mandiGroups->take(2);
            $allOtherMandiGroups = $allOtherMandiGroups ?? $mandiGroups->slice(2);
        @endphp

        @if($mandiGroups->isEmpty())
            <div class="bg-gradient-to-br from-[#FAF8F5] to-white rounded-3xl p-8 text-center border-2 border-[#DDD2BE] shadow-xs space-y-2">
                <div class="text-3xl">{{ $boardMeta ? $boardMeta['icon'] : '🌾' }}</div>
                <div class="font-extrabold text-stone-800 text-base font-kannada">
                    {{ $activeLocale === 'en' ? 'No recent mandi prices available' : 'ಇತ್ತೀಚಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು ಲಭ್ಯವಿಲ್ಲ' }}
                </div>
                <p class="text-xs text-stone-500 font-kannada">
                    @if($boardMeta)
                        {{ $activeLocale === 'en' ? 'Rates not yet published by official centres within the active freshness period.' : 'ಪ್ರಸ್ತುತ ನಿಗದಿತ ಅವಧಿಯಲ್ಲಿ ' . $boardMeta['badge_kn'] . ' ಅಧಿಕೃತ ಕೇಂದ್ರಗಳಿಂದ ದರ ಮಾಹಿತಿ ಪ್ರಕಟವಾಗಿಲ್ಲ.' }}
                    @else
                        {{ $activeLocale === 'en' ? ('No Karnataka APMC auctions recorded within the last ' . ($stalenessThresholdDays ?? 14) . ' days.') : ('ಕಳೆದ ' . ($stalenessThresholdDays ?? 14) . ' ದಿನಗಳಲ್ಲಿ ಯಾವುದೇ ಕರ್ನಾಟಕ ಮಂಡಿಗಳಲ್ಲಿ ದರ ದಾಖಲಾಗಿಲ್ಲ.') }}
                    @endif
                </p>
                <div class="pt-2">
                    <a href="{{ route('farmer.crops.index') }}" class="px-4 py-2 text-xs font-bold text-white bg-[#1C5A2C] rounded-xl hover:bg-[#154622] transition font-sans shadow-xs">
                        {{ $activeLocale === 'en' ? 'View Other Crops' : 'ಇತರ ಬೆಳೆಗಳನ್ನು ವೀಕ್ಷಿಸಿ' }}
                    </a>
                </div>
            </div>
        @else
            <!-- 2 Mandis Near to Current User Location (Ranked by Best Price) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                @foreach($nearestTwoGroups as $index => $group)
                    @include('farmer.crops.partials.mandi-card', [
                        'group' => $group,
                        'rank' => $index + 1,
                        'isTopNearest' => ($index === 0),
                        'isNearestCard' => true,
                        'crop' => $crop,
                        'boardMeta' => $boardMeta,
                        'activeLocale' => $activeLocale,
                    ])
                @endforeach
            </div>

            <!-- Optional View All Other Mandis in Karnataka -->
            @if($allOtherMandiGroups->isNotEmpty())
                <div class="pt-2 text-center">
                    <button type="button" 
                            @click="showAllMandis = !showAllMandis"
                            class="inline-flex items-center gap-2.5 px-6 py-2.5 rounded-2xl bg-[#FAF8F5] hover:bg-white border-2 border-[#DDD2BE] hover:border-[#1C5A2C] text-[#1C5A2C] hover:text-[#144223] font-extrabold text-xs shadow-xs transition active:scale-95 cursor-pointer font-sans">
                        <span x-text="showAllMandis ? '▲' : '▼'"></span>
                        <span x-text="showAllMandis 
                            ? '{{ $activeLocale === 'en' ? 'Show 2 Nearest Mandis Only' : 'ಕೇವಲ ಹತ್ತಿರದ 2 ಮಂಡಿಗಳನ್ನು ತೋರಿಸಿ' }}' 
                            : '{{ $activeLocale === 'en' ? 'View All Other ' . $allOtherMandiGroups->count() . ' Mandis in Karnataka' : 'ಕರ್ನಾಟಕದ ಉಳಿದ ' . $allOtherMandiGroups->count() . ' ಮಂಡಿಗಳನ್ನು ವೀಕ್ಷಿಸಿ' }}'">
                        </span>
                    </button>

                    <div x-show="showAllMandis" 
                         x-cloak
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         class="space-y-3 pt-4 mt-4 border-t border-dashed border-stone-200 text-left">
                        <div class="flex items-center justify-between text-xs font-bold text-stone-500 font-sans px-1">
                            <span>🏛️ {{ $activeLocale === 'en' ? 'All Other Karnataka Mandis (Ranked by Best Price):' : 'ಕರ್ನಾಟಕದ ಇತರ ಎಲ್ಲಾ ಮಂಡಿಗಳು (ಅತ್ಯಧಿಕ ದರದಿಂದ ಇಳಿಕೆ ಕ್ರಮದಲ್ಲಿ):' }}</span>
                            <span class="text-stone-400 text-[11px]">{{ $allOtherMandiGroups->count() }} {{ $activeLocale === 'en' ? 'Mandis' : 'ಮಂಡಿಗಳು' }}</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                            @foreach($allOtherMandiGroups as $otherIndex => $group)
                                @include('farmer.crops.partials.mandi-card', [
                                    'group' => $group,
                                    'rank' => $otherIndex + 3,
                                    'isTopNearest' => false,
                                    'isNearestCard' => false,
                                    'crop' => $crop,
                                    'boardMeta' => $boardMeta,
                                    'activeLocale' => $activeLocale,
                                ])
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

    <!-- 8. Agri Videos, Articles & Government Schemes -->
    @if($cropVideos->isNotEmpty() || $cropArticles->isNotEmpty())
        <div class="bg-white rounded-3xl border border-[#E8DFC8] p-5 sm:p-7 shadow-2xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-stone-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🎬</span>
                    <div>
                        <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? $crop->name . ' — Expert Videos & Agronomy Guides' : (($crop->name_kn ?: $crop->name) . ' — ತಜ್ಞರ ವಿಡಿಯೋ & ಬೇಸಾಯ ಮಾರ್ಗದರ್ಶಿ') }}
                        </h2>
                        <span class="text-xs text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'Scientific farming practices and practical guidance' : 'ವೈಜ್ಞಾನಿಕ ಕೃಷಿ ಪದ್ಧತಿಗಳು ಮತ್ತು ಪ್ರಾಯೋಗಿಕ ಮಾಹಿತಿ' }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('farmer.videos.index', ['crop_id' => $crop->id]) }}" class="text-xs font-bold text-emerald-800 hover:text-emerald-950 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'All Videos &rarr;' : 'ಎಲ್ಲಾ ವಿಡಿಯೋಗಳು &rarr;' }}
                    </a>
                </div>
            </div>

            <!-- Videos Row -->
            @if($cropVideos->isNotEmpty())
                <div>
                    <h3 class="text-xs font-extrabold text-stone-400 uppercase tracking-wider mb-3 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Training Videos' : 'ತರಬೇತಿ ವಿಡಿಯೋಗಳು' }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach($cropVideos as $vid)
                            <div class="bg-stone-50 rounded-2xl border border-stone-200 overflow-hidden hover:border-emerald-500 hover:shadow-xs transition group">
                                <a href="{{ route('farmer.videos.index', ['crop_id' => $crop->id]) }}" class="block relative aspect-video bg-stone-900">
                                    <img src="{{ $vid->thumbnail_url }}" alt="{{ $vid->title }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    <div class="absolute inset-0 bg-stone-950/20 flex items-center justify-center">
                                        <div class="w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center shadow">
                                            <svg class="w-5 h-5 ml-0.5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        </div>
                                    </div>
                                    @if($vid->duration_text)
                                        <span class="absolute bottom-1.5 right-1.5 px-1.5 py-0.5 rounded bg-black/80 text-white text-[9px] font-bold font-sans">
                                            {{ $vid->duration_text }}
                                        </span>
                                    @endif
                                </a>
                                <div class="p-3">
                                    <h4 class="text-xs font-bold text-stone-900 line-clamp-2 group-hover:text-emerald-700 transition {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                        {{ $activeLocale === 'en' ? $vid->title : ($vid->title_kn ?: $vid->title) }}
                                    </h4>
                                    <div class="text-[10px] text-stone-500 mt-1 flex items-center justify-between {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                        <span>{{ $vid->channel_name ?: ($activeLocale === 'en' ? 'Agri Info' : 'ಕೃಷಿ ಮಾಹಿತಿ') }}</span>
                                        <a href="{{ route('farmer.videos.index', ['crop_id' => $crop->id]) }}" class="font-bold text-emerald-800 font-sans">{{ $activeLocale === 'en' ? 'Watch ▶' : 'ವೀಕ್ಷಿಸಿ ▶' }}</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Articles Row -->
            @if($cropArticles->isNotEmpty())
                <div class="pt-3 border-t border-stone-100">
                    <h3 class="text-xs font-extrabold text-stone-400 uppercase tracking-wider mb-3 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Agri Guides & Manuals' : 'ಬೇಸಾಯ ಲೇಖನಗಳು & ಕೈಪಿಡಿ' }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach($cropArticles as $art)
                            <a href="{{ route('farmer.articles.show', $art->slug) }}" class="p-3.5 bg-stone-50 rounded-2xl border border-stone-200 hover:border-emerald-500 hover:shadow-xs transition block">
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 mb-1.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                    {{ $activeLocale === 'en' ? ($art->category_label_en ?? $art->category_label_kn) : $art->category_label_kn }}
                                </span>
                                <h4 class="text-xs font-bold text-stone-900 line-clamp-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                    {{ $activeLocale === 'en' ? $art->title : ($art->title_kn ?: $art->title) }}
                                </h4>
                                <p class="text-[11px] text-stone-500 mt-1 line-clamp-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                    {{ $activeLocale === 'en' ? $art->summary : ($art->summary_kn ?: $art->summary) }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- 9. Government Schemes -->
    @if($cropSchemes->isNotEmpty())
        <div class="bg-gradient-to-br from-[#1C5A2C] to-teal-900 rounded-3xl p-5 sm:p-7 text-white shadow-md space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-emerald-700/60 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🏛️</span>
                    <div>
                        <h2 class="text-lg sm:text-xl font-black text-white tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'Agricultural Subsidies & Government Schemes' : 'ಕೃಷಿ ಸಬ್ಸಿಡಿ & ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು' }}
                        </h2>
                        <span class="text-xs text-emerald-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'Financial assistance and farm machinery subsidies available for farmers' : 'ರೈತರಿಗೆ ಲಭ್ಯವಿರುವ ಆರ್ಥಿಕ ನೆರವು & ಯಂತ್ರೋಪಕರಣ ಸಬ್ಸಿಡಿ' }}
                        </span>
                    </div>
                </div>
                <a href="{{ route('farmer.schemes.index') }}" class="text-xs font-bold text-amber-300 hover:text-amber-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'All Schemes &rarr;' : 'ಎಲ್ಲಾ ಯೋಜನೆಗಳು &rarr;' }}
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach($cropSchemes as $sch)
                    <div class="bg-emerald-950/50 backdrop-blur-xs border border-emerald-600/50 rounded-2xl p-4 flex flex-col justify-between hover:border-emerald-400 transition">
                        <div>
                            <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-emerald-700/70 text-emerald-100 mb-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $activeLocale === 'en' ? ($sch->category_label_en ?? $sch->category_label_kn) : $sch->category_label_kn }}
                            </span>
                            <h4 class="text-xs font-bold text-white line-clamp-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $activeLocale === 'en' ? $sch->title : ($sch->title_kn ?: $sch->title) }}
                            </h4>
                            <p class="text-[11px] text-emerald-200/90 mt-1.5 line-clamp-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $sch->summary_kn ?: $sch->summary }}
                            </p>
                        </div>
                        <div class="mt-4 pt-2 border-t border-emerald-800/80 flex items-center justify-between text-xs">
                            <a href="{{ route('farmer.schemes.show', $sch->slug) }}" class="font-bold text-amber-300 hover:text-amber-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $activeLocale === 'en' ? 'Scheme Details &rarr;' : 'ಅರ್ಜಿ ವಿವರ &rarr;' }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

                window.animateSeasonalBars = function() {
            const barFills = document.querySelectorAll('.season-bar-fill');
            if (!barFills || barFills.length === 0) return;
            // Double rAF forces browser to paint height:0 before transitioning
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    barFills.forEach((bar, idx) => {
                        const targetHeight = bar.getAttribute('data-target-height') || '0%';
                        setTimeout(() => {
                            bar.style.height = targetHeight;
                            // After spring-bounce settles (~900ms), activate peak glow + crown + price
                            if (bar.classList.contains('pk')) {
                                setTimeout(() => {
                                    bar.classList.add('khc-loaded');
                                    const col = bar.closest('.khc-col');
                                    if (col) {
                                        const crown = col.querySelector('.khc-crown');
                                        if (crown) crown.classList.add('khc-loaded');
                                        const priceLabel = col.querySelector('.khc-price-inline');
                                        if (priceLabel) priceLabel.classList.add('khc-loaded');
                                    }
                                }, 900);
                            } else if (bar.classList.contains('lo')) {
                                setTimeout(() => {
                                    bar.classList.add('khc-loaded');
                                }, 900);
                            }
                        }, 60 * idx);
                    });
                });
            });
        };

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        window.animateSeasonalBars();
                        observer.disconnect();
                    }
                });
            }, { threshold: 0.08 });

            const container = document.querySelector('.season-bars-container');
            if (container) {
                observer.observe(container);
            } else {
                setTimeout(window.animateSeasonalBars, 200);
            }
        } else {
            setTimeout(window.animateSeasonalBars, 200);
        }
    });
</script>
@endsection
