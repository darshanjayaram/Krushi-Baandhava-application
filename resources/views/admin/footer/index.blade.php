@extends('layouts.admin')

@section('header', 'Footer Layout & Content CMS')

@section('content')
<div class="space-y-6" x-data="{
    previewLang: 'en',
    previewMode: 'desktop',
    previewCol2Open: false,
    previewCol3Open: false,
    col2AllCollapsed: false,
    col3AllCollapsed: false,
    
    // Live reactive fields
    devName: @js($settings['developer_name']),
    devUrl: @js($settings['developer_url']),
    copyrightText: @js($settings['copyright_text']),
    taglineEn: @js($settings['tagline_en']),
    taglineKn: @js($settings['tagline_kn']),
    descEn: @js($settings['description_en']),
    descKn: @js($settings['description_kn']),
    telemetryBadge: @js($settings['telemetry_badge']),
    telemetrySource: @js($settings['telemetry_source']),
    disclaimerEn: @js($settings['disclaimer_en']),
    disclaimerKn: @js($settings['disclaimer_kn']),
    showTelemetry: @js($settings['show_telemetry']),
    showDisclaimer: @js($settings['show_disclaimer']),

    // Column 2 CMS State
    col2TitleEn: @js($settings['col2_title_en']),
    col2TitleKn: @js($settings['col2_title_kn']),
    col2Links: (@js($settings['col2_links']) || []).map(item => ({
        ...item,
        is_visible: item.is_visible !== false,
        is_collapsed: false
    })),

    // Column 3 CMS State
    col3TitleEn: @js($settings['col3_title_en']),
    col3TitleKn: @js($settings['col3_title_kn']),
    col3Links: (@js($settings['col3_links']) || []).map(item => ({
        ...item,
        is_visible: item.is_visible !== false,
        is_collapsed: false
    })),

    // Helper functions for Column 2
    addCol2Link() {
        this.col2Links.push({
            icon: '🔗',
            label_en: 'New Link',
            label_kn: 'ಹೊಸ ಲಿಂಕ್',
            url: '/',
            style: 'link',
            new_tab: false,
            is_visible: true,
            is_collapsed: false
        });
    },
    removeCol2Link(index) {
        if (confirm('Remove this link from Column 2?')) {
            this.col2Links.splice(index, 1);
        }
    },
    moveCol2Link(index, direction) {
        const target = index + direction;
        if (target >= 0 && target < this.col2Links.length) {
            const item = this.col2Links.splice(index, 1)[0];
            this.col2Links.splice(target, 0, item);
        }
    },
    toggleCol2CollapseAll() {
        this.col2AllCollapsed = !this.col2AllCollapsed;
        this.col2Links.forEach(item => item.is_collapsed = this.col2AllCollapsed);
    },

    // Helper functions for Column 3
    addCol3Link(defaultStyle = 'button') {
        const icons = {
            button: '💬',
            chip: '✉️',
            alert: '⚠️',
            link: '🔗'
        };
        const labelsEn = {
            button: 'Join WhatsApp Community',
            chip: 'support@krushibaandhava.org',
            alert: 'Report Mandi Rate Issue',
            link: 'Farmer Help Portal'
        };
        const labelsKn = {
            button: 'ವಾಟ್ಸಾಪ್ ಸಮುದಾಯಕ್ಕೆ ಸೇರಿ',
            chip: 'support@krushibaandhava.org',
            alert: 'ದರ ವ್ಯತ್ಯಾಸ ವರದಿ ಮಾಡಿ',
            link: 'ರೈತ ಸಹಾಯ ಕೇಂದ್ರ'
        };
        const subtitlesEn = {
            button: 'Karnataka Rytha Channel',
            chip: 'Email Support',
            alert: '',
            link: ''
        };
        const subtitlesKn = {
            button: 'ಕರ್ನಾಟಕ ರೈತರ ಸಮುದಾಯ',
            chip: 'ಇಮೇಲ್ ಬೆಂಬಲ',
            alert: '',
            link: ''
        };
        const urls = {
            button: 'https://whatsapp.com/channel/krushi-baandhava',
            chip: 'mailto:support@krushibaandhava.org',
            alert: '#',
            link: '#'
        };

        this.col3Links.push({
            icon: icons[defaultStyle] || '🔗',
            label_en: labelsEn[defaultStyle] || 'New Item',
            label_kn: labelsKn[defaultStyle] || 'ಹೊಸ ವಿವರ',
            subtitle_en: subtitlesEn[defaultStyle] || '',
            subtitle_kn: subtitlesKn[defaultStyle] || '',
            url: urls[defaultStyle] || '#',
            style: defaultStyle,
            new_tab: defaultStyle === 'button' || defaultStyle === 'alert',
            is_visible: true,
            is_collapsed: false
        });
    },
    removeCol3Link(index) {
        if (confirm('Remove this action from Column 3?')) {
            this.col3Links.splice(index, 1);
        }
    },
    moveCol3Link(index, direction) {
        const target = index + direction;
        if (target >= 0 && target < this.col3Links.length) {
            const item = this.col3Links.splice(index, 1)[0];
            this.col3Links.splice(target, 0, item);
        }
    },
    toggleCol3CollapseAll() {
        this.col3AllCollapsed = !this.col3AllCollapsed;
        this.col3Links.forEach(item => item.is_collapsed = this.col3AllCollapsed);
    }
}">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/90 border border-slate-800 p-5 rounded-3xl shadow-xl">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-xl shadow-xs">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 4h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-bold text-white tracking-tight">Dynamic Footer CMS Manager</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-950 text-emerald-300 border border-emerald-800/60">
                        Top-to-Bottom 2-Column Grid
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">Control developer attribution, rename column titles, toggle link visibility, and configure top-to-bottom 2-column navigation.</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <a href="{{ route('home') }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold transition flex items-center gap-1.5 border border-slate-700/80">
                <span>View Farmer PWA</span>
                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
            <button type="submit" form="footer-cms-form" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-sm flex items-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Save All Changes</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats Cards Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
        <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
            <div class="text-[10px] font-mono text-slate-400 uppercase tracking-wider">Footer Layout</div>
            <div class="text-sm font-bold text-white mt-1">Full-Width Classic</div>
            <div class="text-[11px] text-emerald-400 mt-0.5">Top-to-Bottom 2-Col Grid</div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
            <div class="text-[10px] font-mono text-slate-400 uppercase tracking-wider">Column 2 Links</div>
            <div class="text-sm font-bold text-teal-400 mt-1" x-text="col2Links.filter(i => i.is_visible !== false).length + ' / ' + col2Links.length + ' Shown'"></div>
            <div class="text-[11px] text-slate-400 mt-0.5" x-text="Math.min(col2Links.filter(i => i.is_visible !== false).length, 6) + ' in Col 1 (Left) • ' + Math.max(0, col2Links.filter(i => i.is_visible !== false).length - 6) + ' in Col 2 (Right)'"></div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
            <div class="text-[10px] font-mono text-slate-400 uppercase tracking-wider">Column 3 Actions</div>
            <div class="text-sm font-bold text-emerald-400 mt-1" x-text="col3Links.filter(i => i.is_visible !== false).length + ' / ' + col3Links.length + ' Shown'"></div>
            <div class="text-[11px] text-slate-400 mt-0.5" x-text="col3TitleEn"></div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
            <div class="text-[10px] font-mono text-slate-400 uppercase tracking-wider">Developer Credit</div>
            <div class="text-sm font-bold text-amber-400 mt-1 truncate" x-text="devName || 'Darshan Jayaram'"></div>
            <div class="text-[11px] text-slate-400 mt-0.5">Bottom Attribution Badge</div>
        </div>
    </div>

    <!-- Main Grid: Left Form Controls (7 cols) + Right Live Preview (5 cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Form Column (Left 7 Cols) -->
        <div class="lg:col-span-7 space-y-6">
            <form id="footer-cms-form" method="POST" action="{{ route('admin.footer.update') }}" class="space-y-6">
                @csrf

                <!-- Hidden inputs for serializing dynamic link arrays -->
                <input type="hidden" name="footer_col2_links" :value="JSON.stringify(col2Links)">
                <input type="hidden" name="footer_col3_links" :value="JSON.stringify(col3Links)">

                <!-- SECTION 1: COLUMN 2 DYNAMIC CMS BUILDER (Platform Hubs) -->
                <div class="p-5 sm:p-6 rounded-3xl bg-slate-950/80 border border-teal-500/30 shadow-xl space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/80 pb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-teal-500/10 border border-teal-500/30 flex items-center justify-center text-teal-400 text-sm">
                                📂
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">Column 2: Dynamic Navigation Links CMS</h3>
                                <p class="text-[11px] text-slate-400">First 6 items fill Column 1 (Left) top-to-bottom. 7th item onwards flows to Column 2 (Right). Hide/Show toggle controls public footer visibility.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 self-start sm:self-auto">
                            <!-- Collapse / Expand All Links -->
                            <button type="button" @click="toggleCol2CollapseAll()"
                                    class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition border border-slate-700/80 cursor-pointer">
                                <span x-text="col2AllCollapsed ? 'Expand All' : 'Collapse All'"></span>
                            </button>

                            <!-- Add New Link Button -->
                            <button type="button" @click="addCol2Link()"
                                    class="px-3 py-1.5 rounded-xl bg-teal-600 hover:bg-teal-500 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs">
                                <span>➕</span>
                                <span>Add Link</span>
                            </button>
                        </div>
                    </div>

                    <!-- Column 2 Titles (English & Kannada) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800">
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-teal-300">
                                Column 2 Title (English) *
                            </label>
                            <input type="text" name="footer_col2_title_en" x-model="col2TitleEn" required
                                   placeholder="e.g. Platform Hubs"
                                   class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-teal-500 font-sans">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-teal-300">
                                Column 2 Title (ಕನ್ನಡ / Kannada) *
                            </label>
                            <input type="text" name="footer_col2_title_kn" x-model="col2TitleKn" required
                                   placeholder="ಉದಾ. ವೇದಿಕೆ ಕೇಂದ್ರಗಳು"
                                   class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-teal-500 font-sans">
                        </div>
                    </div>

                    <!-- Links List -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-xs text-slate-400 font-semibold px-1">
                            <div class="flex items-center gap-2">
                                <span>Configured Links (<span x-text="col2Links.length"></span> Total)</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-950 text-teal-300 border border-teal-800/60"
                                      x-text="col2Links.filter(i => i.is_visible !== false).length + ' Visible on Footer'"></span>
                            </div>
                            <span class="text-[11px] text-slate-400 hidden sm:inline">First 6 items fill Col 1 (Left) • 7th+ in Col 2 (Right)</span>
                        </div>

                        <template x-for="(item, idx) in col2Links" :key="idx">
                            <div class="p-3.5 rounded-2xl border transition space-y-2.5"
                                 :class="item.is_visible === false ? 'opacity-70 bg-slate-950/50 border-dashed border-slate-700' : 'bg-slate-900/90 border-slate-800 hover:border-slate-700'">
                                
                                <!-- Card Header Row: Sub-column Badge, Hide/Show Toggle, Reorder, Delete -->
                                <div class="flex items-center justify-between gap-2 border-b border-slate-800/60 pb-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="px-2 py-0.5 rounded-md bg-slate-800 text-[10px] font-mono font-bold text-teal-400 shrink-0" x-text="'#' + (idx + 1)"></span>
                                        
                                        <!-- Sub-column Flow Indicator (First 6 on Left, 7+ on Right) -->
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold shrink-0"
                                              :class="idx < 6 ? 'bg-teal-950 text-teal-300 border border-teal-800/60' : 'bg-cyan-950 text-cyan-300 border border-cyan-800/60'"
                                              x-text="idx < 6 ? 'Col 1 (Left)' : 'Col 2 (Right)'">
                                        </span>

                                        <span class="text-xs font-bold text-white truncate" x-text="item.label_en || 'Link Item'"></span>
                                        
                                        <!-- Hidden Tag -->
                                        <span x-show="item.is_visible === false" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-950 text-amber-300 border border-amber-800 shrink-0">
                                            Hidden
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <!-- 👁️ HIDE / SHOW TOGGLE BUTTON -->
                                        <button type="button" @click="item.is_visible = (item.is_visible === false ? true : false)"
                                                :class="item.is_visible !== false ? 'bg-emerald-950/80 text-emerald-300 border-emerald-700/80 hover:bg-emerald-900' : 'bg-slate-800 text-slate-400 border-slate-700 hover:text-white'"
                                                class="px-2.5 py-1 rounded-lg text-[10px] font-bold border transition flex items-center gap-1 cursor-pointer"
                                                :title="item.is_visible !== false ? 'Currently Visible on Footer. Click to Hide.' : 'Currently Hidden from Footer. Click to Show.'">
                                            <span x-text="item.is_visible !== false ? '👁️ Shown' : '🚫 Hidden'"></span>
                                        </button>

                                        <!-- Collapse / Expand Card Details -->
                                        <button type="button" @click="item.is_collapsed = !item.is_collapsed"
                                                class="w-6 h-6 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs flex items-center justify-center transition cursor-pointer"
                                                :title="item.is_collapsed ? 'Expand details' : 'Collapse card'">
                                            <span x-text="item.is_collapsed ? '▼' : '▲'"></span>
                                        </button>

                                        <!-- Reorder Up -->
                                        <button type="button" @click="moveCol2Link(idx, -1)" :disabled="idx === 0"
                                                class="w-6 h-6 rounded-lg bg-slate-800 hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed text-slate-300 text-xs flex items-center justify-center transition cursor-pointer" title="Move Up">
                                            ▲
                                        </button>
                                        <!-- Reorder Down -->
                                        <button type="button" @click="moveCol2Link(idx, 1)" :disabled="idx === col2Links.length - 1"
                                                class="w-6 h-6 rounded-lg bg-slate-800 hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed text-slate-300 text-xs flex items-center justify-center transition cursor-pointer" title="Move Down">
                                            ▼
                                        </button>
                                        <!-- Delete -->
                                        <button type="button" @click="removeCol2Link(idx)"
                                                class="w-6 h-6 rounded-lg bg-red-950/60 hover:bg-red-800/80 text-red-300 text-xs flex items-center justify-center transition ml-1 cursor-pointer" title="Delete Link">
                                            ✕
                                        </button>
                                    </div>
                                </div>

                                <!-- Collapsed 1-Line Preview -->
                                <div x-show="item.is_collapsed" class="text-[11px] text-slate-400 font-mono truncate flex items-center gap-2 pt-0.5">
                                    <span x-text="item.icon || '🔗'"></span>
                                    <span class="text-white font-sans font-semibold" x-text="item.label_en"></span>
                                    <span class="text-slate-500">•</span>
                                    <span class="text-slate-400 truncate" x-text="item.url"></span>
                                </div>

                                <!-- Expanded Full Form Inputs -->
                                <div x-show="!item.is_collapsed" class="space-y-2.5">
                                    <!-- Warning note if hidden -->
                                    <div x-show="item.is_visible === false" class="text-[10px] text-amber-400/90 font-medium flex items-center gap-1">
                                        <span>⚠️</span>
                                        <span>This link is currently hidden and will not be displayed in the public footer.</span>
                                    </div>

                                    <div class="grid grid-cols-12 gap-2.5 pt-1">
                                        <!-- Emoji / Icon -->
                                        <div class="col-span-3 sm:col-span-2 space-y-1">
                                            <label class="block text-[10px] font-semibold text-slate-400">Icon / Emoji</label>
                                            <input type="text" x-model="item.icon" placeholder="📊"
                                                   class="w-full text-center px-2 py-1.5 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-teal-500 font-sans">
                                        </div>
                                        <!-- English Label -->
                                        <div class="col-span-9 sm:col-span-5 space-y-1">
                                            <label class="block text-[10px] font-semibold text-slate-400">English Label *</label>
                                            <input type="text" x-model="item.label_en" placeholder="e.g. Daily Mandi Rates" required
                                                   class="w-full px-2.5 py-1.5 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-teal-500 font-sans">
                                        </div>
                                        <!-- Kannada Label -->
                                        <div class="col-span-12 sm:col-span-5 space-y-1">
                                            <label class="block text-[10px] font-semibold text-slate-400">ಕನ್ನಡ ಹೆಸರು (Kannada Label)</label>
                                            <input type="text" x-model="item.label_kn" placeholder="ಉದಾ. ದೈನಂದಿನ ಮಂಡಿ ದರಗಳು"
                                                   class="w-full px-2.5 py-1.5 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-teal-500 font-sans">
                                        </div>

                                        <!-- Target URL -->
                                        <div class="col-span-12 sm:col-span-8 space-y-1">
                                            <label class="block text-[10px] font-semibold text-slate-400">Target Page URL / Path *</label>
                                            <input type="text" x-model="item.url" placeholder="/crops or https://..." required
                                                   class="w-full px-2.5 py-1.5 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-teal-500 font-mono">
                                        </div>

                                        <!-- New Tab Checkbox -->
                                        <div class="col-span-12 sm:col-span-4 flex items-center pt-5">
                                            <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
                                                <input type="checkbox" x-model="item.new_tab"
                                                       class="w-3.5 h-3.5 rounded text-teal-600 bg-slate-950 border-slate-700 focus:ring-teal-500">
                                                <span class="text-[11px]">Open in New Tab</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </template>

                        <div x-show="col2Links.length === 0" class="p-6 rounded-2xl bg-slate-900/40 border border-dashed border-slate-800 text-center space-y-2">
                            <p class="text-xs text-slate-400">No navigation links added yet.</p>
                            <button type="button" @click="addCol2Link()" class="px-3 py-1.5 rounded-xl bg-teal-600/80 hover:bg-teal-600 text-white text-xs font-bold">
                                ➕ Add First Link
                            </button>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: COLUMN 3 DYNAMIC CMS BUILDER (Community, WhatsApp & Actions) -->
                <div class="p-5 sm:p-6 rounded-3xl bg-slate-950/80 border border-emerald-500/30 shadow-xl space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/80 pb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-sm">
                                🤝
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">Column 3: Community, WhatsApp CTA & Action Builder</h3>
                                <p class="text-[11px] text-slate-400">Rename the column title, style items as green WhatsApp CTA buttons, contact chips, or alert pills.</p>
                            </div>
                        </div>

                        <!-- Style Presets Quick Add -->
                        <div class="flex flex-wrap items-center gap-1.5 self-start sm:self-auto">
                            <button type="button" @click="addCol3Link('button')"
                                    class="px-2.5 py-1 rounded-xl bg-emerald-700 hover:bg-emerald-600 text-white text-[11px] font-bold transition flex items-center gap-1 shadow-xs cursor-pointer">
                                <span>🟢</span>
                                <span>+ WhatsApp CTA</span>
                            </button>
                            <button type="button" @click="addCol3Link('chip')"
                                    class="px-2.5 py-1 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-semibold transition flex items-center gap-1 border border-slate-700 cursor-pointer">
                                <span>✉️</span>
                                <span>+ Email Chip</span>
                            </button>
                            <button type="button" @click="addCol3Link('alert')"
                                    class="px-2.5 py-1 rounded-xl bg-amber-950/80 hover:bg-amber-900 text-amber-300 text-[11px] font-semibold transition flex items-center gap-1 border border-amber-800/60 cursor-pointer">
                                <span>⚠️</span>
                                <span>+ Alert Pill</span>
                            </button>
                            <button type="button" @click="addCol3Link('link')"
                                    class="px-2 py-1 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] transition cursor-pointer">
                                <span>🔗 Link</span>
                            </button>
                        </div>
                    </div>

                    <!-- Column 3 Titles (English & Kannada) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800">
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-emerald-300">
                                Column 3 Title (English) *
                            </label>
                            <input type="text" name="footer_col3_title_en" x-model="col3TitleEn" required
                                   placeholder="e.g. Community & Help"
                                   class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-emerald-300">
                                Column 3 Title (ಕನ್ನಡ / Kannada) *
                            </label>
                            <input type="text" name="footer_col3_title_kn" x-model="col3TitleKn" required
                                   placeholder="ಉದಾ. ಸಂಪರ್ಕ & ಸಹಾಯ"
                                   class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans">
                        </div>
                    </div>

                    <!-- Actions / Links List -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-xs text-slate-400 font-semibold px-1">
                            <div class="flex items-center gap-2">
                                <span>Configured Actions (<span x-text="col3Links.length"></span> Total)</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800/60"
                                      x-text="col3Links.filter(i => i.is_visible !== false).length + ' Visible on Footer'"></span>
                            </div>
                            <button type="button" @click="toggleCol3CollapseAll()"
                                    class="text-[11px] text-slate-400 hover:text-white transition">
                                <span x-text="col3AllCollapsed ? 'Expand All' : 'Collapse All'"></span>
                            </button>
                        </div>

                        <template x-for="(item, idx) in col3Links" :key="idx">
                            <div class="p-3.5 rounded-2xl border transition space-y-3"
                                 :class="item.is_visible === false ? 'opacity-70 bg-slate-950/50 border-dashed border-slate-700' : 'bg-slate-900/90 border-slate-800 hover:border-slate-700'">
                                
                                <!-- Card Top Row: Index, Style Badge, Hide/Show, Reorder, Delete -->
                                <div class="flex items-center justify-between gap-2 border-b border-slate-800/60 pb-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="px-2 py-0.5 rounded-md bg-slate-800 text-[10px] font-mono font-bold text-emerald-400 shrink-0" x-text="'#' + (idx + 1)"></span>
                                        
                                        <!-- Visual Style Badge Indicator -->
                                        <span x-show="item.style === 'button'" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-700 shrink-0">
                                            🟢 CTA Button
                                        </span>
                                        <span x-show="item.style === 'chip'" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-200 border border-slate-700 shrink-0">
                                            ✉️ Chip
                                        </span>
                                        <span x-show="item.style === 'alert'" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-950 text-amber-300 border border-amber-800 shrink-0">
                                            🟡 Alert
                                        </span>
                                        <span x-show="item.style === 'link'" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700 shrink-0">
                                            🔗 Link
                                        </span>

                                        <span class="text-xs font-bold text-white truncate" x-text="item.label_en || 'Action Item'"></span>

                                        <!-- Hidden Tag -->
                                        <span x-show="item.is_visible === false" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-950 text-amber-300 border border-amber-800 shrink-0">
                                            Hidden
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <!-- 👁️ HIDE / SHOW TOGGLE BUTTON -->
                                        <button type="button" @click="item.is_visible = (item.is_visible === false ? true : false)"
                                                :class="item.is_visible !== false ? 'bg-emerald-950/80 text-emerald-300 border-emerald-700/80 hover:bg-emerald-900' : 'bg-slate-800 text-slate-400 border-slate-700 hover:text-white'"
                                                class="px-2.5 py-1 rounded-lg text-[10px] font-bold border transition flex items-center gap-1 cursor-pointer"
                                                :title="item.is_visible !== false ? 'Currently Visible on Footer. Click to Hide.' : 'Currently Hidden from Footer. Click to Show.'">
                                            <span x-text="item.is_visible !== false ? '👁️ Shown' : '🚫 Hidden'"></span>
                                        </button>

                                        <!-- Collapse / Expand Card Details -->
                                        <button type="button" @click="item.is_collapsed = !item.is_collapsed"
                                                class="w-6 h-6 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs flex items-center justify-center transition cursor-pointer"
                                                :title="item.is_collapsed ? 'Expand details' : 'Collapse card'">
                                            <span x-text="item.is_collapsed ? '▼' : '▲'"></span>
                                        </button>

                                        <!-- Reorder Up -->
                                        <button type="button" @click="moveCol3Link(idx, -1)" :disabled="idx === 0"
                                                class="w-6 h-6 rounded-lg bg-slate-800 hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed text-slate-300 text-xs flex items-center justify-center transition cursor-pointer" title="Move Up">
                                            ▲
                                        </button>
                                        <!-- Reorder Down -->
                                        <button type="button" @click="moveCol3Link(idx, 1)" :disabled="idx === col3Links.length - 1"
                                                class="w-6 h-6 rounded-lg bg-slate-800 hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed text-slate-300 text-xs flex items-center justify-center transition cursor-pointer" title="Move Down">
                                            ▼
                                        </button>
                                        <!-- Delete -->
                                        <button type="button" @click="removeCol3Link(idx)"
                                                class="w-6 h-6 rounded-lg bg-red-950/60 hover:bg-red-800/80 text-red-300 text-xs flex items-center justify-center transition ml-1 cursor-pointer" title="Delete Action">
                                            ✕
                                        </button>
                                    </div>
                                </div>

                                <!-- Collapsed 1-Line Preview -->
                                <div x-show="item.is_collapsed" class="text-[11px] text-slate-400 font-mono truncate flex items-center gap-2 pt-0.5">
                                    <span x-text="item.icon || '💬'"></span>
                                    <span class="text-white font-sans font-semibold" x-text="item.label_en"></span>
                                    <span class="text-slate-500">•</span>
                                    <span class="text-slate-400 truncate" x-text="item.url"></span>
                                </div>

                                <!-- Expanded Form Body -->
                                <div x-show="!item.is_collapsed" class="space-y-3">
                                    <!-- Warning note if hidden -->
                                    <div x-show="item.is_visible === false" class="text-[10px] text-amber-400/90 font-medium flex items-center gap-1">
                                        <span>⚠️</span>
                                        <span>This action is currently hidden and will not be displayed in the public footer.</span>
                                    </div>

                                    <!-- Style Picker Segmented Bar -->
                                    <div class="space-y-1">
                                        <label class="block text-[10px] font-bold text-slate-400">Display Style Format</label>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 p-1 bg-slate-950 border border-slate-800 rounded-xl text-[11px]">
                                            <button type="button" @click="item.style = 'button'"
                                                    :class="item.style === 'button' ? 'bg-emerald-700 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                                    class="py-1 px-2 rounded-lg transition text-center cursor-pointer">
                                                🟢 CTA Button
                                            </button>
                                            <button type="button" @click="item.style = 'chip'"
                                                    :class="item.style === 'chip' ? 'bg-slate-700 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                                    class="py-1 px-2 rounded-lg transition text-center cursor-pointer">
                                                ✉️ Contact Chip
                                            </button>
                                            <button type="button" @click="item.style = 'alert'"
                                                    :class="item.style === 'alert' ? 'bg-amber-800 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                                    class="py-1 px-2 rounded-lg transition text-center cursor-pointer">
                                                🟡 Alert Pill
                                            </button>
                                            <button type="button" @click="item.style = 'link'"
                                                    :class="item.style === 'link' ? 'bg-slate-700 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                                    class="py-1 px-2 rounded-lg transition text-center cursor-pointer">
                                                🔗 Plain Link
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Fields Grid -->
                                    <div class="grid grid-cols-12 gap-2.5 pt-1">
                                        <!-- Emoji / Icon -->
                                        <div class="col-span-3 sm:col-span-2 space-y-1">
                                            <label class="block text-[10px] font-semibold text-slate-400">Icon</label>
                                            <input type="text" x-model="item.icon" placeholder="💬"
                                                   class="w-full text-center px-2 py-1.5 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans">
                                        </div>
                                        <!-- English Label -->
                                        <div class="col-span-9 sm:col-span-5 space-y-1">
                                            <label class="block text-[10px] font-semibold text-slate-400">English Label *</label>
                                            <input type="text" x-model="item.label_en" placeholder="e.g. Join WhatsApp Community" required
                                                   class="w-full px-2.5 py-1.5 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans">
                                        </div>
                                        <!-- Kannada Label -->
                                        <div class="col-span-12 sm:col-span-5 space-y-1">
                                            <label class="block text-[10px] font-semibold text-slate-400">ಕನ್ನಡ ಹೆಸರು (Kannada Label)</label>
                                            <input type="text" x-model="item.label_kn" placeholder="ಉದಾ. ವಾಟ್ಸಾಪ್ ಸಮುದಾಯಕ್ಕೆ ಸೇರಿ"
                                                   class="w-full px-2.5 py-1.5 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans">
                                        </div>

                                        <!-- Optional Subtitle EN (Shown in Button & Chip) -->
                                        <div class="col-span-12 sm:col-span-6 space-y-1" x-show="item.style === 'button' || item.style === 'chip'">
                                            <label class="block text-[10px] font-semibold text-slate-400">Subtitle (English) <span class="text-slate-500 font-normal">Optional</span></label>
                                            <input type="text" x-model="item.subtitle_en" placeholder="e.g. Karnataka Rytha Channel"
                                                   class="w-full px-2.5 py-1.5 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans">
                                        </div>

                                        <!-- Optional Subtitle KN -->
                                        <div class="col-span-12 sm:col-span-6 space-y-1" x-show="item.style === 'button' || item.style === 'chip'">
                                            <label class="block text-[10px] font-semibold text-slate-400">ಉಪ-ವಿವರಣೆ (Subtitle Kannada)</label>
                                            <input type="text" x-model="item.subtitle_kn" placeholder="ಉದಾ. ಕರ್ನಾಟಕ ರೈತರ ಸಮುದಾಯ"
                                                   class="w-full px-2.5 py-1.5 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans">
                                        </div>

                                        <!-- Target URL -->
                                        <div class="col-span-12 sm:col-span-8 space-y-1">
                                            <label class="block text-[10px] font-semibold text-slate-400">URL / Link Target *</label>
                                            <input type="text" x-model="item.url" placeholder="https://whatsapp.com/channel/... or mailto:..." required
                                                   class="w-full px-2.5 py-1.5 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-mono">
                                        </div>

                                        <!-- New Tab Checkbox -->
                                        <div class="col-span-12 sm:col-span-4 flex items-center pt-5">
                                            <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
                                                <input type="checkbox" x-model="item.new_tab"
                                                       class="w-3.5 h-3.5 rounded text-emerald-600 bg-slate-950 border-slate-700 focus:ring-emerald-500">
                                                <span class="text-[11px]">Open in New Tab</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </template>

                        <div x-show="col3Links.length === 0" class="p-6 rounded-2xl bg-slate-900/40 border border-dashed border-slate-800 text-center space-y-2">
                            <p class="text-xs text-slate-400">No community actions configured yet.</p>
                            <button type="button" @click="addCol3Link('button')" class="px-3 py-1.5 rounded-xl bg-emerald-700 hover:bg-emerald-600 text-white text-xs font-bold">
                                🟢 Add WhatsApp CTA Button
                            </button>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: DEVELOPER ATTRIBUTION & COPYRIGHT -->
                <div class="p-5 sm:p-6 rounded-3xl bg-slate-950/80 border border-slate-800 shadow-xl space-y-4">
                    <div class="flex items-center gap-3 border-b border-slate-800/80 pb-3">
                        <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 text-sm">
                            🛠️
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">Developer Attribution & Copyright Bar</h3>
                            <p class="text-[11px] text-slate-400">Configure developer signature, portfolio URL, and copyright year in the bottom footer bar.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Developer Name -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-amber-400">
                                <span>Developer Attribution Name *</span>
                                <span class="text-slate-400 font-normal ml-1">(ಡೆವಲಪರ್ ಹೆಸರು)</span>
                            </label>
                            <input type="text" name="footer_developer_name" x-model="devName" required
                                   placeholder="e.g. Darshan Jayaram"
                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-amber-500 font-sans">
                            <p class="text-[10px] text-slate-500">Rendered in the 'Developed by [Name]' badge on the bottom left.</p>
                        </div>

                        <!-- Developer URL -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-amber-400">
                                <span>Developer Website / GitHub URL</span>
                                <span class="text-slate-400 font-normal ml-1">(ವೆಬ್‌ಸೈಟ್ ಲಿಂಕ್)</span>
                            </label>
                            <input type="text" name="footer_developer_url" x-model="devUrl"
                                   placeholder="https://github.com/..."
                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-amber-500 font-sans">
                            <p class="text-[10px] text-slate-500">Opens in a new tab when clicked (use '#' if none).</p>
                        </div>

                        <!-- Copyright Text -->
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-300">
                                <span>Copyright Text</span>
                            </label>
                            <input type="text" name="footer_copyright_text" x-model="copyrightText"
                                   placeholder="© 2026 Krushi Baandhava. All rights reserved."
                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-slate-500 font-sans">
                        </div>
                    </div>
                </div>

                <!-- SECTION 4: BILINGUAL MISSION STATEMENT & DISCLAIMERS -->
                <div class="p-5 sm:p-6 rounded-3xl bg-slate-950/80 border border-slate-800 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/80 pb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-teal-500/10 border border-teal-500/30 flex items-center justify-center text-teal-400 text-sm">
                                🌐
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">Column 1: Mission Statement & Disclaimers</h3>
                                <p class="text-[11px] text-slate-400">Manage English and Kannada copy for the mission statement and legal notices.</p>
                            </div>
                        </div>

                        <!-- Language Switch Tabs -->
                        <div class="flex items-center gap-1 p-1 bg-slate-900 border border-slate-800 rounded-xl self-start sm:self-auto">
                            <button type="button" @click="previewLang = 'en'"
                                    :class="previewLang === 'en' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                    class="px-3 py-1 text-xs rounded-lg transition cursor-pointer">
                                English
                            </button>
                            <button type="button" @click="previewLang = 'kn'"
                                    :class="previewLang === 'kn' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                    class="px-3 py-1 text-xs rounded-lg transition cursor-pointer">
                                ಕನ್ನಡ
                            </button>
                        </div>
                    </div>

                    <!-- English Inputs Tab -->
                    <div x-show="previewLang === 'en'" class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-teal-400">
                                <span>Tagline (English)</span>
                            </label>
                            <input type="text" name="footer_tagline_en" x-model="taglineEn"
                                   placeholder="e.g. Karnataka Farmer Market Intelligence Network"
                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-teal-500 font-sans">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-teal-400">
                                <span>Mission Description (English) *</span>
                            </label>
                            <textarea name="footer_description_en" x-model="descEn" rows="2" required
                                      class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-teal-500 font-sans leading-relaxed"></textarea>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-400">
                                <span>Independent Platform Disclaimer (English)</span>
                            </label>
                            <textarea name="footer_disclaimer_en" x-model="disclaimerEn" rows="2"
                                      class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-slate-500 font-sans leading-relaxed"></textarea>
                        </div>
                    </div>

                    <!-- Kannada Inputs Tab -->
                    <div x-show="previewLang === 'kn'" class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-teal-400">
                                <span>ಕನ್ನಡ ಉಪ-ಶೀರ್ಷಿಕೆ (Tagline Kannada)</span>
                            </label>
                            <input type="text" name="footer_tagline_kn" x-model="taglineKn"
                                   placeholder="ಕರ್ನಾಟಕದ ರೈತ ಮಾರುಕಟ್ಟೆ ಮಾಹಿತಿ ವೇದಿಕೆ"
                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-teal-500 font-sans">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-teal-400">
                                <span>ಕನ್ನಡ ವಿವರಣೆ (Mission Description Kannada) *</span>
                            </label>
                            <textarea name="footer_description_kn" x-model="descKn" rows="2" required
                                      class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-teal-500 font-sans leading-relaxed"></textarea>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-400">
                                <span>ಕನ್ನಡ ಹಕ್ಕುತ್ಯಾಗ ಸೂಚನೆ (Disclaimer Kannada)</span>
                            </label>
                            <textarea name="footer_disclaimer_kn" x-model="disclaimerKn" rows="2"
                                      class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-slate-500 font-sans leading-relaxed"></textarea>
                        </div>
                    </div>

                    <!-- Show Disclaimer Box Toggle -->
                    <div class="pt-2 border-t border-slate-800/80">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="footer_show_disclaimer" value="1" x-model="showDisclaimer"
                                   class="w-4 h-4 rounded text-emerald-600 bg-slate-900 border-slate-700 focus:ring-emerald-500">
                            <span class="text-xs text-slate-300 font-medium">Display Independent Platform Disclaimer Notice in Column 1</span>
                        </label>
                    </div>
                </div>

                <!-- SECTION 5: LIVE APMC TELEMETRY BADGE -->
                <div class="p-5 sm:p-6 rounded-3xl bg-slate-950/80 border border-slate-800 shadow-xl space-y-4">
                    <div class="flex items-center gap-3 border-b border-slate-800/80 pb-3">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-sm">
                            📡
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">Live APMC Mandi Telemetry Pill</h3>
                            <p class="text-[11px] text-slate-400">Display real-time feeds status and mandi coverage numbers.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-emerald-400">
                                <span>Mandi Coverage Badge Text</span>
                            </label>
                            <input type="text" name="footer_telemetry_badge" x-model="telemetryBadge"
                                   placeholder="e.g. 31 Districts • 160+ APMCs"
                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-emerald-400">
                                <span>Feed Source Attribution</span>
                            </label>
                            <input type="text" name="footer_telemetry_source" x-model="telemetrySource"
                                   placeholder="e.g. Sourced from KRAMA & Agmarknet Feeds"
                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans">
                        </div>

                        <div class="sm:col-span-2 pt-2 border-t border-slate-800/80">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="footer_show_telemetry" value="1" x-model="showTelemetry"
                                       class="w-4 h-4 rounded text-emerald-600 bg-slate-900 border-slate-700 focus:ring-emerald-500">
                                <span class="text-xs text-slate-300 font-medium">Display Live Mandi Telemetry Chip with Pulsing Green Dot</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Bottom Save Button -->
                <div class="flex items-center justify-end">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-sm flex items-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Publish Footer Settings</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Sticky Live Preview Column (Right 5 Cols) -->
        <div class="lg:col-span-5 sticky top-20 space-y-4">
            <div class="p-5 rounded-3xl bg-slate-950/80 border border-slate-800 shadow-xl space-y-4">
                
                <!-- Preview Toolbar -->
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-white uppercase tracking-wider">Interactive Live Preview</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    </div>

                    <!-- Desktop / Mobile Toggle -->
                    <div class="flex items-center gap-1 p-1 bg-slate-900 border border-slate-800 rounded-xl text-[11px]">
                        <button type="button" @click="previewMode = 'desktop'"
                                :class="previewMode === 'desktop' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                class="px-2.5 py-1 rounded-lg transition cursor-pointer">
                            🖥️ Desktop
                        </button>
                        <button type="button" @click="previewMode = 'mobile'"
                                :class="previewMode === 'mobile' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                class="px-2.5 py-1 rounded-lg transition cursor-pointer">
                            📱 Mobile
                        </button>
                    </div>
                </div>

                <!-- Mini Render Frame (Matches Full-Width Compact Classic Theme) -->
                <div :class="previewMode === 'mobile' ? 'max-w-[340px] mx-auto' : 'w-full'"
                     class="transition-all duration-300">
                    
                    <!-- Simulated Full-Width Classic Footer Band -->
                    <div class="rounded-xl bg-[#EFEAE0] border-t-2 border-b border-[#DDD3BE] p-4 text-stone-800 relative shadow-inner space-y-3.5">
                        
                        <!-- COLUMN 1: Brand & Description -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-white border border-[#DDD3BE] p-1 flex items-center justify-center shrink-0">
                                    <span class="text-base">🌱</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-black text-[#1C5A2C]">Krushi Baandhava</span>
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-[#E2DAC8] text-[#1C5A2C]">ಕೃಷಿ ಬಾಂಧವ</span>
                                    </div>
                                    <div class="text-[9px] text-stone-600 font-medium" x-text="previewLang === 'kn' ? (taglineKn || 'ಕರ್ನಾಟಕದ ರೈತ ಮಾರುಕಟ್ಟೆ ಮಾಹಿತಿ') : (taglineEn || 'Karnataka Farmer Market Network')"></div>
                                </div>
                            </div>

                            <!-- Mission text -->
                            <p class="text-[10px] text-stone-600 leading-snug line-clamp-2"
                               x-text="previewLang === 'kn' ? descKn : descEn"></p>

                            <!-- Telemetry Pill (if enabled) -->
                            <template x-if="showTelemetry">
                                <div class="p-1.5 rounded-lg bg-[#E6DFD1] border border-[#D5CABB] flex items-center justify-between text-[10px]">
                                    <div class="flex items-center gap-1.5 font-bold text-stone-800">
                                        <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                                        <span x-text="telemetryBadge || '31 Districts • 160+ APMCs'"></span>
                                    </div>
                                    <span class="text-[9px] text-stone-600 font-mono">⚡ Feeds</span>
                                </div>
                            </template>

                            <!-- Disclaimers (if enabled) -->
                            <template x-if="showDisclaimer">
                                <div class="p-1 text-[9px] text-stone-500 leading-tight">
                                    <span class="font-bold text-stone-700">🛡️ Notice:</span>
                                    <span x-text="previewLang === 'kn' ? disclaimerKn : disclaimerEn" class="line-clamp-2"></span>
                                </div>
                            </template>
                        </div>

                        <!-- DESKTOP PREVIEW COLUMNS 2 & 3 -->
                        <div x-show="previewMode === 'desktop'" class="space-y-3 pt-2 border-t border-[#DDD3BE]">
                            <!-- Column 2 Preview (Ordered Top to Bottom in 2 Columns) -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <div class="text-[10px] font-black text-stone-800 uppercase tracking-wider flex items-center gap-1">
                                        <span>📂</span>
                                        <span x-text="previewLang === 'kn' ? col2TitleKn : col2TitleEn"></span>
                                    </div>
                                    <span class="text-[9px] font-mono text-teal-800 font-bold"
                                          x-text="col2Links.filter(i => i.is_visible !== false).length + ' Links (Top to Bottom)'"></span>
                                </div>

                                <div class="grid gap-x-3 gap-y-1 text-[10px] bg-white/40 p-2 rounded-lg border border-[#DDD3BE]"
                                     :class="col2Links.filter(i => i.is_visible !== false).length > 6 ? 'grid-cols-2' : 'grid-cols-1'">
                                    <!-- Sub-column 1 (Left: Items 1 to 6 Top to Bottom) -->
                                    <div class="space-y-1" :class="col2Links.filter(i => i.is_visible !== false).length > 6 ? 'border-r border-[#DDD3BE]/60 pr-2' : ''">
                                        <div class="text-[8px] font-mono text-stone-500 font-bold uppercase tracking-wider">Col 1 (Left • 1-6)</div>
                                        <template x-for="(item, idx) in col2Links.filter(i => i.is_visible !== false).slice(0, 6)" :key="'col1-' + idx">
                                            <div class="flex items-center gap-1 text-stone-700 truncate font-medium py-0.5">
                                                <span x-text="item.icon || '🔗'"></span>
                                                <span class="truncate" x-text="previewLang === 'kn' ? (item.label_kn || item.label_en) : item.label_en"></span>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Sub-column 2 (Right: Items 7 onwards Top to Bottom) -->
                                    <template x-if="col2Links.filter(i => i.is_visible !== false).length > 6">
                                        <div class="space-y-1 pl-1">
                                            <div class="text-[8px] font-mono text-stone-500 font-bold uppercase tracking-wider">Col 2 (Right • 7+)</div>
                                            <template x-for="(item, idx) in col2Links.filter(i => i.is_visible !== false).slice(6)" :key="'col2-' + idx">
                                                <div class="flex items-center gap-1 text-stone-700 truncate font-medium py-0.5">
                                                    <span x-text="item.icon || '🔗'"></span>
                                                    <span class="truncate" x-text="previewLang === 'kn' ? (item.label_kn || item.label_en) : item.label_en"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Column 3 Preview -->
                            <div class="space-y-1.5 pt-2 border-t border-[#DDD3BE]">
                                <div class="text-[10px] font-black text-stone-800 uppercase tracking-wider flex items-center gap-1">
                                    <span>🤝</span>
                                    <span x-text="previewLang === 'kn' ? col3TitleKn : col3TitleEn"></span>
                                </div>
                                <div class="space-y-1.5">
                                    <template x-for="(item, idx) in col3Links.filter(i => i.is_visible !== false)" :key="idx">
                                        <div>
                                            <!-- Green Button -->
                                            <template x-if="item.style === 'button'">
                                                <div class="px-2.5 py-1.5 rounded-lg bg-[#1C5A2C] text-white text-[10px] font-bold flex items-center justify-between shadow-2xs">
                                                    <span class="flex items-center gap-1.5">
                                                        <span x-text="item.icon || '💬'"></span>
                                                        <span x-text="previewLang === 'kn' ? (item.label_kn || item.label_en) : item.label_en"></span>
                                                    </span>
                                                    <span>→</span>
                                                </div>
                                            </template>
                                            <!-- Contact Chip -->
                                            <template x-if="item.style === 'chip'">
                                                <div class="px-2 py-1 rounded-md bg-white/70 border border-[#DDD3BE] text-stone-800 text-[9px] font-medium flex items-center gap-1 truncate">
                                                    <span x-text="item.icon || '✉️'"></span>
                                                    <span class="truncate" x-text="previewLang === 'kn' ? (item.label_kn || item.label_en) : item.label_en"></span>
                                                </div>
                                            </template>
                                            <!-- Alert -->
                                            <template x-if="item.style === 'alert'">
                                                <div class="px-2 py-1 rounded-md bg-amber-500/10 border border-amber-600/20 text-amber-900 text-[9px] font-semibold flex items-center gap-1">
                                                    <span x-text="item.icon || '⚠️'"></span>
                                                    <span x-text="previewLang === 'kn' ? (item.label_kn || item.label_en) : item.label_en"></span>
                                                </div>
                                            </template>
                                            <!-- Plain Link -->
                                            <template x-if="item.style === 'link'">
                                                <div class="flex items-center gap-1 text-[9px] text-stone-700 font-semibold">
                                                    <span x-text="item.icon || '🔗'"></span>
                                                    <span x-text="previewLang === 'kn' ? (item.label_kn || item.label_en) : item.label_en"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- MOBILE ACCORDION PREVIEW -->
                        <div x-show="previewMode === 'mobile'" class="space-y-2 pt-2 border-t border-[#DDD3BE]">
                            <!-- Mobile Accordion Column 2 -->
                            <div class="space-y-1">
                                <button type="button" @click="previewCol2Open = !previewCol2Open"
                                        class="w-full py-1.5 px-2.5 rounded-lg bg-white/70 border border-[#DDD3BE] flex items-center justify-between text-[10px] font-bold text-stone-800">
                                    <span class="flex items-center gap-1.5">
                                        <span>📂</span>
                                        <span x-text="previewLang === 'kn' ? col2TitleKn : col2TitleEn"></span>
                                    </span>
                                    <span class="text-stone-400 text-[8px]" x-text="previewCol2Open ? '▲' : '▼'"></span>
                                </button>
                                <div x-show="previewCol2Open" class="p-2 space-y-1 bg-white/50 rounded-lg border border-[#DDD3BE]">
                                    <template x-for="(item, idx) in col2Links.filter(i => i.is_visible !== false)" :key="idx">
                                        <div class="flex items-center justify-between text-[9px] text-stone-700 py-0.5">
                                            <span class="flex items-center gap-1">
                                                <span x-text="item.icon"></span>
                                                <span x-text="previewLang === 'kn' ? (item.label_kn || item.label_en) : item.label_en"></span>
                                            </span>
                                            <span class="text-stone-400">›</span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Mobile Accordion Column 3 -->
                            <div class="space-y-1">
                                <button type="button" @click="previewCol3Open = !previewCol3Open"
                                        class="w-full py-1.5 px-2.5 rounded-lg bg-white/70 border border-[#DDD3BE] flex items-center justify-between text-[10px] font-bold text-stone-800">
                                    <span class="flex items-center gap-1.5">
                                        <span>🤝</span>
                                        <span x-text="previewLang === 'kn' ? col3TitleKn : col3TitleEn"></span>
                                    </span>
                                    <span class="text-stone-400 text-[8px]" x-text="previewCol3Open ? '▲' : '▼'"></span>
                                </button>
                                <div x-show="previewCol3Open" class="p-2 space-y-1 bg-white/50 rounded-lg border border-[#DDD3BE]">
                                    <template x-for="(item, idx) in col3Links.filter(i => i.is_visible !== false)" :key="idx">
                                        <div>
                                            <template x-if="item.style === 'button'">
                                                <div class="px-2 py-1 rounded bg-[#1C5A2C] text-white text-[9px] font-bold flex items-center justify-between">
                                                    <span class="flex items-center gap-1">
                                                        <span x-text="item.icon"></span>
                                                        <span x-text="previewLang === 'kn' ? (item.label_kn || item.label_en) : item.label_en"></span>
                                                    </span>
                                                    <span>→</span>
                                                </div>
                                            </template>
                                            <template x-if="item.style !== 'button'">
                                                <div class="flex items-center justify-between text-[9px] text-stone-700 py-0.5">
                                                    <span class="flex items-center gap-1">
                                                        <span x-text="item.icon"></span>
                                                        <span x-text="previewLang === 'kn' ? (item.label_kn || item.label_en) : item.label_en"></span>
                                                    </span>
                                                    <span class="text-stone-400">›</span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Compact Bottom Bar (Left: Developed by, Center: Made with ❤️, Right: Copyright) -->
                        <div class="pt-2 border-t border-[#DDD3BE] flex flex-col sm:flex-row items-center justify-between gap-1 text-[9px] text-stone-600">
                            <!-- Left: Developed by -->
                            <div class="w-full sm:w-1/3 flex justify-center sm:justify-start items-center">
                                <span>Developed by&nbsp;</span>
                                <strong class="text-[#1C5A2C]" x-text="devName || 'Darshan Jayaram'"></strong>
                            </div>

                            <!-- Center: Pride -->
                            <div class="w-full sm:w-1/3 flex justify-center items-center text-center">
                                <span>🌾 Made with ❤️ for Farmers 🌱</span>
                            </div>

                            <!-- Right: Copyright -->
                            <div class="w-full sm:w-1/3 flex justify-center sm:justify-end items-center text-center sm:text-right">
                                <span x-text="copyrightText || '© 2026 Krushi Baandhava. All rights reserved.'"></span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="text-[11px] text-slate-500 text-center">
                    💡 Edits and visibility toggles update this preview instantly. Click "Save All Changes" to publish.
                </div>

            </div>
        </div>

    </div>
</div>
@endsection
