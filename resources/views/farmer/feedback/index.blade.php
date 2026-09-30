@extends('layouts.farmer')

@php
    $activeLocale = app()->getLocale();
    $isKn = $activeLocale === 'kn';
@endphp

@section('title', $isKn 
    ? 'ಸಮಸ್ಯೆ ವರದಿ & ರೈತರ ಸಲಹೆಗಳು — ಕೃಷಿ ಬಾಂಧವ' 
    : 'Report Issues & Farmer Feedback — Krushi Baandhava'
)

@section('content')
<div class="min-h-screen bg-[#FAF8F5] pb-24 pt-4 sm:pt-6" 
     x-data="farmerFeedbackForm({
        initialMode: '{{ old('type', $mode ?? 'issue') }}',
        initialCategory: '{{ old('category', $prefillCategory ?? '') }}',
        initialCrop: '{{ old('crop_name', $prefillCrop ?? '') }}',
        initialDistrict: '{{ old('district', $prefillDistrict ?? '') }}',
        initialMarket: '{{ old('market_name', $prefillMarket ?? '') }}',
        csrfToken: '{{ csrf_token() }}',
        postUrl: '{{ route('farmer.feedback.store') }}',
        settings: @js($settings ?? []),
        issueCategories: @js($issueCategories ?? []),
        feedbackCategories: @js($feedbackCategories ?? [])
     })">

    <div class="max-w-3xl mx-auto px-4 sm:px-6">

        <!-- Breadcrumb & Back -->
        <nav class="flex items-center gap-2 text-xs font-medium text-[#7A6B58] mb-4">
            <a href="{{ route('home') }}" class="hover:text-[#1C5A2C] transition-colors flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>{{ $isKn ? 'ಮುಖಪುಟ' : 'Home' }}</span>
            </a>
            <svg class="w-3 h-3 text-[#DDD3BE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-[#1C5A2C] font-semibold">{{ $isKn ? 'ಸಮಸ್ಯೆ ವರದಿ & ಸಲಹೆ' : 'Report & Feedback' }}</span>
        </nav>

        <!-- Header Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-[#1C5A2C] to-[#154622] rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-[#1C5A2C]/10 border border-[#2d7743] mb-6">
            <div class="absolute -right-8 -bottom-8 w-44 h-44 rounded-full bg-white/5 pointer-events-none blur-2xl"></div>
            <div class="relative z-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-sm text-xs font-semibold tracking-wide text-amber-200 border border-white/10 mb-3">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    {{ $isKn ? '🌾 ರೈತರ ಸಹಾಯವಾಣಿ ಮತ್ತು ಧ್ವನಿ' : '🌾 Farmer Grievance & Voice Helpdesk' }}
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    {{ $isKn ? 'ಸಮಸ್ಯೆ ವರದಿ & ನಿಮ್ಮ ಸಲಹೆಗಳು' : 'Report Issues & Share Suggestions' }}
                </h1>
                <p class="text-white/80 text-sm sm:text-base mt-2 leading-relaxed">
                    {{ $isKn 
                        ? 'ಮಾರುಕಟ್ಟೆ ದರ ವ್ಯತ್ಯಾಸ, ತೂಕದ ವಂಚನೆ, ಆ್ಯಪ್ ದೋಷಗಳು ಅಥವಾ ನಿಮ್ಮ ಸಲಹೆಗಳನ್ನು ನೇರವಾಗಿ ಹಂಚಿಕೊಳ್ಳಿ. ನೀವು ಟೈಪ್ ಮಾಡಬಹುದು ಅಥವಾ ಧ್ವನಿ ರೆಕಾರ್ಡ್ ಮಾಡಬಹುದು.'
                        : 'Report mandi price discrepancies, weighing issues, app bugs or share ideas. You can write details or directly record a voice note.' }}
                </p>
            </div>
        </div>

        @if(session('feedback_success'))
            <!-- Flash Success Card from Standard Form Submit -->
            <div class="bg-[#FAF8F5] border-2 border-emerald-500/40 rounded-3xl p-6 sm:p-8 text-center shadow-lg mb-8">
                <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto mb-4 text-2xl shadow-inner">
                    ✓
                </div>
                <span class="text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
                    {{ $isKn ? 'ಯಶಸ್ವಿಯಾಗಿ ದಾಖಲಾಗಿದೆ' : 'Successfully Submitted' }}
                </span>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mt-3">
                    {{ $isKn ? 'ಧನ್ಯವಾದಗಳು! ನಿಮ್ಮ ಕೋರಿಕೆಯನ್ನು ಸ್ವೀಕರಿಸಲಾಗಿದೆ' : 'Thank You! Your Ticket has been logged' }}
                </h2>
                <p class="text-sm text-[#7A6B58] mt-1">
                    {{ $isKn ? 'ನಿಮ್ಮ ಸಮಸ್ಯೆ/ಸಲಹೆಯ ಟಿಕೆಟ್ ರೆಫರೆನ್ಸ್ ಸಂಖ್ಯೆ:' : 'Your reference ticket number:' }}
                </p>

                <div class="inline-flex items-center gap-3 bg-[#EFEAE0] px-5 py-2.5 rounded-2xl border border-[#DDD3BE] mt-3 font-mono text-xl font-extrabold text-[#1C5A2C]">
                    <span>{{ session('feedback_success')['ticket_no'] }}</span>
                </div>

                <p class="text-xs text-gray-600 max-w-md mx-auto mt-4 leading-relaxed">
                    {{ $isKn 
                        ? 'ನಮ್ಮ ಕೃಷಿ ಬಾಂಧವ ತಂಡವು ಈ ಮಾಹಿತಿಯನ್ನು ಪರಿಶೀಲಿಸಿ ಅಗತ್ಯ ಕ್ರಮ ಕೈಗೊಳ್ಳಲಿದೆ. ತ್ವರಿತ ಅಪ್‌ಡೇಟ್‌ಗಾಗಿ ವಾಟ್ಸಾಪ್ ಮೂಲಕವೂ ಸಂಪರ್ಕಿಸಬಹುದು.' 
                        : 'Our Krushi Baandhava desk is reviewing your ticket. For immediate tracking or follow-up, chat directly on WhatsApp.' }}
                </p>

                <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ session('feedback_success')['whatsapp_url'] }}" 
                       target="_blank" 
                       rel="noopener"
                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow-md shadow-emerald-600/20 transition-all">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.699c.971.53 1.761.802 2.796.803h.001c3.181 0 5.768-2.587 5.768-5.766 0-3.18-2.587-5.789-5.769-5.789zm3.366 8.232c-.143.402-.832.748-1.157.794-.325.045-.733.069-2.144-.492-1.411-.561-2.47-1.745-2.614-1.936-.143-.191-1.121-1.488-1.121-2.839 0-1.35.707-2.016.958-2.274.251-.258.547-.323.73-.323.182 0 .365.002.525.01.169.008.396-.064.62.474.23.551.782 1.91.85 2.05.068.14.114.304.023.486-.091.182-.137.295-.274.453-.137.159-.288.354-.412.475-.137.135-.28.281-.12.556.16.274.71 1.171 1.523 1.895 1.047.931 1.93 1.218 2.204 1.353.274.135.434.113.594-.07.16-.182.685-.795.868-1.069.183-.274.366-.228.617-.137.251.091 1.599.754 1.873.891.274.137.457.205.525.32.068.114.068.662-.075 1.064z"/></svg>
                        <span>{{ $isKn ? 'WhatsApp ನಲ್ಲಿ ಸಂಪರ್ಕಿಸಿ' : 'Follow up on WhatsApp' }}</span>
                    </a>
                    <a href="{{ route('farmer.feedback.create') }}" 
                       class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-xl bg-white hover:bg-gray-50 text-gray-700 font-semibold text-sm border border-[#DDD3BE] shadow-sm transition-all">
                        {{ $isKn ? 'ಮತ್ತೊಂದು ವರದಿ ಸಲ್ಲಿಸಿ' : 'Submit Another Ticket' }}
                    </a>
                </div>
            </div>
        @endif

        <!-- Client-Side Success Card (Alpine) -->
        <div x-show="submittedSuccess" x-cloak class="bg-[#FAF8F5] border-2 border-emerald-500/40 rounded-3xl p-6 sm:p-8 text-center shadow-lg mb-8">
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto mb-4 text-2xl shadow-inner">
                ✓
            </div>
            <span class="text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
                {{ $isKn ? 'ಯಶಸ್ವಿಯಾಗಿ ದಾಖಲಾಗಿದೆ' : 'Successfully Submitted' }}
            </span>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mt-3">
                {{ $isKn ? 'ಧನ್ಯವಾದಗಳು! ನಿಮ್ಮ ಕೋರಿಕೆಯನ್ನು ಸ್ವೀಕರಿಸಲಾಗಿದೆ' : 'Thank You! Your Ticket has been logged' }}
            </h2>
            <p class="text-sm text-[#7A6B58] mt-1">
                {{ $isKn ? 'ನಿಮ್ಮ ಟಿಕೆಟ್ ಸಂಖ್ಯೆ:' : 'Your reference ticket number:' }}
            </p>

            <div class="inline-flex items-center gap-3 bg-[#EFEAE0] px-5 py-2.5 rounded-2xl border border-[#DDD3BE] mt-3 font-mono text-xl font-extrabold text-[#1C5A2C]">
                <span x-text="submittedTicketNo"></span>
            </div>

            <p class="text-xs text-gray-600 max-w-md mx-auto mt-4 leading-relaxed">
                {{ $isKn 
                    ? 'ನಮ್ಮ ಕೃಷಿ ಬಾಂಧವ ತಂಡವು ಶೀಘ್ರದಲ್ಲಿ ಪರಿಶೀಲಿಸಲಿದೆ. ಹೆಚ್ಚಿನ ವಿವರ ತಿಳಿಸಲು ವಾಟ್ಸಾಪ್ ಬಟನ್ ಬಳಸಿ.' 
                    : 'Our Krushi Baandhava desk is reviewing your ticket. You can also chat directly on WhatsApp.' }}
            </p>

            <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a :href="submittedWhatsAppUrl" 
                   target="_blank" 
                   rel="noopener"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow-md shadow-emerald-600/20 transition-all">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.699c.971.53 1.761.802 2.796.803h.001c3.181 0 5.768-2.587 5.768-5.766 0-3.18-2.587-5.789-5.769-5.789zm3.366 8.232c-.143.402-.832.748-1.157.794-.325.045-.733.069-2.144-.492-1.411-.561-2.47-1.745-2.614-1.936-.143-.191-1.121-1.488-1.121-2.839 0-1.35.707-2.016.958-2.274.251-.258.547-.323.73-.323.182 0 .365.002.525.01.169.008.396-.064.62.474.23.551.782 1.91.85 2.05.068.14.114.304.023.486-.091.182-.137.295-.274.453-.137.159-.288.354-.412.475-.137.135-.28.281-.12.556.16.274.71 1.171 1.523 1.895 1.047.931 1.93 1.218 2.204 1.353.274.135.434.113.594-.07.16-.182.685-.795.868-1.069.183-.274.366-.228.617-.137.251.091 1.599.754 1.873.891.274.137.457.205.525.32.068.114.068.662-.075 1.064z"/></svg>
                    <span>{{ $isKn ? 'WhatsApp ನಲ್ಲಿ ಸಂಪರ್ಕಿಸಿ' : 'Follow up on WhatsApp' }}</span>
                </a>
                <button type="button" 
                        @click="resetForm()"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-xl bg-white hover:bg-gray-50 text-gray-700 font-semibold text-sm border border-[#DDD3BE] shadow-sm transition-all">
                    {{ $isKn ? 'ಮತ್ತೊಂದು ವರದಿ ಸಲ್ಲಿಸಿ' : 'Submit Another Ticket' }}
                </button>
            </div>
        </div>

        <!-- Main Form Container -->
        <div x-show="!submittedSuccess" class="bg-white rounded-3xl border border-[#DDD3BE] shadow-sm overflow-hidden p-6 sm:p-8">

            <form @submit.prevent="submitForm()" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Honeypot anti-spam (hidden) -->
                <input type="text" name="antispam_website" x-model="form.antispam_website" class="hidden" tabindex="-1" autocomplete="off">

                <!-- 1. Segmented Mode Switcher (Issue vs Feedback) -->
                <div class="mb-6">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#7A6B58] mb-2">
                        {{ $isKn ? 'ನೀವು ಏನು ಸಲ್ಲಿಸಲು ಬಯಸುವಿರಿ?' : 'What would you like to submit?' }}
                    </label>
                    <div class="grid grid-cols-2 p-1.5 bg-[#EFEAE0] rounded-2xl border border-[#DDD3BE] gap-1.5">
                        <button type="button" 
                                @click="setMode('issue')"
                                :class="mode === 'issue' 
                                    ? 'bg-[#1C5A2C] text-white shadow-md' 
                                    : 'text-[#5C5346] hover:text-[#1C5A2C] hover:bg-white/50'"
                                class="flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-bold transition-all">
                            <span class="text-lg">🛠️</span>
                            <span>{{ $isKn ? 'ಸಮಸ್ಯೆ ವರದಿ' : 'Report Issue' }}</span>
                        </button>

                        <button type="button" 
                                @click="setMode('feedback')"
                                :class="mode === 'feedback' 
                                    ? 'bg-[#1C5A2C] text-white shadow-md' 
                                    : 'text-[#5C5346] hover:text-[#1C5A2C] hover:bg-white/50'"
                                class="flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-bold transition-all">
                            <span class="text-lg">💡</span>
                            <span>{{ $isKn ? 'ಸಲಹೆ & ಪ್ರತಿಕ್ರಿಯೆ' : 'Feedback & Ideas' }}</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Interactive Category Chips -->
                <div class="mb-6">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#7A6B58] mb-2">
                        {{ $isKn ? 'ವರ್ಗವನ್ನು ಆಯ್ಕೆಮಾಡಿ *' : 'Select Category *' }}
                    </label>

                    <!-- Issue Categories -->
                    <div x-show="mode === 'issue'" class="flex flex-wrap gap-2">
                        <template x-for="cat in activeIssueCategories" :key="cat.id">
                            <button type="button" 
                                    @click="form.category = cat.id"
                                    :class="form.category === cat.id 
                                        ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-sm font-semibold' 
                                        : 'bg-[#FAF8F5] text-gray-700 border-[#DDD3BE] hover:border-[#1C5A2C] hover:bg-[#F5F1E8]'"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs sm:text-sm border transition-all">
                                <span x-text="cat.icon"></span>
                                <span x-text="locale === 'kn' ? (cat.label_kn || cat.label_en) : (cat.label_en || cat.label_kn)"></span>
                            </button>
                        </template>
                    </div>

                    <!-- Feedback Categories -->
                    <div x-show="mode === 'feedback'" class="flex flex-wrap gap-2">
                        <template x-for="cat in activeFeedbackCategories" :key="cat.id">
                            <button type="button" 
                                    @click="form.category = cat.id"
                                    :class="form.category === cat.id 
                                        ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-sm font-semibold' 
                                        : 'bg-[#FAF8F5] text-gray-700 border-[#DDD3BE] hover:border-[#1C5A2C] hover:bg-[#F5F1E8]'"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs sm:text-sm border transition-all">
                                <span x-text="cat.icon"></span>
                                <span x-text="locale === 'kn' ? (cat.label_kn || cat.label_en) : (cat.label_en || cat.label_kn)"></span>
                            </button>
                        </template>
                    </div>

                    <p x-show="errors.category" class="text-xs text-red-600 mt-1 font-medium" x-text="errors.category"></p>
                </div>

                <!-- 3. Star Rating (Feedback Mode Only) -->
                <div x-show="mode === 'feedback'" x-transition class="mb-6 bg-[#FAF8F5] p-4 rounded-2xl border border-[#DDD3BE]">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#7A6B58] mb-2">
                        {{ $isKn ? 'ನಿಮ್ಮ ಅನುಭವವನ್ನು ರೇಟಿಂಗ್ ಮಾಡಿ' : 'Rate Your Experience' }}
                    </label>
                    <div class="flex items-center gap-2">
                        <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                            <button type="button" 
                                    @click="form.rating = star"
                                    @mouseover="hoverRating = star"
                                    @mouseleave="hoverRating = 0"
                                    class="text-2xl sm:text-3xl transition-transform hover:scale-125 focus:outline-none">
                                <span :class="(hoverRating || form.rating) >= star ? 'text-amber-400' : 'text-gray-300'">★</span>
                            </button>
                        </template>
                        <span class="text-xs font-semibold text-[#7A6B58] ml-2" x-text="getRatingLabel(form.rating)"></span>
                    </div>
                </div>

                <!-- 4. Agri Context (Crop & Market) - Collapsible/Optional -->
                <div class="mb-6 bg-[#FAF8F5] p-4 sm:p-5 rounded-2xl border border-[#DDD3BE]">
                    <div class="flex items-center justify-between cursor-pointer" @click="showContext = !showContext">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">📍</span>
                            <span class="text-xs sm:text-sm font-bold text-gray-800">
                                {{ $isKn ? 'ಬೆಳೆ ಮತ್ತು ಮಾರುಕಟ್ಟೆ ಮಾಹಿತಿ (ಐಚ್ಛಿಕ)' : 'Crop & Mandi Details (Optional)' }}
                            </span>
                        </div>
                        <span class="text-xs text-[#1C5A2C] font-semibold flex items-center gap-1">
                            <span x-text="showContext ? '{{ $isKn ? 'ಮರೆಮಾಡಿ' : 'Hide' }}' : '{{ $isKn ? 'ಸೇರಿಸಿ' : 'Add' }}'"></span>
                            <svg class="w-4 h-4 transform transition-transform" :class="showContext ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </span>
                    </div>

                    <div x-show="showContext" x-collapse class="mt-4 pt-4 border-t border-[#DDD3BE] grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Crop Selector / Free text -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                {{ $isKn ? 'ಬೆಳೆಯ ಹೆಸರು' : 'Crop Name' }}
                            </label>
                            <input type="text" 
                                   list="crops-list" 
                                   x-model="form.crop_name"
                                   placeholder="{{ $isKn ? 'ಉದಾ: ಟೊಮೆಟೊ, ಈರುಳ್ಳಿ' : 'e.g. Tomato, Onion' }}"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-[#DDD3BE] bg-white text-sm text-gray-800 focus:ring-2 focus:ring-[#1C5A2C] focus:border-transparent outline-none">
                            <datalist id="crops-list">
                                @foreach($crops as $crop)
                                    <option value="{{ $isKn && $crop->name_kn ? $crop->name_kn : $crop->name }}">
                                @endforeach
                            </datalist>
                        </div>

                        <!-- District & Mandi -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                {{ $isKn ? 'ಜಿಲ್ಲೆ / ಮಂಡಿ' : 'District / Mandi' }}
                            </label>
                            <input type="text" 
                                   list="markets-list" 
                                   x-model="form.market_name"
                                   placeholder="{{ $isKn ? 'ಉದಾ: ಕೋಲಾರ, ಯಶವಂತಪುರ, ಶಿವಮೊಗ್ಗ' : 'e.g. Kolar, Yeshwantpur, Shivamogga' }}"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-[#DDD3BE] bg-white text-sm text-gray-800 focus:ring-2 focus:ring-[#1C5A2C] focus:border-transparent outline-none">
                            <datalist id="markets-list">
                                @foreach($districts as $district)
                                    @foreach($district->markets as $m)
                                        <option value="{{ ($isKn && $m->name_kn ? $m->name_kn : $m->name) . ' (' . ($isKn && $district->name_kn ? $district->name_kn : $district->name) . ')' }}">
                                    @endforeach
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                </div>

                <!-- 5. Voice Note Recording Module (Restored Earlier Card UI + Direct Native Permission Trigger) -->
                @if(!isset($settings['enable_voice']) || $settings['enable_voice'])
                <div class="mb-6 bg-gradient-to-br from-[#FBF9F5] to-[#F5EFE6] p-4 sm:p-5 rounded-2xl border-2 border-dashed border-[#CFC3A9]">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🎙️</span>
                                <h3 class="text-sm font-bold text-gray-900">
                                    {{ $isKn ? 'ಧ್ವನಿ ಸಂದೇಶ ರೆಕಾರ್ಡ್ ಮಾಡಿ' : 'Record Voice Note' }}
                                </h3>
                                <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 text-[10px] font-bold uppercase tracking-wider">
                                    {{ $isKn ? 'ರೈತರಿಗೆ ಸುಲಭ' : 'Voice-First' }}
                                </span>
                            </div>
                            <p class="text-xs text-[#7A6B58] mt-1">
                                {{ $isKn 
                                    ? 'ಟೈಪ್ ಮಾಡುವುದು ಬೇಡವೇ? ಮೈಕ್ ಬಟನ್ ಒತ್ತಿ ನಿಮ್ಮ ಮಾತಿನಲ್ಲೇ ನೇರವಾಗಿ ರೆಕಾರ್ಡ್ ಮಾಡಿ ಕಳುಹಿಸಿ.' 
                                    : 'Prefer not to type? Simply tap the mic and record your message directly.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Voice Recorder Container -->
                    <div class="mt-4">
                        <!-- 1. Idle State -->
                        <div x-show="!isRecording && !recordedAudioUrl" class="flex flex-wrap items-center gap-3">
                            <!-- Direct In-Browser Record Button (Triggers native browser permission prompt on click) -->
                            <button type="button" 
                                    @click="startRecording()"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#1C5A2C] hover:bg-[#154622] text-white text-xs sm:text-sm font-bold shadow-sm transition-all active:scale-95 cursor-pointer">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/><path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>
                                <span>{{ $isKn ? 'ರೆಕಾರ್ಡ್ ಮಾಡಿ' : 'Record Voice Note' }}</span>
                            </button>

                            <!-- Mobile Native Audio File / Capture Fallback -->
                            <label class="cursor-pointer inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white hover:bg-stone-50 border border-[#DDD3BE] text-gray-700 text-xs font-semibold shadow-2xs transition-all active:scale-95" title="Mobile sound recorder / Audio file">
                                <span>📁</span>
                                <span>{{ $isKn ? 'ಫೋನ್ ರೆಕಾರ್ಡರ್ / ಫೈಲ್' : 'Device Audio / File' }}</span>
                                <input type="file" 
                                       accept="audio/*" 
                                       capture="microphone"
                                       @change="handleAudioFileUpload($event)" 
                                       class="hidden">
                            </label>

                            <span class="text-xs text-gray-500 font-medium" x-text="'{{ $isKn ? 'ಗರಿಷ್ಠ ' : 'Max ' }}' + Math.floor(maxSeconds / 60) + ' {{ $isKn ? 'ನಿಮಿಷ' : 'mins' }}'"></span>
                        </div>

                        <!-- 2. Recording in Progress State -->
                        <div x-show="isRecording" x-cloak class="flex items-center justify-between bg-red-50 p-3 sm:p-3.5 rounded-xl border border-red-200">
                            <div class="flex items-center gap-3">
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-600"></span>
                                </span>
                                <span class="text-xs sm:text-sm font-bold text-red-700 tracking-wide">
                                    {{ $isKn ? 'ರೆಕಾರ್ಡಿಂಗ್ ಆಗುತ್ತಿದೆ...' : 'Recording in progress...' }}
                                </span>
                                <span class="text-xs font-mono font-bold bg-white px-2.5 py-0.5 rounded-md border border-red-200 text-red-700 tabular-nums shadow-2xs" x-text="formatTime(recordingSeconds)">00:00</span>
                            </div>
                            <button type="button" 
                                    @click="stopRecording()"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-sm transition-all active:scale-95 cursor-pointer">
                                <span class="w-2.5 h-2.5 bg-white rounded-xs"></span>
                                <span>{{ $isKn ? 'ನಿಲ್ಲಿಸಿ' : 'Stop' }}</span>
                            </button>
                        </div>

                        <!-- 3. Recorded Audio Preview State -->
                        <div x-show="recordedAudioUrl && !isRecording" x-cloak class="space-y-3 bg-white p-3 sm:p-4 rounded-xl border border-[#DDD3BE] shadow-2xs">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-emerald-800 flex items-center gap-1.5">
                                    <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold">✓</span>
                                    <span>{{ $isKn ? 'ಧ್ವನಿ ರೆಕಾರ್ಡ್ ಆಗಿದೆ' : 'Audio Note Ready' }}</span>
                                </span>
                                <button type="button" 
                                        @click="deleteRecording()"
                                        class="text-xs font-semibold text-red-600 hover:text-red-700 flex items-center gap-1 cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>{{ $isKn ? 'ಅಳಿಸಿ / ಪುನಃ ರೆಕಾರ್ಡ್ ಮಾಡಿ' : 'Delete & Retake' }}</span>
                                </button>
                            </div>
                            <audio :src="recordedAudioUrl" controls class="w-full h-10 rounded-lg outline-none"></audio>
                        </div>

                        <!-- Helpful Permission Guidance (Graceful inline banner only if mic is blocked in browser) -->
                        <div x-show="permissionDeniedNotice || insecureContextNotice" x-cloak class="mt-3 p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 space-y-2">
                            <div class="font-bold flex items-center gap-1.5 text-amber-800">
                                <span>🔒</span>
                                <span x-text="insecureContextNotice 
                                    ? '{{ $isKn ? 'ಭದ್ರತಾ ಸೂಚನೆ (HTTPS ಅಗತ್ಯವಿದೆ)' : 'Security Notice (HTTPS Required)' }}' 
                                    : '{{ $isKn ? 'ಮೈಕ್ರೊಫೋನ್ ಅನುಮತಿ ಸರಿಪಡಿಸುವ ವಿಧಾನ (Chrome Settings):' : 'How to enable microphone access (Chrome Settings):' }}'"></span>
                            </div>
                            <div class="leading-relaxed space-y-1" x-show="!insecureContextNotice">
                                <p>{{ $isKn 
                                    ? 'ನಿಮ್ಮ ಬ್ರೌಸರ್‌ನಲ್ಲಿ localhost ಗೆ ಮೈಕ್ರೊಫೋನ್ ಅನುಮತಿ "Block" ಆಗಿದೆ. ಆದ್ದರಿಂದ ಬ್ರೌಸರ್ ಪಾಪ್-ಅಪ್ ಕೇಳುತ್ತಿಲ್ಲ.' 
                                    : 'Microphone permission for localhost is currently set to "Block" in Chrome, so Chrome is not showing the prompt.' }}</p>
                                <ol class="list-decimal pl-4 space-y-0.5 font-medium">
                                    <li>{{ $isKn ? 'Chrome URL ಬಾರ್‌ನಲ್ಲಿ localhost ಪಕ್ಕದಲ್ಲಿರುವ Tune / Lock (🔒) ಐಕಾನ್ ಕ್ಲಿಕ್ ಮಾಡಿ.' : 'Click the Tune / Lock (🔒) icon on the left of localhost in the address bar.' }}</li>
                                    <li>{{ $isKn ? 'Microphone ಅನ್ನು "Ask" ಅಥವಾ "Allow" ಮಾಡಿ (ಅಥವಾ "Reset permission" ಕ್ಲಿಕ್ ಮಾಡಿ).' : 'Change Microphone to "Ask (default)" or "Allow" (or click "Reset permission").' }}</li>
                                    <li>{{ $isKn ? 'ಪೇಜ್ ರಿಫ್ರೆಶ್ ಮಾಡಿ ಮತ್ತೆ "ರೆಕಾರ್ಡ್ ಮಾಡಿ" ಕ್ಲಿಕ್ ಮಾಡಿ.' : 'Refresh the page and tap "Record Voice Note" again.' }}</li>
                                </ol>
                            </div>
                            <p class="leading-relaxed" x-show="insecureContextNotice">
                                {{ $isKn 
                                    ? 'ಬ್ರೌಸರ್‌ನ ನೇರ ಮೈಕ್ ರೆಕಾರ್ಡಿಂಗ್‌ಗೆ HTTPS ಅಥವಾ localhost ಅಗತ್ಯವಿದೆ. ನೀವು ಮೊಬೈಲ್ ನೆಟ್‌ವರ್ಕ್ ಐಪಿ ಮೂಲಕ ಪರೀಕ್ಷಿಸುತ್ತಿದ್ದರೆ, ದಯವಿಟ್ಟು ಮೇಲಿನ "📁 ಫೋನ್ ರೆಕಾರ್ಡರ್ / ಫೈಲ್" ಬಟನ್ ಒತ್ತಿ ನಿಮ್ಮ ಧ್ವನಿ ರೆಕಾರ್ಡ್ ಮಾಡಿ ಕಳುಹಿಸಿ.' 
                                    : 'Direct in-browser microphone requires HTTPS or localhost. If testing via mobile network IP, please tap "📁 Device Audio / File" to record directly.' }}
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- 6. Written Description Textarea -->
                <div class="mb-6">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#7A6B58] mb-1">
                        {{ $isKn ? 'ವಿವರಣೆ / ಸಂದೇಶ' : 'Detailed Message / Description' }}
                        <span class="text-gray-400 font-normal normal-case">({{ $isKn ? 'ಧ್ವನಿ ರೆಕಾರ್ಡ್ ಮಾಡದಿದ್ದರೆ ಅಗತ್ಯ' : 'Required if voice note is not recorded' }})</span>
                    </label>
                    <textarea x-model="form.message" 
                              rows="4" 
                              placeholder="{{ $isKn 
                                  ? 'ನಿಮ್ಮ ಸಮಸ್ಯೆ ಅಥವಾ ಸಲಹೆಯನ್ನು ಇಲ್ಲಿ ಸ್ಪಷ್ಟವಾಗಿ ಬರೆಯಿರಿ...' 
                                  : 'Type your issue details, mandi rate discrepancies or suggestions here...' }}"
                              class="w-full px-4 py-3 rounded-2xl border border-[#DDD3BE] bg-white text-sm text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-[#1C5A2C] focus:border-transparent outline-none transition-all"></textarea>
                    <p x-show="errors.message" class="text-xs text-red-600 mt-1 font-medium" x-text="errors.message"></p>
                </div>

                <!-- 7. Photo Upload (Mandi Slips, Receipts, Crop Disease) -->
                @if(!isset($settings['enable_photos']) || $settings['enable_photos'])
                <div class="mb-6">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#7A6B58] mb-1">
                        {{ $isKn ? 'ಫೋಟೋ / ರಶೀದಿ ಲಗತ್ತಿಸಿ (ಐಚ್ಛಿಕ)' : 'Attach Photo or Mandi Slip (Optional)' }}
                    </label>
                    
                    <div class="flex items-center gap-4">
                        <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-[#DDD3BE] bg-[#FAF8F5] hover:bg-[#F5F1E8] text-xs sm:text-sm font-semibold text-gray-700 transition-all">
                            <svg class="w-4 h-4 text-[#1C5A2C]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>{{ $isKn ? 'ಕ್ಯಾಮೆರಾ / ಫೋಟೋ ಆಯ್ಕೆ' : 'Upload Mandi Slip / Photo' }}</span>
                            <input type="file" 
                                   accept="image/jpeg,image/png,image/webp" 
                                   @change="handlePhotoUpload($event)" 
                                   class="hidden">
                        </label>
                        <span class="text-xs text-gray-500">{{ $isKn ? 'JPG, PNG, WebP (ಗರಿಷ್ಠ 10MB)' : 'JPG, PNG, WebP (Max 10MB)' }}</span>
                    </div>

                    <!-- Photo Preview Thumbnail -->
                    <div x-show="photoPreviewUrl" x-cloak class="mt-3 relative inline-block">
                        <img :src="photoPreviewUrl" class="w-28 h-28 object-cover rounded-xl border border-[#DDD3BE] shadow-sm">
                        <button type="button" 
                                @click="removePhoto()"
                                class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-red-600 text-white flex items-center justify-center text-xs font-bold hover:bg-red-700 shadow">
                            ×
                        </button>
                    </div>
                    <p x-show="errors.photo" class="text-xs text-red-600 mt-1 font-medium" x-text="errors.photo"></p>
                </div>
                @endif

                <!-- 8. Farmer Contact Information (Name, Phone, Email) -->
                <div class="mb-8 pt-6 border-t border-[#DDD3BE]">
                    <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                        <span>👤</span>
                        <span>{{ $isKn ? 'ನಿಮ್ಮ ಸಂಪರ್ಕ ಮಾಹಿತಿ' : 'Your Contact Information' }}</span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Farmer Name -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                {{ $isKn ? 'ರೈತರ ಹೆಸರು (ಐಚ್ಛಿಕ)' : 'Farmer Name (Optional)' }}
                            </label>
                            <input type="text" 
                                   x-model="form.farmer_name" 
                                   placeholder="{{ $isKn ? 'ನಿಮ್ಮ ಹೆಸರು' : 'Your Name' }}"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-[#DDD3BE] bg-white text-sm text-gray-800 focus:ring-2 focus:ring-[#1C5A2C] focus:border-transparent outline-none">
                        </div>

                        <!-- Farmer Phone -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                {{ $isKn ? 'ಮೊಬೈಲ್ ಸಂಖ್ಯೆ *' : 'Mobile Phone Number *' }}
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-bold text-gray-500">
                                    +91
                                </div>
                                <input type="tel" 
                                       x-model="form.farmer_phone" 
                                       maxlength="10"
                                       placeholder="{{ $isKn ? '10-ಅಂಕಿಯ ಮೊಬೈಲ್' : '10-digit Mobile' }}"
                                       class="w-full pl-12 pr-3.5 py-2.5 rounded-xl border border-[#DDD3BE] bg-white text-sm text-gray-800 focus:ring-2 focus:ring-[#1C5A2C] focus:border-transparent outline-none">
                            </div>
                            <p x-show="errors.farmer_phone" class="text-xs text-red-600 mt-1 font-medium" x-text="errors.farmer_phone"></p>
                        </div>

                        <!-- Farmer Email (Requested for both modes) -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                {{ $isKn ? 'ಇಮೇಲ್ ವಿಳಾಸ (ಐಚ್ಛಿಕ)' : 'Email ID (Optional)' }}
                            </label>
                            <div class="relative">
                                <input type="email" 
                                       x-model="form.farmer_email" 
                                       placeholder="{{ $isKn ? 'ನಿಮ್ಮ ಇಮೇಲ್' : 'name@example.com' }}"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#DDD3BE] bg-white text-sm text-gray-800 focus:ring-2 focus:ring-[#1C5A2C] focus:border-transparent outline-none">
                            </div>
                            <p x-show="errors.farmer_email" class="text-xs text-red-600 mt-1 font-medium" x-text="errors.farmer_email"></p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-between gap-4">
                    <p class="text-xs text-gray-500 hidden sm:block">
                        {{ $isKn ? 'ನಿಮ್ಮ ವಿವರಗಳನ್ನು ಸುರಕ್ಷಿತವಾಗಿಡಲಾಗುತ್ತದೆ.' : 'Your details remain private & secure.' }}
                    </p>
                    <button type="submit" 
                            :disabled="isSubmitting"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl bg-[#1C5A2C] hover:bg-[#154622] text-white text-sm sm:text-base font-bold shadow-lg shadow-[#1C5A2C]/20 transition-all disabled:opacity-50">
                        <svg x-show="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isSubmitting ? '{{ $isKn ? 'ಸಲ್ಲಿಸಲಾಗುತ್ತಿದೆ...' : 'Submitting...' }}' : '{{ $isKn ? 'ವರದಿ / ಸಲಹೆ ಸಲ್ಲಿಸಿ' : 'Submit Ticket' }}'"></span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('farmerFeedbackForm', (config) => ({
        mode: config.initialMode || 'issue',
        csrfToken: config.csrfToken,
        postUrl: config.postUrl,
        locale: '{{ $activeLocale }}',
        showContext: !!(config.initialCrop || config.initialMarket),
        hoverRating: 0,
        isSubmitting: false,
        submittedSuccess: false,
        submittedTicketNo: '',
        submittedWhatsAppUrl: '',
        permissionDeniedNotice: false,
        insecureContextNotice: false,
        maxSeconds: (config.settings && config.settings.max_voice_seconds) ? config.settings.max_voice_seconds : 180,

        form: {
            category: config.initialCategory || '',
            rating: 5,
            crop_name: config.initialCrop || '',
            district: config.initialDistrict || '',
            market_name: config.initialMarket || '',
            message: '',
            farmer_name: '',
            farmer_phone: '',
            farmer_email: '',
            antispam_website: ''
        },

        errors: {},

        // Audio Recorder State
        mediaRecorder: null,
        audioChunks: [],
        recordedAudioBlob: null,
        recordedAudioUrl: null,
        isRecording: false,
        recordingSeconds: 0,
        timerInterval: null,

        // Photo State
        photoFile: null,
        photoPreviewUrl: null,

        activeIssueCategories: config.issueCategories || [],
        activeFeedbackCategories: config.feedbackCategories || [],

        init() {
            if (!this.form.category) {
                if (this.mode === 'issue' && this.activeIssueCategories.length > 0) {
                    this.form.category = this.activeIssueCategories[0].id;
                } else if (this.mode === 'feedback' && this.activeFeedbackCategories.length > 0) {
                    this.form.category = this.activeFeedbackCategories[0].id;
                }
            }
        },

        setMode(newMode) {
            this.mode = newMode;
            if (newMode === 'issue' && this.activeIssueCategories.length > 0) {
                this.form.category = this.activeIssueCategories[0].id;
            } else if (newMode === 'feedback' && this.activeFeedbackCategories.length > 0) {
                this.form.category = this.activeFeedbackCategories[0].id;
            }
            this.errors = {};
        },

        getRatingLabel(val) {
            const labels = {
                1: '😡 ಅತೃಪ್ತಿ (Poor)',
                2: '🙁 ಸಾಧಾರಣ (Fair)',
                3: '🙂 ಉತ್ತಮ (Good)',
                4: '😃 ಬಹಳ ಉತ್ತಮ (Very Good)',
                5: '🤩 ಅದ್ಭುತ! (Excellent!)'
            };
            return labels[val] || '';
        },

        // HTML5 Audio Recording Handlers (Negilu-inspired + Robust Fallback)
        async toggleRecording() {
            if (this.isRecording) {
                this.stopRecording();
            } else {
                await this.startRecording();
            }
        },

        async startRecording() {
            this.permissionDeniedNotice = false;
            this.insecureContextNotice = false;

            // Direct check for insecure HTTP context on mobile/LAN
            if (window.isSecureContext === false && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                this.insecureContextNotice = true;
                return;
            }

            // Check if getUserMedia exists
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                this.permissionDeniedNotice = true;
                return;
            }

            try {
                // Request mic with plain { audio: true } directly on user click
                // This triggers the browser's native permission modal (as in Negilu screenshot)
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

                this.audioChunks = [];

                try {
                    this.mediaRecorder = new MediaRecorder(stream);
                } catch (mrErr) {
                    console.warn('MediaRecorder error:', mrErr);
                    this.permissionDeniedNotice = true;
                    return;
                }

                this.mediaRecorder.ondataavailable = (e) => {
                    if (e.data && e.data.size > 0) {
                        this.audioChunks.push(e.data);
                    }
                };

                this.mediaRecorder.onstop = () => {
                    const mime = (this.mediaRecorder && this.mediaRecorder.mimeType) 
                        ? this.mediaRecorder.mimeType.split(';')[0] 
                        : 'audio/webm';
                    this.recordedAudioBlob = new Blob(this.audioChunks, { type: mime });
                    this.recordedAudioUrl = URL.createObjectURL(this.recordedAudioBlob);
                    // Stop tracks to release mic hardware
                    stream.getTracks().forEach(track => track.stop());
                };

                this.mediaRecorder.start(250); // Slice in 250ms chunks for smooth recording
                this.isRecording = true;
                this.recordingSeconds = 0;

                this.timerInterval = setInterval(() => {
                    this.recordingSeconds++;
                    if (this.recordingSeconds >= this.maxSeconds) {
                        this.stopRecording();
                    }
                }, 1000);

            } catch (err) {
                console.warn('Microphone access denied or error:', err);
                this.permissionDeniedNotice = true;
                this.isRecording = false;
                if (this.timerInterval) clearInterval(this.timerInterval);
            }
        },

        stopRecording() {
            if (this.mediaRecorder && this.isRecording) {
                try {
                    this.mediaRecorder.stop();
                } catch (e) {
                    console.error(e);
                }
                this.isRecording = false;
                clearInterval(this.timerInterval);
            }
        },

        deleteRecording() {
            this.stopRecording();
            if (this.recordedAudioUrl) {
                try { URL.revokeObjectURL(this.recordedAudioUrl); } catch (e) {}
            }
            this.recordedAudioBlob = null;
            this.recordedAudioUrl = null;
            this.recordingSeconds = 0;
            this.audioChunks = [];
        },

        handleAudioFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.stopRecording();
            this.recordedAudioBlob = file;
            this.recordedAudioUrl = URL.createObjectURL(file);
            this.recordingSeconds = 0;
            this.permissionDeniedNotice = false;
        },

        formatTime(sec) {
            const m = Math.floor(sec / 60).toString().padStart(2, '0');
            const s = (sec % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        },

        // Photo Upload Handlers
        handlePhotoUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 10 * 1024 * 1024) {
                this.errors.photo = 'ಫೋಟೋ ಗಾತ್ರ 10MB ಗಿಂತ ಕಡಿಮೆಯಿರಬೇಕು (Max 10MB)';
                return;
            }

            this.errors.photo = null;
            this.photoFile = file;
            this.photoPreviewUrl = URL.createObjectURL(file);
        },

        removePhoto() {
            this.photoFile = null;
            this.photoPreviewUrl = null;
        },

        // Form Submission
        async submitForm() {
            this.errors = {};

            // Client side validation
            if (!this.form.category) {
                this.errors.category = 'ದಯವಿಟ್ಟು ವರ್ಗವನ್ನು ಆಯ್ಕೆಮಾಡಿ (Please select a category)';
            }

            if (!this.form.farmer_phone || this.form.farmer_phone.length < 10) {
                this.errors.farmer_phone = 'ದಯವಿಟ್ಟು 10-ಅಂಕಿಯ ಮೊಬೈಲ್ ಸಂಖ್ಯೆ ನಮೂದಿಸಿ (Please enter 10-digit mobile number)';
            }

            if (!this.form.message && !this.recordedAudioBlob) {
                this.errors.message = 'ದಯವಿಟ್ಟು ವಿವರಣೆ ಬರೆಯಿರಿ ಅಥವಾ ಧ್ವನಿ ಸಂದೇಶ ರೆಕಾರ್ಡ್ ಮಾಡಿ (Please provide message or voice note)';
            }

            if (Object.keys(this.errors).length > 0) {
                return;
            }

            this.isSubmitting = true;

            const formData = new FormData();
            formData.append('_token', this.csrfToken);
            formData.append('type', this.mode);
            formData.append('category', this.form.category);
            formData.append('rating', this.form.rating);
            formData.append('crop_name', this.form.crop_name || '');
            formData.append('district', this.form.district || '');
            formData.append('market_name', this.form.market_name || '');
            formData.append('message', this.form.message || '');
            formData.append('farmer_name', this.form.farmer_name || '');
            formData.append('farmer_phone', this.form.farmer_phone);
            formData.append('farmer_email', this.form.farmer_email || '');
            formData.append('antispam_website', this.form.antispam_website);

            if (this.recordedAudioBlob) {
                const ext = this.recordedAudioBlob.name ? this.recordedAudioBlob.name.split('.').pop() : 'webm';
                formData.append('voice', this.recordedAudioBlob, 'voice_recording.' + ext);
                formData.append('voice_duration', this.recordingSeconds);
            }

            if (this.photoFile) {
                formData.append('photo', this.photoFile);
            }

            try {
                const response = await fetch(this.postUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.submittedTicketNo = data.ticket_no;
                    this.submittedWhatsAppUrl = data.whatsapp_url;
                    this.submittedSuccess = true;
                    window.scrollTo({ top: 100, behavior: 'smooth' });
                } else if (data.errors) {
                    for (const [key, msgs] of Object.entries(data.errors)) {
                        this.errors[key] = msgs[0];
                    }
                } else {
                    alert(data.message || 'ದೋಷ ಸಂಭವಿಸಿದೆ. ದಯವಿಟ್ಟು ಪುನಃ ಪ್ರಯತ್ನಿಸಿ.');
                }
            } catch (err) {
                console.error('Submission error:', err);
                alert('ಸರ್ವರ್ ಸಂಪರ್ಕ ದೋಷ. ದಯವಿಟ್ಟು ನಿಮ್ಮ ಇಂಟರ್ನೆಟ್ ಸಂಪರ್ಕ ಪರೀಕ್ಷಿಸಿ.');
            } finally {
                this.isSubmitting = false;
            }
        },

        resetForm() {
            this.submittedSuccess = false;
            this.submittedTicketNo = '';
            this.submittedWhatsAppUrl = '';
            this.form.message = '';
            this.deleteRecording();
            this.removePhoto();
        }
    }));
});
</script>
@endsection
