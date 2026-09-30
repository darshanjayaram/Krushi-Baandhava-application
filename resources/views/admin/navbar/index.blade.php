@extends('layouts.admin')

@section('header', 'Navbar & Menus CMS')

@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'desktop', // 'desktop', 'mobile_dock', 'drawer', 'feedback'
    previewMode: 'desktop', // 'desktop', 'mobile'
    previewLang: 'en', // 'en', 'kn'
    previewDrawerOpen: false,

    // 1. Desktop Navbar State
    desktopLinks: (@js($settings['desktop_links']) || []).map(item => ({
        ...item,
        is_visible: item.is_visible !== false,
        is_collapsed: false
    })),
    desktopAllCollapsed: false,
    showLocationPill: @js($settings['show_location_pill']),
    showLanguageToggle: @js($settings['show_language_toggle']),
    showHamburgerButton: @js($settings['show_hamburger_button']),

    // 2. Mobile Bottom Dock State
    mobileDockLinks: (@js($settings['mobile_dock_links']) || []).map(item => ({
        ...item,
        is_visible: item.is_visible !== false,
        is_collapsed: false
    })),
    mobileDockAllCollapsed: false,
    mobileDockStyle: @js($settings['mobile_dock_style']),
    mobileDockShowLabels: @js($settings['mobile_dock_show_labels']),

    // 3. Hamburger Drawer State
    drawerLinks: (@js($settings['drawer_links']) || []).map(item => ({
        ...item,
        is_visible: item.is_visible !== false,
        is_collapsed: false
    })),
    drawerAllCollapsed: false,
    drawerShowDistrict: @js($settings['drawer_show_district']),
    drawerShowWhatsapp: @js($settings['drawer_show_whatsapp']),
    drawerWhatsappUrl: @js($settings['drawer_whatsapp_url']),
    drawerWhatsappLabelEn: @js($settings['drawer_whatsapp_label_en']),
    drawerWhatsappLabelKn: @js($settings['drawer_whatsapp_label_kn']),
    drawerShowPwa: @js($settings['drawer_show_pwa']),

    // 4. Floating Feedback Button State
    showFeedbackFab: @js($settings['show_feedback_fab']),
    feedbackFabPulse: @js($settings['feedback_fab_pulse']),
    feedbackFabUrl: @js($settings['feedback_fab_url']),

    // --- Helper Methods: Desktop Links ---
    addDesktopLink() {
        this.desktopLinks.push({
            label_en: 'New Link',
            label_kn: 'ಹೊಸ ಲಿಂಕ್',
            url: '/',
            route_match: '',
            badge: '',
            badge_color: 'emerald',
            new_tab: false,
            is_visible: true,
            is_collapsed: false
        });
    },
    removeDesktopLink(index) {
        if (confirm('Delete this link from the Desktop Navbar?')) {
            this.desktopLinks.splice(index, 1);
        }
    },
    moveDesktopLink(index, direction) {
        const target = index + direction;
        if (target >= 0 && target < this.desktopLinks.length) {
            const item = this.desktopLinks.splice(index, 1)[0];
            this.desktopLinks.splice(target, 0, item);
        }
    },
    toggleDesktopCollapseAll() {
        this.desktopAllCollapsed = !this.desktopAllCollapsed;
        this.desktopLinks.forEach(i => i.is_collapsed = this.desktopAllCollapsed);
    },

    // --- Helper Methods: Mobile Dock Links ---
    addMobileDockLink() {
        this.mobileDockLinks.push({
            icon: 'rates',
            label_en: 'New Tab',
            label_kn: 'ಹೊಸ ಟ್ಯಾಬ್',
            url: '/',
            route_match: '',
            has_dot: false,
            new_tab: false,
            is_visible: true,
            is_collapsed: false
        });
    },
    removeMobileDockLink(index) {
        if (confirm('Delete this tab from the Mobile Bottom Dock?')) {
            this.mobileDockLinks.splice(index, 1);
        }
    },
    moveMobileDockLink(index, direction) {
        const target = index + direction;
        if (target >= 0 && target < this.mobileDockLinks.length) {
            const item = this.mobileDockLinks.splice(index, 1)[0];
            this.mobileDockLinks.splice(target, 0, item);
        }
    },
    toggleMobileDockCollapseAll() {
        this.mobileDockAllCollapsed = !this.mobileDockAllCollapsed;
        this.mobileDockLinks.forEach(i => i.is_collapsed = this.mobileDockAllCollapsed);
    },

    // --- Helper Methods: Drawer Links ---
    addDrawerLink() {
        this.drawerLinks.push({
            icon: '🌾',
            label_en: 'New Section',
            label_kn: 'ಹೊಸ ವಿಭಾಗ',
            subtitle_en: 'Section description',
            subtitle_kn: 'ವಿವರಣೆ',
            url: '/',
            badge: '',
            new_tab: false,
            is_visible: true,
            is_collapsed: false
        });
    },
    removeDrawerLink(index) {
        if (confirm('Delete this item from the Mobile Drawer Menu?')) {
            this.drawerLinks.splice(index, 1);
        }
    },
    moveDrawerLink(index, direction) {
        const target = index + direction;
        if (target >= 0 && target < this.drawerLinks.length) {
            const item = this.drawerLinks.splice(index, 1)[0];
            this.drawerLinks.splice(target, 0, item);
        }
    },
    toggleDrawerCollapseAll() {
        this.drawerAllCollapsed = !this.drawerAllCollapsed;
        this.drawerLinks.forEach(i => i.is_collapsed = this.drawerAllCollapsed);
    }
}">

    <!-- Page Header & Action Controls -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-slate-900 border border-slate-800 p-5 rounded-2xl shadow-xl">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500/20 to-teal-500/20 border border-emerald-500/30 flex items-center justify-center text-2xl text-emerald-400 shrink-0">
                🧭
            </div>
            <div>
                <h1 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
                    Navbar & Menus CMS
                    <span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                        Bilingual & Dynamic
                    </span>
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    Configure desktop navigation links, mobile floating island dock tabs, and slide-over hamburger drawer menus independently.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <!-- Reset Defaults Form -->
            <form action="{{ route('admin.navbar.reset') }}" method="POST" onsubmit="return confirm('Reset all navigation menus and settings to factory defaults? This will overwrite custom menus.');">
                @csrf
                <button type="submit" 
                        class="px-3.5 py-2 rounded-xl border border-slate-700 hover:border-rose-500/50 bg-slate-800/80 hover:bg-rose-950/30 text-slate-300 hover:text-rose-300 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4 text-slate-400 hover:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Reset Defaults</span>
                </button>
            </form>

            <!-- Save & Publish Button -->
            <button type="button" 
                    @click="$refs.navbarForm.submit()"
                    class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-xs shadow-lg shadow-emerald-900/30 active:scale-95 transition flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <span>Publish All Menus</span>
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-200 text-sm font-semibold flex items-center gap-3">
        <span class="text-lg">✓</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <!-- Section Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-800 overflow-x-auto pb-1">
        <button type="button" 
                @click="activeTab = 'desktop'; previewMode = 'desktop'"
                :class="activeTab === 'desktop' ? 'bg-emerald-950 text-emerald-300 border-emerald-500 shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60 border-transparent'"
                class="px-4 py-2.5 rounded-t-xl text-xs font-bold border-b-2 transition flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <span class="text-base">🖥️</span>
            <span>Desktop Header Navbar</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-800 text-slate-300" x-text="desktopLinks.length"></span>
        </button>

        <button type="button" 
                @click="activeTab = 'mobile_dock'; previewMode = 'mobile'; previewDrawerOpen = false"
                :class="activeTab === 'mobile_dock' ? 'bg-emerald-950 text-emerald-300 border-emerald-500 shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60 border-transparent'"
                class="px-4 py-2.5 rounded-t-xl text-xs font-bold border-b-2 transition flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <span class="text-base">📱</span>
            <span>Mobile Bottom Dock</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-800 text-slate-300" x-text="mobileDockLinks.length"></span>
        </button>

        <button type="button" 
                @click="activeTab = 'drawer'; previewMode = 'mobile'; previewDrawerOpen = true"
                :class="activeTab === 'drawer' ? 'bg-emerald-950 text-emerald-300 border-emerald-500 shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60 border-transparent'"
                class="px-4 py-2.5 rounded-t-xl text-xs font-bold border-b-2 transition flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <span class="text-base">☰</span>
            <span>Mobile Hamburger Drawer</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-800 text-slate-300" x-text="drawerLinks.length"></span>
        </button>

        <button type="button" 
                @click="activeTab = 'feedback'"
                :class="activeTab === 'feedback' ? 'bg-emerald-950 text-emerald-300 border-emerald-500 shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60 border-transparent'"
                class="px-4 py-2.5 rounded-t-xl text-xs font-bold border-b-2 transition flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <span class="text-base">💬</span>
            <span>Floating Action Button (FAB)</span>
        </button>
    </div>

    <!-- Main Workspace: Left Editor Column + Right Simulator Column -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left: Form Controls (7 cols on large screens) -->
        <div class="lg:col-span-7 space-y-6">
            <form x-ref="navbarForm" action="{{ route('admin.navbar.update') }}" method="POST">
                @csrf

                <!-- Serialized JSON Payloads -->
                <input type="hidden" name="navbar_desktop_links" :value="JSON.stringify(desktopLinks)">
                <input type="hidden" name="navbar_mobile_dock_links" :value="JSON.stringify(mobileDockLinks)">
                <input type="hidden" name="navbar_drawer_links" :value="JSON.stringify(drawerLinks)">

                <!-- Hidden Boolean Values -->
                <input type="hidden" name="navbar_show_location_pill" :value="showLocationPill ? '1' : '0'">
                <input type="hidden" name="navbar_show_language_toggle" :value="showLanguageToggle ? '1' : '0'">
                <input type="hidden" name="navbar_show_hamburger_button" :value="showHamburgerButton ? '1' : '0'">
                <input type="hidden" name="navbar_mobile_dock_style" :value="mobileDockStyle">
                <input type="hidden" name="navbar_mobile_dock_show_labels" :value="mobileDockShowLabels ? '1' : '0'">
                <input type="hidden" name="navbar_drawer_show_district" :value="drawerShowDistrict ? '1' : '0'">
                <input type="hidden" name="navbar_drawer_show_whatsapp" :value="drawerShowWhatsapp ? '1' : '0'">
                <input type="hidden" name="navbar_drawer_whatsapp_url" :value="drawerWhatsappUrl">
                <input type="hidden" name="navbar_drawer_whatsapp_label_en" :value="drawerWhatsappLabelEn">
                <input type="hidden" name="navbar_drawer_whatsapp_label_kn" :value="drawerWhatsappLabelKn">
                <input type="hidden" name="navbar_drawer_show_pwa" :value="drawerShowPwa ? '1' : '0'">
                <input type="hidden" name="navbar_show_feedback_fab" :value="showFeedbackFab ? '1' : '0'">
                <input type="hidden" name="navbar_feedback_fab_pulse" :value="feedbackFabPulse ? '1' : '0'">
                <input type="hidden" name="navbar_feedback_fab_url" :value="feedbackFabUrl">

                <!-- TAB 1: DESKTOP NAVBAR SETTINGS -->
                <div x-show="activeTab === 'desktop'" class="space-y-6">
                    
                    <!-- Desktop Utility Toggles -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>🛠️</span>
                                <span>Desktop Header Controls</span>
                            </h3>
                            <span class="text-xs text-slate-400">Manage interactive components</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label class="flex items-center justify-between p-3 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                                <div>
                                    <div class="text-xs font-bold text-slate-200">Location Pill</div>
                                    <div class="text-[10px] text-slate-400">📍 District Selector</div>
                                </div>
                                <input type="checkbox" x-model="showLocationPill" class="rounded bg-slate-800 border-slate-700 text-emerald-600 focus:ring-0">
                            </label>

                            <label class="flex items-center justify-between p-3 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                                <div>
                                    <div class="text-xs font-bold text-slate-200">Language Switch</div>
                                    <div class="text-[10px] text-slate-400">EN | ಕನ್ನಡ Toggle</div>
                                </div>
                                <input type="checkbox" x-model="showLanguageToggle" class="rounded bg-slate-800 border-slate-700 text-emerald-600 focus:ring-0">
                            </label>

                            <label class="flex items-center justify-between p-3 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                                <div>
                                    <div class="text-xs font-bold text-slate-200">Hamburger Menu</div>
                                    <div class="text-[10px] text-slate-400">☰ Top-Right Button</div>
                                </div>
                                <input type="checkbox" x-model="showHamburgerButton" class="rounded bg-slate-800 border-slate-700 text-emerald-600 focus:ring-0">
                            </label>
                        </div>
                    </div>

                    <!-- Desktop Menu Items CRUD List -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <div>
                                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                    <span>🔗</span>
                                    <span>Desktop Navigation Links</span>
                                </h3>
                                <p class="text-xs text-slate-400">These links display on large screens in the main navbar.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="toggleDesktopCollapseAll()" class="text-xs font-semibold text-slate-400 hover:text-slate-200 px-2.5 py-1 rounded-lg bg-slate-800 border border-slate-700 transition">
                                    <span x-text="desktopAllCollapsed ? 'Expand All' : 'Collapse All'"></span>
                                </button>
                                <button type="button" @click="addDesktopLink()" class="text-xs font-bold text-emerald-300 hover:text-white px-3 py-1.5 rounded-lg bg-emerald-950 border border-emerald-700/60 hover:bg-emerald-900 transition flex items-center gap-1.5">
                                    <span>+ Add Link</span>
                                </button>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <template x-if="desktopLinks.length === 0">
                            <div class="py-8 text-center text-slate-500 text-xs">
                                No desktop links added. Click "+ Add Link" to create one.
                            </div>
                        </template>

                        <!-- Links Repeater -->
                        <div class="space-y-3">
                            <template x-for="(item, index) in desktopLinks" :key="index">
                                <div class="bg-slate-950/70 border border-slate-800 rounded-xl overflow-hidden transition"
                                     :class="{ 'opacity-60 border-slate-800/50': !item.is_visible }">
                                    
                                    <!-- Item Header Row -->
                                    <div class="p-3 bg-slate-900/80 flex items-center justify-between gap-3 border-b border-slate-800/60">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span class="text-xs font-black text-slate-500" x-text="'#' + (index + 1)"></span>
                                            
                                            <span class="text-xs font-bold text-white truncate" x-text="item.label_en || 'Untitled Link'"></span>
                                            
                                            <span class="text-xs text-slate-400 truncate" x-text="'(' + (item.label_kn || 'ಕನ್ನಡ') + ')'"></span>

                                            <template x-if="item.badge">
                                                <span class="text-[10px] px-1.5 py-0.2 rounded font-black tracking-wider uppercase"
                                                      :class="{
                                                          'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': item.badge_color === 'emerald',
                                                          'bg-rose-500/20 text-rose-400 border border-rose-500/30': item.badge_color === 'rose',
                                                          'bg-amber-500/20 text-amber-400 border border-amber-500/30': item.badge_color === 'amber',
                                                          'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30': item.badge_color === 'cyan'
                                                      }"
                                                      x-text="item.badge"></span>
                                            </template>
                                        </div>

                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <!-- Show/Hide Toggle Button -->
                                            <button type="button" 
                                                    @click="item.is_visible = !item.is_visible"
                                                    :title="item.is_visible ? 'Visible on site' : 'Hidden from site'"
                                                    class="p-1 rounded text-xs transition"
                                                    :class="item.is_visible ? 'text-emerald-400 hover:text-emerald-300' : 'text-slate-600 hover:text-slate-400'">
                                                <span x-text="item.is_visible ? '👁️' : '🙈'"></span>
                                            </button>

                                            <!-- Reorder Up -->
                                            <button type="button" 
                                                    @click="moveDesktopLink(index, -1)" 
                                                    :disabled="index === 0"
                                                    class="p-1 text-slate-400 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed">
                                                ▲
                                            </button>

                                            <!-- Reorder Down -->
                                            <button type="button" 
                                                    @click="moveDesktopLink(index, 1)" 
                                                    :disabled="index === desktopLinks.length - 1"
                                                    class="p-1 text-slate-400 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed">
                                                ▼
                                            </button>

                                            <!-- Toggle Accordion Collapse -->
                                            <button type="button" 
                                                    @click="item.is_collapsed = !item.is_collapsed"
                                                    class="p-1 text-slate-400 hover:text-white text-xs">
                                                <span x-text="item.is_collapsed ? '⚙️ Edit' : '▲ Close'"></span>
                                            </button>

                                            <!-- Delete Link -->
                                            <button type="button" 
                                                    @click="removeDesktopLink(index)"
                                                    class="p-1 text-rose-400 hover:text-rose-300 ml-1">
                                                ✕
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Item Body Fields (Collapsible) -->
                                    <div x-show="!item.is_collapsed" class="p-3.5 space-y-3">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1">English Label</label>
                                                <input type="text" x-model="item.label_en" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white focus:border-emerald-500 focus:ring-0">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1 font-kannada">ಕನ್ನಡ ಹೆಸರು (Kannada Label)</label>
                                                <input type="text" x-model="item.label_kn" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white font-kannada focus:border-emerald-500 focus:ring-0">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div class="sm:col-span-2">
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1">Target URL or Route</label>
                                                <div class="flex gap-2">
                                                    <input type="text" x-model="item.url" placeholder="/crops" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white focus:border-emerald-500 focus:ring-0">
                                                    <select @change="if($event.target.value) { item.url = $event.target.value; $event.target.value = ''; }" class="text-xs bg-slate-800 border border-slate-700 text-slate-300 rounded-lg px-2 py-1">
                                                        <option value="">Quick Pick</option>
                                                        <option value="/crops">/crops (Rates)</option>
                                                        <option value="/schemes">/schemes (Schemes)</option>
                                                        <option value="/videos">/videos (Videos)</option>
                                                        <option value="/news">/news (News)</option>
                                                        <option value="/articles">/articles (Guides)</option>
                                                        <option value="/weather">/weather (Weather)</option>
                                                        <option value="/feedback">/feedback (Helpdesk)</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1">Active Route Pattern</label>
                                                <input type="text" x-model="item.route_match" placeholder="farmer.crops.*" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white focus:border-emerald-500 focus:ring-0">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center pt-1 border-t border-slate-900">
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1">Optional Pill Badge</label>
                                                <input type="text" x-model="item.badge" placeholder="e.g. LIVE, NEW" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1 text-white focus:border-emerald-500 focus:ring-0">
                                            </div>

                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1">Badge Color</label>
                                                <select x-model="item.badge_color" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1 text-white focus:border-emerald-500 focus:ring-0">
                                                    <option value="emerald">Emerald Green</option>
                                                    <option value="rose">Rose Red</option>
                                                    <option value="amber">Amber Yellow</option>
                                                    <option value="cyan">Cyan Blue</option>
                                                </select>
                                            </div>

                                            <div class="pt-3">
                                                <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
                                                    <input type="checkbox" x-model="item.new_tab" class="rounded bg-slate-800 border-slate-700 text-emerald-600 focus:ring-0">
                                                    <span>Open in new tab</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: MOBILE BOTTOM DOCK (ASTRO-STYLE) -->
                <div x-show="activeTab === 'mobile_dock'" class="space-y-6">
                    
                    <!-- Dock Layout Options & Capacity Notice -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>📱</span>
                                <span>Mobile Dock Layout Options</span>
                            </h3>
                            
                            <!-- Capacity Badge -->
                            <div class="flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-full"
                                 :class="mobileDockLinks.length <= 5 ? 'bg-emerald-950 text-emerald-300 border border-emerald-700/60' : 'bg-amber-950 text-amber-300 border border-amber-700/60'">
                                <span x-text="mobileDockLinks.length + ' tabs active'"></span>
                                <span class="text-[10px] text-slate-400">(4-5 recommended)</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1.5">Dock Appearance</label>
                                <select x-model="mobileDockStyle" class="w-full text-xs bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white focus:border-emerald-500 focus:ring-0">
                                    <option value="floating">Option A: Astro-Style Floating Island Dock (Recommended)</option>
                                    <option value="fixed">Classic Full-Width Fixed Bottom Bar</option>
                                </select>
                            </div>

                            <div class="flex items-center pt-5">
                                <label class="flex items-center gap-2.5 cursor-pointer text-xs font-bold text-slate-200">
                                    <input type="checkbox" x-model="mobileDockShowLabels" class="rounded bg-slate-800 border-slate-700 text-emerald-600 focus:ring-0">
                                    <span>Show Text Labels below Icons</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Dock Items Repeater -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <div>
                                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                    <span>🏝️</span>
                                    <span>Mobile Dock Navigation Tabs</span>
                                </h3>
                                <p class="text-xs text-slate-400">Quick-access bottom navigation buttons for smartphones & PWA.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="toggleMobileDockCollapseAll()" class="text-xs font-semibold text-slate-400 hover:text-slate-200 px-2.5 py-1 rounded-lg bg-slate-800 border border-slate-700 transition">
                                    <span x-text="mobileDockAllCollapsed ? 'Expand All' : 'Collapse All'"></span>
                                </button>
                                <button type="button" @click="addMobileDockLink()" class="text-xs font-bold text-emerald-300 hover:text-white px-3 py-1.5 rounded-lg bg-emerald-950 border border-emerald-700/60 hover:bg-emerald-900 transition flex items-center gap-1.5">
                                    <span>+ Add Tab</span>
                                </button>
                            </div>
                        </div>

                        <!-- Repeater Cards -->
                        <div class="space-y-3">
                            <template x-for="(item, index) in mobileDockLinks" :key="index">
                                <div class="bg-slate-950/70 border border-slate-800 rounded-xl overflow-hidden transition"
                                     :class="{ 'opacity-60 border-slate-800/50': !item.is_visible }">
                                    
                                    <!-- Header Row -->
                                    <div class="p-3 bg-slate-900/80 flex items-center justify-between gap-3 border-b border-slate-800/60">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span class="text-xs font-black text-slate-500" x-text="'#' + (index + 1)"></span>
                                            
                                            <!-- Icon Preview Pill -->
                                            <div class="w-6 h-6 rounded-lg bg-slate-800 flex items-center justify-center text-xs text-emerald-400 shrink-0">
                                                <template x-if="item.icon === 'home'"><span>🏠</span></template>
                                                <template x-if="item.icon === 'rates'"><span>📊</span></template>
                                                <template x-if="item.icon === 'schemes'"><span>📜</span></template>
                                                <template x-if="item.icon === 'weather'"><span>⛅</span></template>
                                                <template x-if="item.icon === 'videos'"><span>▶️</span></template>
                                                <template x-if="item.icon === 'news'"><span>📰</span></template>
                                                <template x-if="item.icon === 'help'"><span>💬</span></template>
                                                <template x-if="!['home','rates','schemes','weather','videos','news','help'].includes(item.icon)">
                                                    <span x-text="item.icon"></span>
                                                </template>
                                            </div>

                                            <span class="text-xs font-bold text-white truncate" x-text="item.label_en || 'Tab'"></span>
                                            <span class="text-xs text-slate-400 truncate" x-text="'(' + (item.label_kn || 'ಕನ್ನಡ') + ')'"></span>

                                            <template x-if="item.has_dot">
                                                <span class="w-2 h-2 rounded-full bg-amber-400" title="Indicator Dot Active"></span>
                                            </template>
                                        </div>

                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <!-- Show/Hide Toggle Button -->
                                            <button type="button" 
                                                    @click="item.is_visible = !item.is_visible"
                                                    :title="item.is_visible ? 'Visible on site' : 'Hidden from site'"
                                                    class="p-1 rounded text-xs transition"
                                                    :class="item.is_visible ? 'text-emerald-400 hover:text-emerald-300' : 'text-slate-600 hover:text-slate-400'">
                                                <span x-text="item.is_visible ? '👁️' : '🙈'"></span>
                                            </button>

                                            <!-- Reorder Up -->
                                            <button type="button" 
                                                    @click="moveMobileDockLink(index, -1)" 
                                                    :disabled="index === 0"
                                                    class="p-1 text-slate-400 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed">
                                                ▲
                                            </button>

                                            <!-- Reorder Down -->
                                            <button type="button" 
                                                    @click="moveMobileDockLink(index, 1)" 
                                                    :disabled="index === mobileDockLinks.length - 1"
                                                    class="p-1 text-slate-400 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed">
                                                ▼
                                            </button>

                                            <!-- Toggle Accordion Collapse -->
                                            <button type="button" 
                                                    @click="item.is_collapsed = !item.is_collapsed"
                                                    class="p-1 text-slate-400 hover:text-white text-xs">
                                                <span x-text="item.is_collapsed ? '⚙️ Edit' : '▲ Close'"></span>
                                            </button>

                                            <!-- Delete Link -->
                                            <button type="button" 
                                                    @click="removeMobileDockLink(index)"
                                                    class="p-1 text-rose-400 hover:text-rose-300 ml-1">
                                                ✕
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Fields -->
                                    <div x-show="!item.is_collapsed" class="p-3.5 space-y-3">
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1">Tab Vector Icon / Emoji</label>
                                                <div class="flex gap-2">
                                                    <select x-model="item.icon" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white focus:border-emerald-500 focus:ring-0">
                                                        <option value="home">SVG: Home (ಮನೆ)</option>
                                                        <option value="rates">SVG: Rates Chart (ದರಗಳು)</option>
                                                        <option value="schemes">SVG: Govt Schemes (ಯೋಜನೆ)</option>
                                                        <option value="weather">SVG: Weather Radar (ಹವಾಮಾನ)</option>
                                                        <option value="videos">SVG: Play Videos (ವಿಡಿಯೋ)</option>
                                                        <option value="news">SVG: Newspaper (ಸುದ್ದಿ)</option>
                                                        <option value="help">SVG: Message Help (ಸಹಾಯ)</option>
                                                    </select>
                                                    <input type="text" x-model="item.icon" placeholder="or 🌾" title="Or type custom emoji" class="w-14 text-center text-xs bg-slate-900 border border-slate-700 rounded-lg text-white">
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1">English Label</label>
                                                <input type="text" x-model="item.label_en" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white focus:border-emerald-500 focus:ring-0">
                                            </div>

                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1 font-kannada">ಕನ್ನಡ ಹೆಸರು</label>
                                                <input type="text" x-model="item.label_kn" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white font-kannada focus:border-emerald-500 focus:ring-0">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div class="sm:col-span-2">
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1">Target URL</label>
                                                <div class="flex gap-2">
                                                    <input type="text" x-model="item.url" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white focus:border-emerald-500 focus:ring-0">
                                                    <select @change="if($event.target.value) { item.url = $event.target.value; $event.target.value = ''; }" class="text-xs bg-slate-800 border border-slate-700 text-slate-300 rounded-lg px-2 py-1">
                                                        <option value="">Quick Pick</option>
                                                        <option value="/">/ (Home)</option>
                                                        <option value="/crops">/crops (Rates)</option>
                                                        <option value="/schemes">/schemes (Schemes)</option>
                                                        <option value="/weather">/weather (Weather)</option>
                                                        <option value="/videos">/videos (Videos)</option>
                                                        <option value="/news">/news (News)</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1">Active Pattern</label>
                                                <input type="text" x-model="item.route_match" placeholder="home, farmer.crops.*" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white focus:border-emerald-500 focus:ring-0">
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-6 pt-1 border-t border-slate-900">
                                            <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
                                                <input type="checkbox" x-model="item.has_dot" class="rounded bg-slate-800 border-slate-700 text-amber-500 focus:ring-0">
                                                <span>Show Amber Live Indicator Dot</span>
                                            </label>

                                            <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
                                                <input type="checkbox" x-model="item.new_tab" class="rounded bg-slate-800 border-slate-700 text-emerald-600 focus:ring-0">
                                                <span>Open in new tab</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: HAMBURGER DRAWER MENU -->
                <div x-show="activeTab === 'drawer'" class="space-y-6">
                    
                    <!-- Drawer Global Toggles & WhatsApp Action Setup -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>☰</span>
                                <span>Slide-Over Drawer Components</span>
                            </h3>
                            <span class="text-xs text-slate-400">Controls inside the mobile slide panel</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label class="flex items-center justify-between p-3 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                                <div>
                                    <div class="text-xs font-bold text-slate-200">District Selector</div>
                                    <div class="text-[10px] text-slate-400">Header Location card</div>
                                </div>
                                <input type="checkbox" x-model="drawerShowDistrict" class="rounded bg-slate-800 border-slate-700 text-emerald-600 focus:ring-0">
                            </label>

                            <label class="flex items-center justify-between p-3 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                                <div>
                                    <div class="text-xs font-bold text-slate-200">WhatsApp Helpdesk</div>
                                    <div class="text-[10px] text-slate-400">Community Channel banner</div>
                                </div>
                                <input type="checkbox" x-model="drawerShowWhatsapp" class="rounded bg-slate-800 border-slate-700 text-emerald-600 focus:ring-0">
                            </label>

                            <label class="flex items-center justify-between p-3 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                                <div>
                                    <div class="text-xs font-bold text-slate-200">PWA Install Button</div>
                                    <div class="text-[10px] text-slate-400">Add to Home Screen</div>
                                </div>
                                <input type="checkbox" x-model="drawerShowPwa" class="rounded bg-slate-800 border-slate-700 text-emerald-600 focus:ring-0">
                            </label>
                        </div>

                        <!-- WhatsApp Action Details -->
                        <div x-show="drawerShowWhatsapp" class="pt-3 border-t border-slate-800/80 space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-300 mb-1">WhatsApp Group / Channel URL</label>
                                    <input type="text" x-model="drawerWhatsappUrl" placeholder="https://chat.whatsapp.com/..." class="w-full text-xs bg-slate-950 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white focus:border-emerald-500 focus:ring-0">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-300 mb-1">English Button Label</label>
                                    <input type="text" x-model="drawerWhatsappLabelEn" class="w-full text-xs bg-slate-950 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white focus:border-emerald-500 focus:ring-0">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-300 mb-1 font-kannada">ಕನ್ನಡ ಲೇಬಲ್</label>
                                    <input type="text" x-model="drawerWhatsappLabelKn" class="w-full text-xs bg-slate-950 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white font-kannada focus:border-emerald-500 focus:ring-0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Drawer Directory Links Repeater -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <div>
                                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                    <span>📋</span>
                                    <span>Drawer Directory Items</span>
                                </h3>
                                <p class="text-xs text-slate-400">All navigation sections listed inside the hamburger slide-over menu.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="toggleDrawerCollapseAll()" class="text-xs font-semibold text-slate-400 hover:text-slate-200 px-2.5 py-1 rounded-lg bg-slate-800 border border-slate-700 transition">
                                    <span x-text="drawerAllCollapsed ? 'Expand All' : 'Collapse All'"></span>
                                </button>
                                <button type="button" @click="addDrawerLink()" class="text-xs font-bold text-emerald-300 hover:text-white px-3 py-1.5 rounded-lg bg-emerald-950 border border-emerald-700/60 hover:bg-emerald-900 transition flex items-center gap-1.5">
                                    <span>+ Add Section</span>
                                </button>
                            </div>
                        </div>

                        <!-- Repeater Cards -->
                        <div class="space-y-3">
                            <template x-for="(item, index) in drawerLinks" :key="index">
                                <div class="bg-slate-950/70 border border-slate-800 rounded-xl overflow-hidden transition"
                                     :class="{ 'opacity-60 border-slate-800/50': !item.is_visible }">
                                    
                                    <!-- Header Row -->
                                    <div class="p-3 bg-slate-900/80 flex items-center justify-between gap-3 border-b border-slate-800/60">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span class="text-xs font-black text-slate-500" x-text="'#' + (index + 1)"></span>
                                            
                                            <!-- Emoji/Icon -->
                                            <span class="text-base" x-text="item.icon || '🌾'"></span>

                                            <span class="text-xs font-bold text-white truncate" x-text="item.label_en || 'Untitled'"></span>
                                            <span class="text-xs text-slate-400 truncate" x-text="'(' + (item.label_kn || 'ಕನ್ನಡ') + ')'"></span>

                                            <template x-if="item.badge">
                                                <span class="text-[10px] px-1.5 py-0.2 rounded font-black tracking-wider uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30"
                                                      x-text="item.badge"></span>
                                            </template>
                                        </div>

                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <!-- Show/Hide Toggle Button -->
                                            <button type="button" 
                                                    @click="item.is_visible = !item.is_visible"
                                                    :title="item.is_visible ? 'Visible in drawer' : 'Hidden from drawer'"
                                                    class="p-1 rounded text-xs transition"
                                                    :class="item.is_visible ? 'text-emerald-400 hover:text-emerald-300' : 'text-slate-600 hover:text-slate-400'">
                                                <span x-text="item.is_visible ? '👁️' : '🙈'"></span>
                                            </button>

                                            <!-- Reorder Up -->
                                            <button type="button" 
                                                    @click="moveDrawerLink(index, -1)" 
                                                    :disabled="index === 0"
                                                    class="p-1 text-slate-400 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed">
                                                ▲
                                            </button>

                                            <!-- Reorder Down -->
                                            <button type="button" 
                                                    @click="moveDrawerLink(index, 1)" 
                                                    :disabled="index === drawerLinks.length - 1"
                                                    class="p-1 text-slate-400 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed">
                                                ▼
                                            </button>

                                            <!-- Toggle Accordion Collapse -->
                                            <button type="button" 
                                                    @click="item.is_collapsed = !item.is_collapsed"
                                                    class="p-1 text-slate-400 hover:text-white text-xs">
                                                <span x-text="item.is_collapsed ? '⚙️ Edit' : '▲ Close'"></span>
                                            </button>

                                            <!-- Delete Link -->
                                            <button type="button" 
                                                    @click="removeDrawerLink(index)"
                                                    class="p-1 text-rose-400 hover:text-rose-300 ml-1">
                                                ✕
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Fields -->
                                    <div x-show="!item.is_collapsed" class="p-3.5 space-y-3">
                                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1">Icon / Emoji</label>
                                                <input type="text" x-model="item.icon" class="w-full text-center text-xs bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-white">
                                            </div>
                                            <div class="sm:col-span-3">
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1">English Title</label>
                                                <input type="text" x-model="item.label_en" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white focus:border-emerald-500 focus:ring-0">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1 font-kannada">ಕನ್ನಡ ಹೆಸರು</label>
                                                <input type="text" x-model="item.label_kn" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white font-kannada focus:border-emerald-500 focus:ring-0">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1">Target URL</label>
                                                <input type="text" x-model="item.url" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white focus:border-emerald-500 focus:ring-0">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1">English Subtitle / Hint</label>
                                                <input type="text" x-model="item.subtitle_en" placeholder="Short description" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white focus:border-emerald-500 focus:ring-0">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1 font-kannada">ಕನ್ನಡ ವಿವರಣೆ</label>
                                                <input type="text" x-model="item.subtitle_kn" placeholder="ಸಂಕ್ಷಿಪ್ತ ವಿವರಣೆ" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white font-kannada focus:border-emerald-500 focus:ring-0">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 border-t border-slate-900">
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-300 mb-1">Badge Tag</label>
                                                <input type="text" x-model="item.badge" placeholder="e.g. LIVE, NEW, HELP" class="w-full text-xs bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1 text-white">
                                            </div>
                                            <div class="pt-4">
                                                <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
                                                    <input type="checkbox" x-model="item.new_tab" class="rounded bg-slate-800 border-slate-700 text-emerald-600 focus:ring-0">
                                                    <span>Open in new tab</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: FLOATING ACTION BUTTON (FAB) -->
                <div x-show="activeTab === 'feedback'" class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <span>💬</span>
                            <span>Floating Action Button (Farmer Helpdesk FAB)</span>
                        </h3>
                        <span class="text-xs text-slate-400">Positioned above the bottom dock</span>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="flex items-center justify-between p-4 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                                <div>
                                    <div class="text-xs font-bold text-slate-200">Show Floating Feedback FAB</div>
                                    <div class="text-[10px] text-slate-400">Display button across public farmer pages</div>
                                </div>
                                <input type="checkbox" x-model="showFeedbackFab" class="rounded bg-slate-800 border-slate-700 text-emerald-600 focus:ring-0">
                            </label>

                            <label class="flex items-center justify-between p-4 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                                <div>
                                    <div class="text-xs font-bold text-slate-200">Live Pulse Radar Dot</div>
                                    <div class="text-[10px] text-slate-400">Animated amber attention badge</div>
                                </div>
                                <input type="checkbox" x-model="feedbackFabPulse" class="rounded bg-slate-800 border-slate-700 text-amber-500 focus:ring-0">
                            </label>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Target Action URL</label>
                            <input type="text" x-model="feedbackFabUrl" placeholder="/feedback" class="w-full text-xs bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white focus:border-emerald-500 focus:ring-0">
                            <p class="text-[11px] text-slate-500 mt-1">Default is <code class="text-emerald-400">/feedback</code> for farmer price grievance & voice notes.</p>
                        </div>
                    </div>
                </div>

            </form>
        </div>

        <!-- Right: Live Interactive Simulator (5 cols on large screens) -->
        <div class="lg:col-span-5 sticky top-20 space-y-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
                
                <!-- Simulator Header Controls -->
                <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            Live Simulator
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Preview Mode Switcher (Desktop vs Mobile) -->
                        <div class="flex items-center bg-slate-950 rounded-lg p-0.5 border border-slate-800 text-[11px] font-bold">
                            <button type="button" @click="previewMode = 'desktop'" :class="previewMode === 'desktop' ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:text-white'" class="px-2 py-0.5 rounded transition">
                                🖥️ Desktop
                            </button>
                            <button type="button" @click="previewMode = 'mobile'" :class="previewMode === 'mobile' ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:text-white'" class="px-2 py-0.5 rounded transition">
                                📱 Mobile
                            </button>
                        </div>

                        <!-- Language Switcher in Simulator -->
                        <div class="flex items-center bg-slate-950 rounded-lg p-0.5 border border-slate-800 text-[11px] font-bold">
                            <button type="button" @click="previewLang = 'en'" :class="previewLang === 'en' ? 'bg-slate-800 text-white' : 'text-slate-500 hover:text-slate-300'" class="px-2 py-0.5 rounded transition">
                                EN
                            </button>
                            <button type="button" @click="previewLang = 'kn'" :class="previewLang === 'kn' ? 'bg-slate-800 text-white font-kannada' : 'text-slate-500 hover:text-slate-300 font-kannada'" class="px-2 py-0.5 rounded transition">
                                ಕನ್ನಡ
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 1. DESKTOP VIEW SIMULATOR -->
                <div x-show="previewMode === 'desktop'" class="space-y-3">
                    <div class="text-[11px] text-slate-400 font-medium">Desktop Navigation Bar Preview:</div>
                    
                    <div class="bg-[#F5EFE6] border-2 border-[#D9CEB8] rounded-xl p-3 shadow-md space-y-2">
                        <div class="flex items-center justify-between gap-3">
                            <!-- Brand -->
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-[#1C5A2C] text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                    🌾
                                </div>
                                <div>
                                    <div class="text-xs font-black text-[#1C5A2C] leading-none" x-text="previewLang === 'en' ? 'Krushi Baandhava' : 'ಕೃಷಿ ಬಾಂಧವ'"></div>
                                    <div class="text-[9px] text-stone-500 leading-none mt-0.5" x-text="previewLang === 'en' ? 'Direct APMC Market Rates' : 'ನೇರ ಮಾರುಕಟ್ಟೆ ದರಗಳು'"></div>
                                </div>
                            </div>

                            <!-- Dynamic Desktop Links -->
                            <div class="hidden sm:flex items-center gap-1">
                                <template x-for="(link, lIdx) in desktopLinks" :key="lIdx">
                                    <template x-if="link.is_visible">
                                        <div class="px-2 py-1 rounded-md text-[11px] font-bold transition flex items-center gap-1"
                                             :class="lIdx === 0 ? 'bg-[#E5DDC9] text-stone-900' : 'text-stone-600'">
                                            <span x-text="previewLang === 'en' ? (link.label_en || 'Link') : (link.label_kn || link.label_en)"></span>
                                            <template x-if="link.badge">
                                                <span class="text-[8px] px-1 py-0.2 rounded font-black tracking-wide uppercase"
                                                      :class="{
                                                          'bg-emerald-600 text-white': link.badge_color === 'emerald',
                                                          'bg-rose-600 text-white': link.badge_color === 'rose',
                                                          'bg-amber-500 text-slate-950': link.badge_color === 'amber',
                                                          'bg-cyan-600 text-white': link.badge_color === 'cyan'
                                                      }"
                                                      x-text="link.badge"></span>
                                            </template>
                                        </div>
                                    </template>
                                </template>
                            </div>

                            <!-- Utilities -->
                            <div class="flex items-center gap-1.5 shrink-0">
                                <template x-if="showLocationPill">
                                    <div class="flex items-center gap-1 bg-white border border-stone-200 rounded-full px-2 py-0.5 text-[10px] font-bold text-stone-800 shadow-2xs">
                                        <span>📍</span>
                                        <span x-text="previewLang === 'en' ? 'Shivamogga' : 'ಶಿವಮೊಗ್ಗ'"></span>
                                    </div>
                                </template>

                                <template x-if="showLanguageToggle">
                                    <div class="bg-white rounded-lg p-0.5 border border-stone-200 text-[10px] font-bold flex">
                                        <span class="px-1.5 py-0.5 rounded bg-[#1C5A2C] text-white">EN</span>
                                        <span class="px-1.5 py-0.5 text-stone-500 font-kannada">ಕನ್ನಡ</span>
                                    </div>
                                </template>

                                <template x-if="showHamburgerButton">
                                    <div class="w-7 h-7 rounded-lg bg-white border border-stone-300 flex items-center justify-center text-xs text-stone-700 shadow-2xs">
                                        ☰
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. MOBILE PHONE FRAME SIMULATOR -->
                <div x-show="previewMode === 'mobile'" class="space-y-3">
                    <div class="flex items-center justify-between text-[11px] text-slate-400 font-medium">
                        <span>Mobile Viewport (360x640):</span>
                        <button type="button" @click="previewDrawerOpen = !previewDrawerOpen" class="text-emerald-400 hover:text-emerald-300 font-bold">
                            <span x-text="previewDrawerOpen ? 'Close Drawer ✕' : 'Test Hamburger ☰'"></span>
                        </button>
                    </div>

                    <!-- Smartphone Mockup Container -->
                    <div class="relative w-full max-w-[320px] mx-auto h-[460px] bg-[#F5EFE6] rounded-3xl border-4 border-slate-700 shadow-2xl overflow-hidden flex flex-col justify-between select-none">
                        
                        <!-- Mobile Header Bar -->
                        <div class="h-12 bg-[#F5EFE6]/95 border-b border-[#D9CEB8] px-3 flex items-center justify-between shrink-0 shadow-xs z-10">
                            <div class="flex items-center gap-1.5">
                                <div class="w-6 h-6 rounded-md bg-[#1C5A2C] text-white flex items-center justify-center text-[10px] font-black">
                                    🌾
                                </div>
                                <span class="text-xs font-black text-[#1C5A2C]" x-text="previewLang === 'en' ? 'Krushi Baandhava' : 'ಕೃಷಿ ಬಾಂಧವ'"></span>
                            </div>

                            <div class="flex items-center gap-1">
                                <template x-if="showLanguageToggle">
                                    <div class="bg-white rounded px-1.5 py-0.5 border border-stone-200 text-[9px] font-bold text-stone-700">
                                        <span x-text="previewLang.toUpperCase()"></span>
                                    </div>
                                </template>

                                <template x-if="showHamburgerButton">
                                    <button type="button" @click="previewDrawerOpen = !previewDrawerOpen" class="w-7 h-7 rounded-lg bg-white border border-stone-300 flex items-center justify-center text-xs text-stone-800 shadow-xs cursor-pointer">
                                        ☰
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Simulated Body Content -->
                        <div class="flex-1 p-3 overflow-y-auto space-y-2 bg-[#F5EFE6]">
                            <div class="p-2.5 rounded-xl bg-white border border-stone-200/80 shadow-xs space-y-1">
                                <div class="text-[11px] font-black text-stone-800">🌽 Maize / ಮೆಕ್ಕೆಜೋಳ</div>
                                <div class="text-[10px] text-emerald-700 font-extrabold">₹2,450 / Quintal • Shimoga APMC</div>
                            </div>
                            <div class="p-2.5 rounded-xl bg-white border border-stone-200/80 shadow-xs space-y-1">
                                <div class="text-[11px] font-black text-stone-800">🌰 Arecanut / ಅಡಿಕೆ (Rashi)</div>
                                <div class="text-[10px] text-emerald-700 font-extrabold">₹54,200 / Quintal • High Demand</div>
                            </div>
                        </div>

                        <!-- Simulated Slide-Over Drawer Overlay -->
                        <div x-show="previewDrawerOpen" 
                             class="absolute inset-0 z-30 bg-black/50 backdrop-blur-xs flex justify-end"
                             @click="previewDrawerOpen = false">
                            <div class="w-[85%] h-full bg-[#FAF8F5] border-l border-[#DDD2BE] p-3 flex flex-col justify-between shadow-2xl"
                                 @click.stop>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between border-b border-[#DDD2BE] pb-2">
                                        <span class="text-xs font-black text-[#1C5A2C]">Menu Directory</span>
                                        <button type="button" @click="previewDrawerOpen = false" class="text-xs text-stone-500 font-black">✕</button>
                                    </div>

                                    <template x-if="drawerShowDistrict">
                                        <div class="p-2 rounded-lg bg-white border border-stone-200 text-[10px] font-bold text-stone-800 flex items-center gap-1.5">
                                            <span>📍</span>
                                            <span x-text="previewLang === 'en' ? 'Active: Shivamogga' : 'ಸ್ಥಳ: ಶಿವಮೊಗ್ಗ'"></span>
                                        </div>
                                    </template>

                                    <!-- Drawer Links List -->
                                    <div class="space-y-1 overflow-y-auto max-h-[220px]">
                                        <template x-for="(dItem, dIdx) in drawerLinks" :key="dIdx">
                                            <template x-if="dItem.is_visible">
                                                <div class="p-1.5 rounded-lg bg-white border border-stone-200/70 flex items-center justify-between text-[10px] font-bold text-stone-800">
                                                    <div class="flex items-center gap-1.5 truncate">
                                                        <span x-text="dItem.icon || '🌾'"></span>
                                                        <span class="truncate" x-text="previewLang === 'en' ? (dItem.label_en || 'Item') : (dItem.label_kn || dItem.label_en)"></span>
                                                    </div>
                                                    <template x-if="dItem.badge">
                                                        <span class="text-[8px] px-1 py-0.2 rounded font-black bg-emerald-100 text-emerald-800 uppercase" x-text="dItem.badge"></span>
                                                    </template>
                                                </div>
                                            </template>
                                        </template>
                                    </div>
                                </div>

                                <!-- WhatsApp CTA in Drawer -->
                                <template x-if="drawerShowWhatsapp">
                                    <div class="p-2 rounded-lg bg-emerald-50 border border-emerald-200 text-[10px] font-bold text-emerald-900 text-center">
                                        <span x-text="previewLang === 'en' ? (drawerWhatsappLabelEn || 'Join WhatsApp Helpdesk') : (drawerWhatsappLabelKn || 'ವಾಟ್ಸಾಪ್ ಸಹಾಯವಾಣಿ')"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Floating Action Button (FAB) in Simulator -->
                        <template x-if="showFeedbackFab">
                            <div class="absolute bottom-16 right-3 z-20 w-8 h-8 rounded-full bg-gradient-to-br from-[#134423] to-[#257238] border-2 border-white flex items-center justify-center text-white shadow-lg text-[10px]">
                                <template x-if="feedbackFabPulse">
                                    <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping"></span>
                                </template>
                                💬
                            </div>
                        </template>

                        <!-- Simulated Astro Floating Island Dock -->
                        <div class="p-2.5 z-20">
                            <div :class="mobileDockStyle === 'floating' ? 'rounded-2xl border border-stone-200/90 shadow-lg' : 'rounded-none border-t border-stone-200'"
                                 class="bg-white/95 backdrop-blur-md px-1.5 py-1 flex items-center justify-around">
                                <template x-for="(tab, tIdx) in mobileDockLinks" :key="tIdx">
                                    <template x-if="tab.is_visible">
                                        <div class="flex flex-col items-center justify-center py-1 px-2 rounded-xl transition"
                                             :class="tIdx === 0 ? 'bg-emerald-50 text-[#1C5A2C] font-black' : 'text-stone-500 font-bold'">
                                            <div class="relative text-sm">
                                                <template x-if="tab.icon === 'home'"><span>🏠</span></template>
                                                <template x-if="tab.icon === 'rates'"><span>📊</span></template>
                                                <template x-if="tab.icon === 'schemes'"><span>📜</span></template>
                                                <template x-if="tab.icon === 'weather'"><span>⛅</span></template>
                                                <template x-if="tab.icon === 'videos'"><span>▶️</span></template>
                                                <template x-if="tab.icon === 'news'"><span>📰</span></template>
                                                <template x-if="tab.icon === 'help'"><span>💬</span></template>
                                                <template x-if="!['home','rates','schemes','weather','videos','news','help'].includes(tab.icon)">
                                                    <span x-text="tab.icon"></span>
                                                </template>

                                                <template x-if="tab.has_dot">
                                                    <span class="absolute -top-0.5 -right-0.5 w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                                </template>
                                            </div>

                                            <template x-if="mobileDockShowLabels">
                                                <span class="text-[8px] leading-tight mt-0.5"
                                                      x-text="previewLang === 'en' ? (tab.label_en || 'Tab') : (tab.label_kn || tab.label_en)"></span>
                                            </template>
                                        </div>
                                    </template>
                                </template>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>

    </div>

</div>
@endsection
