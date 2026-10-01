@extends('layouts.admin')

@section('content')
<div x-data="{
    currentType: '{{ $currentType }}',
    editModalOpen: false,
    editId: null,
    editName: '',
    editNameKn: '',
    editSlug: '',
    editIcon: '',
    editDisplayOrder: 0,
    editIsActive: true,
    editDescription: '',
    editFormAction: '',

    openEdit(item) {
        this.editId = item.id;
        this.editName = item.name;
        this.editNameKn = item.name_kn || '';
        this.editSlug = item.slug;
        this.editIcon = item.icon || '';
        this.editDisplayOrder = item.display_order;
        this.editIsActive = Boolean(item.is_active);
        this.editDescription = item.description || '';
        this.editFormAction = `{{ url('admin/videos/taxonomies') }}/${item.id}`;
        this.editModalOpen = true;
    },

    closeEdit() {
        this.editModalOpen = false;
        this.editId = null;
    },

    async toggleTaxonomy(id, btnEl) {
        try {
            const res = await fetch(`{{ url('admin/videos/taxonomies') }}/${id}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                btnEl.classList.toggle('bg-emerald-950', data.is_active);
                btnEl.classList.toggle('text-emerald-300', data.is_active);
                btnEl.classList.toggle('border-emerald-800', data.is_active);
                btnEl.classList.toggle('bg-rose-950', !data.is_active);
                btnEl.classList.toggle('text-rose-300', !data.is_active);
                btnEl.classList.toggle('border-rose-800', !data.is_active);
                btnEl.innerText = data.is_active ? 'Active' : 'Inactive';
            }
        } catch(e) {
            console.error('Toggle failed', e);
        }
    }
}" class="space-y-6">

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-950/80 border border-emerald-800/80 text-emerald-200 text-xs sm:text-sm font-semibold flex items-center justify-between shadow-lg">
            <div class="flex items-center gap-2">
                <span>✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-400 hover:text-white font-bold">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-950/80 border border-rose-800/80 text-rose-200 text-xs sm:text-sm font-semibold flex items-center justify-between shadow-lg">
            <div class="flex items-center gap-2">
                <span>⚠</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-400 hover:text-white font-bold">&times;</button>
        </div>
    @endif

    <!-- Top Navigation & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-white transition">Dashboard</a>
                <span>/</span>
                <a href="{{ route('admin.videos.index') }}" class="hover:text-white transition">Educational Videos</a>
                <span>/</span>
                <span class="text-emerald-400 font-semibold">Taxonomies</span>
            </div>
            <h2 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                <span>🏷️ Taxonomies & Classifications</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">
                Manage educational video categories and crop growth stages to keep farming guides structured and easily searchable.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.videos.index') }}" 
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-700 hover:bg-slate-800 text-slate-200 font-bold text-xs transition shadow-sm">
                <span>&larr; Back to Video Library</span>
            </a>
            <a href="{{ route('admin.videos.create') }}" 
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition shadow-sm">
                <span>+ Add Video</span>
            </a>
        </div>
    </div>

    <!-- Type Switch Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-800 pb-3">
        <a href="{{ route('admin.videos.taxonomies.index', ['type' => 'category']) }}" 
           class="px-4 py-2 rounded-xl font-bold text-xs transition inline-flex items-center gap-2 {{ $currentType === 'category' ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:text-white hover:bg-slate-800' }}">
            <span>📁 Thematic Categories</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $currentType === 'category' ? 'bg-emerald-800 text-white' : 'bg-slate-800 text-slate-300' }}">
                {{ $categoryCount }}
            </span>
        </a>
        <a href="{{ route('admin.videos.taxonomies.index', ['type' => 'growth_stage']) }}" 
           class="px-4 py-2 rounded-xl font-bold text-xs transition inline-flex items-center gap-2 {{ $currentType === 'growth_stage' ? 'bg-amber-600 text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:text-white hover:bg-slate-800' }}">
            <span>🌱 Crop Growth Stages</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $currentType === 'growth_stage' ? 'bg-amber-800 text-white' : 'bg-slate-800 text-slate-300' }}">
                {{ $growthStageCount }}
            </span>
        </a>
    </div>

    <!-- Main Two-Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left Column: Add New Item Form -->
        <div class="lg:col-span-5 bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl">
            <h3 class="text-sm font-black text-white flex items-center gap-2 mb-1">
                <span>+</span>
                <span>Add New {{ $currentType === 'category' ? 'Thematic Category' : 'Crop Growth Stage' }}</span>
            </h3>
            <p class="text-[11px] text-slate-400 mb-4">
                {{ $currentType === 'category' ? 'Categorize videos by agriculture topic (e.g. Pest Control, Organic Farming).' : 'Classify videos by farming lifecycle (e.g. Sowing, Flowering, Harvest).' }}
            </p>

            <form action="{{ route('admin.videos.taxonomies.store') }}" method="POST" class="space-y-4" x-data="{
                nameInput: '',
                slugInput: '',
                syncSlug() {
                    if (!this.slugInput || this.slugInput === this.prevAutoSlug) {
                        this.slugInput = this.nameInput.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
                        this.prevAutoSlug = this.slugInput;
                    }
                },
                prevAutoSlug: ''
            }">
                @csrf
                <input type="hidden" name="type" value="{{ $currentType }}">

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">
                        Name in English <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           x-model="nameInput" 
                           @input="syncSlug()" 
                           required 
                           placeholder="{{ $currentType === 'category' ? 'e.g. Natural Pest Management' : 'e.g. Flowering & Fruit Setting' }}"
                           class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 font-sans">
                    @error('name') <span class="text-rose-400 text-[10px] block mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">
                        Name in Kannada (ಕನ್ನಡ ಹೆಸರು)
                    </label>
                    <input type="text" 
                           name="name_kn" 
                           placeholder="{{ $currentType === 'category' ? 'ಉದಾಹರಣೆಗೆ: ನೈಸರ್ಗಿಕ ಕೀಟ ನಿರ್ವಹಣೆ' : 'ಉದಾಹರಣೆಗೆ: ಹೂವಾಡುವ & ಕಾಯಿ ಕಟ್ಟುವ ಹಂತ' }}"
                           class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 font-kannada">
                    @error('name_kn') <span class="text-rose-400 text-[10px] block mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Unique Key / Slug
                        </label>
                        <input type="text" 
                               name="slug" 
                               x-model="slugInput" 
                               placeholder="e.g. natural_pest"
                               class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 font-mono">
                        <span class="text-[10px] text-slate-500 mt-0.5 block">Stored in video record</span>
                        @error('slug') <span class="text-rose-400 text-[10px] block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Icon / Emoji
                        </label>
                        <input type="text" 
                               name="icon" 
                               id="createIconInput"
                               value="{{ $currentType === 'category' ? '🌾' : '🌱' }}"
                               placeholder="🌾, 🐛, 🌱"
                               class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 text-center font-sans">
                    </div>
                </div>

                <!-- Quick Emoji Picker -->
                <div>
                    <span class="text-[10px] text-slate-400 block mb-1">Quick Select Emoji:</span>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        @php
                            $emojis = ['🌾', '🌱', '🌿', '🐛', '💧', '🚜', '🏆', '📦', '🍃', '✂️', '📌', '🧪', '🌻', '🍎', '🌦️'];
                        @endphp
                        @foreach($emojis as $e)
                            <button type="button" 
                                    @click="document.getElementById('createIconInput').value = '{{ $e }}'"
                                    class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700/60 text-xs flex items-center justify-center transition">
                                {{ $e }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Display Order</label>
                        <input type="number" 
                               name="display_order" 
                               value="0" 
                               class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div class="flex items-center pt-5">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 bg-slate-950 border-slate-700">
                            <span class="text-xs font-bold text-slate-200">Active Status</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Description (Optional)</label>
                    <textarea name="description" 
                              rows="2" 
                              placeholder="Brief guidance for editors..."
                              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>

                <button type="submit" 
                        class="w-full py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs shadow-md transition cursor-pointer">
                    Save {{ $currentType === 'category' ? 'Category' : 'Growth Stage' }}
                </button>
            </form>
        </div>

        <!-- Right Column: Existing List Table -->
        <div class="lg:col-span-7 bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-white">
                        Existing {{ $currentType === 'category' ? 'Categories' : 'Growth Stages' }}
                    </h3>
                    <span class="text-[11px] text-slate-400">Total: {{ $items->count() }} items registered</span>
                </div>
            </div>

            @if($items->isEmpty())
                <div class="p-8 text-center text-slate-400 text-xs">
                    No items found. Create your first {{ $currentType === 'category' ? 'category' : 'growth stage' }} on the left!
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-950 text-slate-400 uppercase text-[10px] font-bold tracking-wider border-b border-slate-800">
                            <tr>
                                <th class="px-4 py-3">Icon & Name</th>
                                <th class="px-3 py-3">Slug Key</th>
                                <th class="px-3 py-3 text-center">Videos</th>
                                <th class="px-3 py-3 text-center">Order</th>
                                <th class="px-3 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/80">
                            @foreach($items as $item)
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-7 h-7 rounded-lg bg-slate-800 flex items-center justify-center text-sm shrink-0 border border-slate-700">
                                                {{ $item->icon ?: '📌' }}
                                            </span>
                                            <div>
                                                <div class="font-bold text-white text-xs">
                                                    {{ $item->name }}
                                                </div>
                                                @if($item->name_kn)
                                                    <div class="text-[11px] text-emerald-400 font-kannada">
                                                        {{ $item->name_kn }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-3 py-3 font-mono text-[11px] text-slate-400">
                                        {{ $item->slug }}
                                    </td>

                                    <td class="px-3 py-3 text-center">
                                        @php $count = $item->videos_count; @endphp
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $count > 0 ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-slate-800 text-slate-400' }}">
                                            {{ $count }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-3 text-center font-mono text-slate-400">
                                        {{ $item->display_order }}
                                    </td>

                                    <td class="px-3 py-3 text-center">
                                        <button type="button" 
                                                @click="toggleTaxonomy({{ $item->id }}, $el)"
                                                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border transition cursor-pointer {{ $item->is_active ? 'bg-emerald-950 text-emerald-300 border-emerald-800' : 'bg-rose-950 text-rose-300 border-rose-800' }}">
                                            {{ $item->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Edit Button -->
                                            <button type="button" 
                                                    @click="openEdit({{ json_encode($item) }})"
                                                    class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 transition text-xs font-bold"
                                                    title="Edit Item">
                                                ✏️
                                            </button>

                                            <!-- Delete Button -->
                                            @if($count === 0)
                                                <form action="{{ route('admin.videos.taxonomies.destroy', $item->id) }}" 
                                                      method="POST" 
                                                      onsubmit="return confirm('Are you sure you want to delete this {{ $item->type === 'category' ? 'category' : 'growth stage' }}?')" 
                                                      class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="p-1.5 rounded-lg bg-rose-950 hover:bg-rose-900 border border-rose-800/60 text-rose-300 transition text-xs font-bold"
                                                            title="Delete Item">
                                                        🗑️
                                                    </button>
                                                </form>
                                            @else
                                                <span class="p-1.5 rounded-lg bg-slate-800/40 text-slate-600 text-xs cursor-not-allowed" title="Cannot delete: videos are linked">
                                                    🔒
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="editModalOpen" 
         x-cloak
         x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         @keydown.escape.window="closeEdit()">
        <div class="bg-slate-900 rounded-3xl overflow-hidden shadow-2xl max-w-lg w-full border border-slate-700/80" 
             @click.away="closeEdit()">
            
            <div class="flex items-center justify-between p-4 bg-slate-950 border-b border-slate-800">
                <h4 class="text-sm font-bold text-white flex items-center gap-2">
                    <span>✏️ Edit {{ $currentType === 'category' ? 'Category' : 'Growth Stage' }}</span>
                </h4>
                <button type="button" @click="closeEdit()" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 text-base font-bold">
                    ✕
                </button>
            </div>

            <form :action="editFormAction" method="POST" class="p-5 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">
                        Name in English <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           x-model="editName" 
                           required 
                           class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 font-sans">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">
                        Name in Kannada (ಕನ್ನಡ ಹೆಸರು)
                    </label>
                    <input type="text" 
                           name="name_kn" 
                           x-model="editNameKn" 
                           class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 font-kannada">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Unique Slug
                        </label>
                        <input type="text" 
                               name="slug" 
                               x-model="editSlug" 
                               required 
                               class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Icon / Emoji
                        </label>
                        <input type="text" 
                               name="icon" 
                               x-model="editIcon" 
                               class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 text-center font-sans">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Display Order</label>
                        <input type="number" 
                               name="display_order" 
                               x-model="editDisplayOrder" 
                               class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div class="flex items-center pt-5">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" :checked="editIsActive" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 bg-slate-950 border-slate-700">
                            <span class="text-xs font-bold text-slate-200">Active Status</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Description (Optional)</label>
                    <textarea name="description" 
                              x-model="editDescription" 
                              rows="2" 
                              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                    <button type="button" @click="closeEdit()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
