@extends('layouts.admin')

@section('header', 'Farmer Helpdesk & Feedback CRM')

@section('content')
<div class="space-y-6" x-data="adminFeedbackManager({
    initialTab: '{{ $tab ?? 'inbox' }}',
    issueCategories: @js($settings['issue_categories'] ?? []),
    feedbackCategories: @js($settings['feedback_categories'] ?? [])
})">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white flex items-center gap-2">
                <span>🎙️</span> Farmer Helpdesk & Grievance CRM
                <span class="text-xs font-semibold px-2 py-0.5 bg-emerald-950/80 text-emerald-300 border border-emerald-800/60 rounded-full font-kannada">ರೈತರ ಧ್ವನಿ & ಪರಿಹಾರ</span>
            </h1>
            <p class="text-sm text-slate-400 font-medium">Manage farmer mandi rate issues, weighing disputes, crop disease queries, and community feedback.</p>
        </div>

        <!-- Mode Tabs Toggle -->
        <div class="flex items-center gap-2 bg-slate-900 border border-slate-800 p-1 rounded-xl">
            <button type="button" 
                    @click="activeTab = 'inbox'"
                    :class="activeTab === 'inbox' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'"
                    class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2">
                <span>📥 Grievances Inbox</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-black/30">{{ $stats['total'] }}</span>
            </button>
            <button type="button" 
                    @click="activeTab = 'settings'"
                    :class="activeTab === 'settings' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'"
                    class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                <span>⚙️ Form Settings & Categories CMS</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-950/80 border border-emerald-800 text-emerald-300 text-sm font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-400 hover:text-white text-lg leading-none">&times;</button>
        </div>
    @endif

    <!-- TAB 1: INBOX & GRIEVANCES CRM -->
    <div x-show="activeTab === 'inbox'" class="space-y-6">

        <!-- 1. KPI Counter Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-4">
            <!-- Total -->
            <a href="{{ route('admin.feedback.index', ['tab' => 'inbox']) }}" 
               class="p-4 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm hover:border-slate-700 transition flex flex-col justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Tickets</span>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-2xl font-black text-white">{{ $stats['total'] }}</span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-800 text-slate-300">All</span>
                </div>
            </a>

            <!-- New / Unread -->
            <a href="{{ route('admin.feedback.index', ['status' => 'new', 'tab' => 'inbox']) }}" 
               class="p-4 rounded-2xl bg-slate-900 border {{ $status === 'new' ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-slate-800' }} shadow-sm hover:border-rose-600 transition flex flex-col justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-400 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                    <span>New / Unread</span>
                </span>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-2xl font-black text-rose-400">{{ $stats['new'] }}</span>
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-rose-950/80 text-rose-300 border border-rose-800/60">Action Needed</span>
                </div>
            </a>

            <!-- In Review -->
            <a href="{{ route('admin.feedback.index', ['status' => 'in_review', 'tab' => 'inbox']) }}" 
               class="p-4 rounded-2xl bg-slate-900 border {{ $status === 'in_review' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-800' }} shadow-sm hover:border-amber-600 transition flex flex-col justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-400">In Review</span>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-2xl font-black text-amber-400">{{ $stats['in_review'] }}</span>
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-950/80 text-amber-300 border border-amber-800/60">Investigating</span>
                </div>
            </a>

            <!-- Resolved -->
            <a href="{{ route('admin.feedback.index', ['status' => 'resolved', 'tab' => 'inbox']) }}" 
               class="p-4 rounded-2xl bg-slate-900 border {{ $status === 'resolved' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-800' }} shadow-sm hover:border-emerald-600 transition flex flex-col justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Resolved</span>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-2xl font-black text-emerald-400">{{ $stats['resolved'] }}</span>
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-950/80 text-emerald-300 border border-emerald-800/60">Closed</span>
                </div>
            </a>

            <!-- Breakdown -->
            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 shadow-sm flex flex-col justify-between col-span-2 sm:col-span-4 lg:col-span-1">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Distribution</span>
                <div class="flex items-center gap-2 mt-2">
                    <div class="flex items-center gap-1 text-xs font-semibold text-rose-400">
                        <span>🛠️</span>
                        <span>{{ $stats['issues_count'] }} Issues</span>
                    </div>
                    <span class="text-slate-600">|</span>
                    <div class="flex items-center gap-1 text-xs font-semibold text-amber-400">
                        <span>💡</span>
                        <span>{{ $stats['feedback_count'] }} Ideas</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Search & Filter Bar -->
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm space-y-4">
            <form method="GET" action="{{ route('admin.feedback.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <input type="hidden" name="tab" value="inbox">

                <!-- Search Keyword -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Search Keywords</label>
                    <div class="relative">
                        <input type="text" 
                               name="search" 
                               value="{{ $search }}" 
                               placeholder="Ticket #, Farmer name, phone, crop, mandi..."
                               class="w-full pl-9 pr-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 placeholder-slate-500 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none">
                        <svg class="w-4 h-4 absolute left-3 top-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        <option value="">All Statuses</option>
                        <option value="new" {{ $status === 'new' ? 'selected' : '' }}>🔴 New / Unread</option>
                        <option value="in_review" {{ $status === 'in_review' ? 'selected' : '' }}>🟡 In Review</option>
                        <option value="resolved" {{ $status === 'resolved' ? 'selected' : '' }}>🟢 Resolved</option>
                        <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>⚪ Rejected</option>
                    </select>
                </div>

                <!-- Type Filter -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Type</label>
                    <select name="type" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        <option value="">All Types</option>
                        <option value="issue" {{ $type === 'issue' ? 'selected' : '' }}>🛠️ Issues Only</option>
                        <option value="feedback" {{ $type === 'feedback' ? 'selected' : '' }}>💡 Feedback & Suggestions</option>
                    </select>
                </div>

                <!-- Filter Buttons -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold transition cursor-pointer">
                        Filter
                    </button>
                    @if($search || $status || $type || $category)
                        <a href="{{ route('admin.feedback.index', ['tab' => 'inbox']) }}" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- 3. Submissions Table -->
        <div class="rounded-2xl bg-slate-900 border border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 text-xs font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">Ticket & Mode</th>
                            <th class="py-3 px-4">Farmer / Contact</th>
                            <th class="py-3 px-4">Category & Context</th>
                            <th class="py-3 px-4">Message / Media</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Submitted</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($feedbacks as $fb)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <!-- Ticket & Mode -->
                                <td class="py-3.5 px-4">
                                    <div class="font-mono font-bold text-white text-xs flex items-center gap-1.5">
                                        <span>{{ $fb->ticket_no }}</span>
                                    </div>
                                    <div class="mt-1">
                                        @if($fb->type === 'issue')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-rose-950/80 text-rose-300 border border-rose-800/60">
                                                🛠️ Issue
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-amber-950/80 text-amber-300 border border-amber-800/60">
                                                💡 Feedback
                                                @if($fb->rating)
                                                    <span>({{ $fb->rating }}★)</span>
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Farmer Contact -->
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-200 text-xs">
                                        {{ $fb->farmer_name ?: 'Anonymous Farmer' }}
                                    </div>
                                    <div class="text-xs text-slate-400 font-mono mt-0.5">
                                        +91 {{ $fb->farmer_phone }}
                                    </div>
                                    @if($fb->farmer_email)
                                        <div class="text-[11px] text-slate-400 font-mono mt-0.5 flex items-center gap-1">
                                            <span>✉️</span>
                                            <span>{{ $fb->farmer_email }}</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Category & Context -->
                                <td class="py-3.5 px-4 max-w-xs">
                                    <div class="text-xs font-medium text-slate-300 truncate" title="{{ $fb->category_label }}">
                                        {{ $fb->category_label }}
                                    </div>
                                    @if($fb->crop_name || $fb->market_name)
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @if($fb->crop_name)
                                                <span class="px-1.5 py-0.5 rounded bg-emerald-950/80 text-emerald-300 text-[10px] font-medium border border-emerald-800/60">
                                                    🌾 {{ $fb->crop_name }}
                                                </span>
                                            @endif
                                            @if($fb->market_name)
                                                <span class="px-1.5 py-0.5 rounded bg-cyan-950/80 text-cyan-300 text-[10px] font-medium border border-cyan-800/60">
                                                    📍 {{ $fb->market_name }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <!-- Message & Media -->
                                <td class="py-3.5 px-4 max-w-sm">
                                    @if($fb->message)
                                        <p class="text-xs text-slate-300 line-clamp-2 leading-relaxed" title="{{ $fb->message }}">
                                            {{ $fb->message }}
                                        </p>
                                    @endif

                                    <div class="flex items-center gap-2 mt-1.5">
                                        @if($fb->voice_path)
                                            <button type="button" 
                                                    @click="playVoice('{{ $fb->voice_url }}')"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-violet-950/80 text-violet-300 text-[11px] font-semibold border border-violet-800/60 hover:bg-violet-900/60 transition cursor-pointer">
                                                <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                                <span>Voice Note</span>
                                                @if($fb->voice_duration)
                                                    <span class="text-[10px] text-violet-400">({{ $fb->voice_duration }}s)</span>
                                                @endif
                                            </button>
                                        @endif

                                        @if($fb->photo_path)
                                            <button type="button" 
                                                    @click="openPhotoLightbox('{{ $fb->photo_url }}')"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-800 text-slate-300 text-[11px] font-semibold border border-slate-700 hover:bg-slate-700 transition cursor-pointer">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                <span>Photo Slip</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($fb->status === 'new')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-950/80 text-rose-300 border border-rose-800/60">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-pulse"></span>
                                            New
                                        </span>
                                    @elseif($fb->status === 'in_review')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-950/80 text-amber-300 border border-amber-800/60">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                            In Review
                                        </span>
                                    @elseif($fb->status === 'resolved')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-950/80 text-emerald-300 border border-emerald-800/60">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                            Resolved
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-400 border border-slate-700">
                                            Rejected
                                        </span>
                                    @endif
                                </td>

                                <!-- Submitted Date -->
                                <td class="py-3.5 px-4 text-xs text-slate-400 whitespace-nowrap">
                                    <div>{{ $fb->created_at->format('M d, Y') }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $fb->created_at->format('h:i A') }}</div>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1">
                                    <!-- View Drawer / Edit -->
                                    <button type="button" 
                                            @click="openTicketDrawer({{ $fb->id }})"
                                            class="p-1.5 text-slate-300 hover:text-emerald-400 hover:bg-slate-800 rounded-lg transition cursor-pointer"
                                            title="View Details & Update">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>

                                    <!-- WhatsApp Direct -->
                                    <a href="{{ $fb->getWhatsAppDirectUrl() }}" 
                                       target="_blank" 
                                       rel="noopener"
                                       class="p-1.5 text-emerald-400 hover:text-emerald-300 hover:bg-emerald-950/60 rounded-lg inline-block transition"
                                       title="Reply on WhatsApp">
                                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.699c.971.53 1.761.802 2.796.803h.001c3.181 0 5.768-2.587 5.768-5.766 0-3.18-2.587-5.789-5.769-5.789zm3.366 8.232c-.143.402-.832.748-1.157.794-.325.045-.733.069-2.144-.492-1.411-.561-2.47-1.745-2.614-1.936-.143-.191-1.121-1.488-1.121-2.839 0-1.35.707-2.016.958-2.274.251-.258.547-.323.73-.323.182 0 .365.002.525.01.169.008.396-.064.62.474.23.551.782 1.91.85 2.05.068.14.114.304.023.486-.091.182-.137.295-.274.453-.137.159-.288.354-.412.475-.137.135-.28.281-.12.556.16.274.71 1.171 1.523 1.895 1.047.931 1.93 1.218 2.204 1.353.274.135.434.113.594-.07.16-.182.685-.795.868-1.069.183-.274.366-.228.617-.137.251.091 1.599.754 1.873.891.274.137.457.205.525.32.068.114.068.662-.075 1.064z"/></svg>
                                    </a>

                                    <!-- Delete -->
                                    <form method="POST" 
                                          action="{{ route('admin.feedback.destroy', $fb) }}" 
                                          class="inline-block" 
                                          onsubmit="return confirm('Are you sure you want to delete Ticket #{{ $fb->ticket_no }}? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-500 hover:text-rose-400 hover:bg-rose-950/60 rounded-lg transition cursor-pointer" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-500">
                                    <div class="text-3xl mb-2">📭</div>
                                    <div class="font-semibold text-slate-300">No tickets found matching your filters</div>
                                    <div class="text-xs text-slate-500 mt-1">Submitted farmer grievances and feedback will show up here.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($feedbacks->hasPages())
                <div class="p-4 border-t border-slate-800 bg-slate-950/40">
                    {{ $feedbacks->links() }}
                </div>
            @endif
        </div>

    </div>

    <!-- TAB 2: FORM SETTINGS & CATEGORIES CMS -->
    <div x-show="activeTab === 'settings'" x-cloak class="space-y-6">

        <form method="POST" action="{{ route('admin.feedback.settings.update') }}">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Column 1: Feature Toggles & Support Channels -->
                <div class="space-y-6">
                    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm space-y-5">
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>🛠️</span> Form Feature Controls
                        </h2>

                        <!-- Enable Voice Recording -->
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                            <div>
                                <span class="text-xs font-bold text-slate-200 block">Voice Note Recording</span>
                                <span class="text-[11px] text-slate-400">Allow farmers to record voice notes</span>
                            </div>
                            <input type="checkbox" 
                                   name="enable_voice" 
                                   value="1" 
                                   {{ $settings['enable_voice'] ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-slate-700 text-emerald-600 focus:ring-emerald-500 bg-slate-900">
                        </div>

                        <!-- Max Voice Duration -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Max Voice Recording Limit</label>
                            <select name="max_voice_seconds" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                                <option value="60" {{ $settings['max_voice_seconds'] == 60 ? 'selected' : '' }}>1 Minute (60s)</option>
                                <option value="120" {{ $settings['max_voice_seconds'] == 120 ? 'selected' : '' }}>2 Minutes (120s)</option>
                                <option value="180" {{ $settings['max_voice_seconds'] == 180 ? 'selected' : '' }}>3 Minutes (180s - Recommended)</option>
                                <option value="300" {{ $settings['max_voice_seconds'] == 300 ? 'selected' : '' }}>5 Minutes (300s)</option>
                            </select>
                        </div>

                        <!-- Enable Photo Upload -->
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                            <div>
                                <span class="text-xs font-bold text-slate-200 block">Photo / Mandi Slip Upload</span>
                                <span class="text-[11px] text-slate-400">Allow attaching mandi slips & receipts</span>
                            </div>
                            <input type="checkbox" 
                                   name="enable_photos" 
                                   value="1" 
                                   {{ $settings['enable_photos'] ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-slate-700 text-emerald-600 focus:ring-emerald-500 bg-slate-900">
                        </div>

                        <!-- Enable Email Field -->
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                            <div>
                                <span class="text-xs font-bold text-slate-200 block">Email Address Field</span>
                                <span class="text-[11px] text-slate-400">Show optional email field on farmer form</span>
                            </div>
                            <input type="checkbox" 
                                   name="enable_email" 
                                   value="1" 
                                   {{ $settings['enable_email'] ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-slate-700 text-emerald-600 focus:ring-emerald-500 bg-slate-900">
                        </div>
                    </div>

                    <!-- Support Desk Channels -->
                    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm space-y-4">
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>💬</span> Support Desk Channels
                        </h2>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Official Support WhatsApp Number *</label>
                            <input type="text" 
                                   name="support_whatsapp" 
                                   value="{{ $settings['support_whatsapp'] }}" 
                                   placeholder="e.g. 919876543210"
                                   class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Farmers clicking "WhatsApp ನಲ್ಲಿ ಸಂಪರ್ಕಿಸಿ" will send their ticket directly to this WhatsApp number.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Notification Alert Email</label>
                            <input type="email" 
                                   name="notification_email" 
                                   value="{{ $settings['notification_email'] }}" 
                                   placeholder="support@krushibaandhava.org"
                                   class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Optional: Receive an email notification when a new ticket is logged.</span>
                        </div>
                    </div>
                </div>

                <!-- Column 2 & 3: Category Management CMS -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Issue Categories CMS -->
                    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h2 class="text-base font-bold text-white flex items-center gap-2">
                                    <span>🛠️</span> Issue Categories (ಸಮಸ್ಯೆ ವರದಿ ವರ್ಗಗಳು)
                                </h2>
                                <span class="text-xs text-slate-400">Categories shown when farmer is in "Report Issue" mode</span>
                            </div>
                            <button type="button" 
                                    @click="addIssueCategory()"
                                    class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-emerald-400 text-xs font-bold transition flex items-center gap-1 cursor-pointer">
                                <span>+ Add Category</span>
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(cat, index) in issueCategories" :key="index">
                                <div class="p-3.5 rounded-xl bg-slate-950/70 border border-slate-800 flex items-center gap-3">
                                    <input type="hidden" :name="'issue_categories[' + index + '][id]'" x-model="cat.id">
                                    
                                    <!-- Emoji Icon -->
                                    <div class="w-12">
                                        <input type="text" 
                                               :name="'issue_categories[' + index + '][icon]'" 
                                               x-model="cat.icon" 
                                               class="w-full text-center px-1 py-1.5 rounded-lg bg-slate-900 border border-slate-700 text-slate-100 text-sm">
                                    </div>

                                    <!-- English Label -->
                                    <div class="flex-1">
                                        <input type="text" 
                                               :name="'issue_categories[' + index + '][label_en]'" 
                                               x-model="cat.label_en" 
                                               placeholder="English Label" 
                                               class="w-full px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-700 text-slate-200 text-xs">
                                    </div>

                                    <!-- Kannada Label -->
                                    <div class="flex-1">
                                        <input type="text" 
                                               :name="'issue_categories[' + index + '][label_kn]'" 
                                               x-model="cat.label_kn" 
                                               placeholder="ಕನ್ನಡ ಹೆಸರು" 
                                               class="w-full px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-700 text-slate-200 text-xs font-kannada">
                                    </div>

                                    <!-- Active Checkbox -->
                                    <label class="flex items-center gap-1 cursor-pointer text-xs text-slate-400">
                                        <input type="checkbox" 
                                               :name="'issue_categories[' + index + '][is_active]'" 
                                               value="1" 
                                               x-model="cat.is_active" 
                                               class="rounded border-slate-700 text-emerald-600 bg-slate-900">
                                        <span>Active</span>
                                    </label>

                                    <!-- Remove button -->
                                    <button type="button" 
                                            @click="removeIssueCategory(index)"
                                            class="text-slate-500 hover:text-rose-400 text-lg leading-none cursor-pointer">
                                        &times;
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Feedback Categories CMS -->
                    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h2 class="text-base font-bold text-white flex items-center gap-2">
                                    <span>💡</span> Feedback Categories (ಸಲಹೆ & ಅಭಿಪ್ರಾಯ ವರ್ಗಗಳು)
                                </h2>
                                <span class="text-xs text-slate-400">Categories shown when farmer is in "Feedback" mode</span>
                            </div>
                            <button type="button" 
                                    @click="addFeedbackCategory()"
                                    class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-emerald-400 text-xs font-bold transition flex items-center gap-1 cursor-pointer">
                                <span>+ Add Category</span>
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(cat, index) in feedbackCategories" :key="index">
                                <div class="p-3.5 rounded-xl bg-slate-950/70 border border-slate-800 flex items-center gap-3">
                                    <input type="hidden" :name="'feedback_categories[' + index + '][id]'" x-model="cat.id">
                                    
                                    <!-- Emoji Icon -->
                                    <div class="w-12">
                                        <input type="text" 
                                               :name="'feedback_categories[' + index + '][icon]'" 
                                               x-model="cat.icon" 
                                               class="w-full text-center px-1 py-1.5 rounded-lg bg-slate-900 border border-slate-700 text-slate-100 text-sm">
                                    </div>

                                    <!-- English Label -->
                                    <div class="flex-1">
                                        <input type="text" 
                                               :name="'feedback_categories[' + index + '][label_en]'" 
                                               x-model="cat.label_en" 
                                               placeholder="English Label" 
                                               class="w-full px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-700 text-slate-200 text-xs">
                                    </div>

                                    <!-- Kannada Label -->
                                    <div class="flex-1">
                                        <input type="text" 
                                               :name="'feedback_categories[' + index + '][label_kn]'" 
                                               x-model="cat.label_kn" 
                                               placeholder="ಕನ್ನಡ ಹೆಸರು" 
                                               class="w-full px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-700 text-slate-200 text-xs font-kannada">
                                    </div>

                                    <!-- Active Checkbox -->
                                    <label class="flex items-center gap-1 cursor-pointer text-xs text-slate-400">
                                        <input type="checkbox" 
                                               :name="'feedback_categories[' + index + '][is_active]'" 
                                               value="1" 
                                               x-model="cat.is_active" 
                                               class="rounded border-slate-700 text-emerald-600 bg-slate-900">
                                        <span>Active</span>
                                    </label>

                                    <!-- Remove button -->
                                    <button type="button" 
                                            @click="removeFeedbackCategory(index)"
                                            class="text-slate-500 hover:text-rose-400 text-lg leading-none cursor-pointer">
                                        &times;
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Save Actions -->
                    <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-900 border border-slate-800">
                        <form method="POST" action="{{ route('admin.feedback.settings.reset') }}" onsubmit="return confirm('Reset all form settings to system defaults?');">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-rose-400 text-xs font-bold transition cursor-pointer">
                                ↺ Reset to System Defaults
                            </button>
                        </form>

                        <button type="submit" class="px-8 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm shadow-md transition cursor-pointer">
                            💾 Save Form Settings & Categories
                        </button>
                    </div>

                </div>

            </div>
        </form>

    </div>

    <!-- 4. Interactive Detail & Update Drawer / Modal -->
    <div x-show="activeTicketModal" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
        
        <div @click.away="activeTicketModal = false" 
             class="bg-slate-900 rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-800 overflow-hidden transform transition-all text-slate-100">
            
            <!-- Drawer Header -->
            <div class="px-6 py-4 bg-slate-950 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="font-mono text-sm font-bold text-emerald-400" x-text="ticket.ticket_no"></span>
                    <span class="text-xs px-2 py-0.5 rounded font-semibold"
                          :class="ticket.type === 'issue' ? 'bg-rose-950/80 text-rose-300 border border-rose-800/60' : 'bg-amber-950/80 text-amber-300 border border-amber-800/60'"
                          x-text="ticket.type === 'issue' ? '🛠️ Issue' : '💡 Feedback'"></span>
                </div>
                <button type="button" @click="activeTicketModal = false" class="text-slate-400 hover:text-white text-xl font-bold cursor-pointer">
                    &times;
                </button>
            </div>

            <!-- Drawer Body -->
            <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto custom-scrollbar">
                <!-- Farmer Contact Information -->
                <div class="grid grid-cols-2 gap-4 p-4 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Farmer Name</span>
                        <div class="text-sm font-semibold text-white" x-text="ticket.farmer_name || 'Anonymous Farmer'"></div>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Phone Number</span>
                        <div class="text-sm font-mono font-bold text-white flex items-center gap-2">
                            <span x-text="'+91 ' + ticket.farmer_phone"></span>
                            <a :href="'tel:+91' + ticket.farmer_phone" class="text-emerald-400 hover:text-emerald-300 text-xs font-semibold">📞 Call</a>
                        </div>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Category</span>
                        <div class="text-xs font-medium text-slate-300" x-text="ticket.category_label"></div>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Email Address</span>
                        <div class="text-xs font-mono text-slate-300" x-text="ticket.farmer_email || 'Not provided'"></div>
                    </div>
                </div>

                <!-- Agricultural Context -->
                <template x-if="ticket.crop_name || ticket.market_name">
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-emerald-950/50 border border-emerald-800/60 text-xs">
                        <template x-if="ticket.crop_name">
                            <div class="font-medium text-emerald-300">
                                🌾 Crop: <strong x-text="ticket.crop_name"></strong>
                            </div>
                        </template>
                        <template x-if="ticket.market_name">
                            <div class="font-medium text-emerald-300">
                                📍 Market: <strong x-text="ticket.market_name"></strong>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Voice Note Player (if recorded) -->
                <template x-if="ticket.voice_url">
                    <div class="p-4 rounded-2xl bg-violet-950/50 border border-violet-800/60">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-violet-300 flex items-center gap-1.5">
                                🎙️ Farmer's Voice Note Recording
                            </span>
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-mono text-violet-400" x-text="ticket.voice_duration ? (ticket.voice_duration + 's') : ''"></span>
                                <a :href="ticket.voice_url" download="voice_recording.webm" target="_blank" class="text-[11px] text-violet-300 hover:text-white font-semibold underline flex items-center gap-1 cursor-pointer">
                                    <span>⬇️ Download Audio</span>
                                </a>
                            </div>
                        </div>
                        <audio :src="ticket.voice_url" controls preload="metadata" class="w-full h-10 outline-none rounded-lg bg-slate-900"></audio>
                    </div>
                </template>

                <!-- Written Description -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Message / Grievance Description</label>
                    <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 text-sm text-slate-200 leading-relaxed whitespace-pre-wrap" x-text="ticket.message || 'No written text provided. (Listen to voice note above)'"></div>
                </div>

                <!-- Attached Photo / Mandi Slip -->
                <template x-if="ticket.photo_url">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Attached Mandi Slip / Photo</label>
                        <div class="relative group cursor-pointer inline-block" @click="openPhotoLightbox(ticket.photo_url)">
                            <img :src="ticket.photo_url" class="h-44 object-cover rounded-xl border border-slate-700 shadow-sm group-hover:opacity-90">
                            <span class="absolute bottom-2 right-2 px-2 py-1 bg-black/80 text-white text-[11px] rounded font-medium backdrop-blur-sm">
                                🔍 Click to Zoom
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Resolution & Status Update Form -->
                <form :action="'{{ url('/admin/feedback') }}/' + ticket.id" method="POST" class="pt-4 border-t border-slate-800 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Update Status *</label>
                            <select name="status" x-model="ticket.status" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-sm font-semibold">
                                <option value="new">🔴 New / Unread</option>
                                <option value="in_review">🟡 In Review</option>
                                <option value="resolved">🟢 Resolved</option>
                                <option value="rejected">⚪ Rejected</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Quick Contact</label>
                            <a :href="ticket.whatsapp_url" 
                               target="_blank" 
                               rel="noopener"
                               class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold shadow-sm transition">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.699c.971.53 1.761.802 2.796.803h.001c3.181 0 5.768-2.587 5.768-5.766 0-3.18-2.587-5.789-5.769-5.789zm3.366 8.232c-.143.402-.832.748-1.157.794-.325.045-.733.069-2.144-.492-1.411-.561-2.47-1.745-2.614-1.936-.143-.191-1.121-1.488-1.121-2.839 0-1.35.707-2.016.958-2.274.251-.258.547-.323.73-.323.182 0 .365.002.525.01.169.008.396-.064.62.474.23.551.782 1.91.85 2.05.068.14.114.304.023.486-.091.182-.137.295-.274.453-.137.159-.288.354-.412.475-.137.135-.28.281-.12.556.16.274.71 1.171 1.523 1.895 1.047.931 1.93 1.218 2.204 1.353.274.135.434.113.594-.07.16-.182.685-.795.868-1.069.183-.274.366-.228.617-.137.251.091 1.599.754 1.873.891.274.137.457.205.525.32.068.114.068.662-.075 1.064z"/></svg>
                                <span>WhatsApp Farmer</span>
                            </a>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Internal Resolution Notes (Admin Only)</label>
                        <textarea name="admin_notes" 
                                  x-model="ticket.admin_notes" 
                                  rows="3" 
                                  placeholder="Notes on resolution, call summaries, market verification details..."
                                  class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="activeTicketModal = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-6 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold shadow-md transition cursor-pointer">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 5. Fullscreen Photo Lightbox -->
    <div x-show="lightboxPhotoUrl" 
         x-cloak 
         @click="lightboxPhotoUrl = null"
         class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex items-center justify-center p-4 cursor-zoom-out">
        <img :src="lightboxPhotoUrl" class="max-h-[90vh] max-w-[90vw] object-contain rounded-xl shadow-2xl">
        <button type="button" class="absolute top-4 right-4 text-white text-3xl font-bold cursor-pointer">&times;</button>
    </div>

    <!-- 6. Floating Audio Player Modal -->
    <div x-show="floatingAudioUrl" 
         x-cloak 
         class="fixed bottom-6 right-6 z-40 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-4 max-w-sm w-full text-slate-100">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-slate-300 flex items-center gap-1.5">
                🎙️ Listening to Voice Note
            </span>
            <div class="flex items-center gap-3">
                <a :href="floatingAudioUrl" download="voice_recording.webm" target="_blank" class="text-[11px] text-violet-400 hover:text-white underline font-semibold">⬇️ Save</a>
                <button type="button" @click="floatingAudioUrl = null" class="text-slate-400 hover:text-white text-base font-bold cursor-pointer leading-none">&times;</button>
            </div>
        </div>
        <audio :src="floatingAudioUrl" autoplay controls class="w-full h-9 outline-none bg-slate-950 rounded"></audio>
    </div>

</div>

<script>
function adminFeedbackManager(config) {
    return {
        activeTab: config.initialTab || 'inbox',
        activeTicketModal: false,
        lightboxPhotoUrl: null,
        floatingAudioUrl: null,
        ticket: {},

        issueCategories: config.issueCategories || [],
        feedbackCategories: config.feedbackCategories || [],

        async openTicketDrawer(id) {
            try {
                const res = await fetch(`{{ url('/admin/feedback') }}/${id}`);
                const json = await res.json();
                if (json.success) {
                    this.ticket = json.data;
                    this.activeTicketModal = true;
                }
            } catch (e) {
                console.error('Error fetching ticket:', e);
                alert('Could not load ticket details.');
            }
        },

        playVoice(url) {
            this.floatingAudioUrl = url;
        },

        openPhotoLightbox(url) {
            this.lightboxPhotoUrl = url;
        },

        addIssueCategory() {
            this.issueCategories.push({
                id: 'custom_issue_' + Date.now(),
                icon: '📌',
                label_en: 'New Issue Category',
                label_kn: 'ಹೊಸ ಸಮಸ್ಯೆ ವಿಷಯ',
                is_active: true
            });
        },

        removeIssueCategory(idx) {
            this.issueCategories.splice(idx, 1);
        },

        addFeedbackCategory() {
            this.feedbackCategories.push({
                id: 'custom_feedback_' + Date.now(),
                icon: '💬',
                label_en: 'New Feedback Topic',
                label_kn: 'ಹೊಸ ಸಲಹೆ ವಿಷಯ',
                is_active: true
            });
        },

        removeFeedbackCategory(idx) {
            this.feedbackCategories.splice(idx, 1);
        }
    };
}
</script>
@endsection
