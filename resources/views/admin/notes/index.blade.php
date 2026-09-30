@extends('layouts.admin')

@section('title', 'Admin Notes & Media Guidelines')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">

    <!-- Top Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-emerald-400 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-200">System & Governance</span>
                <span>/</span>
                <span class="text-emerald-400">Admin Notes & Guidelines</span>
            </div>
            <h1 class="text-2xl font-black text-white flex items-center gap-3">
                <span class="p-2 rounded-2xl bg-emerald-950/80 border border-emerald-700/50 text-emerald-400 text-xl shadow-inner">📝</span>
                Admin Notes & System Guidelines
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Official specifications for crop media, image dimensions, ingestion best practices, and team operational notes.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.crops.index') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold text-slate-300 bg-slate-900 border border-slate-700/80 hover:bg-slate-800 transition flex items-center gap-1.5 shadow-sm">
                <span>🌾</span> Manage Crops
            </a>
            <a href="#custom-notes" 
               class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 border border-emerald-500/40 transition flex items-center gap-1.5 shadow-md shadow-emerald-950/40">
                <span>✍️</span> Edit Operational Notes
            </a>
        </div>
    </div>

    <!-- Quick Navigation Anchors -->
    <div class="flex flex-wrap items-center gap-2 p-2 bg-slate-900/60 border border-slate-800 rounded-2xl">
        <a href="#media-guidelines" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition flex items-center gap-2">
            <span>🖼️</span> Image & Media Standards
        </a>
        <a href="#crop-ingestion" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition flex items-center gap-2">
            <span>🔄</span> Ingestion & Mapping Rules
        </a>
        <a href="#apmc-standards" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition flex items-center gap-2">
            <span>🏛️</span> APMC & Market Standards
        </a>
        <a href="#custom-notes" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-950 text-emerald-300 border border-emerald-800/60 hover:bg-emerald-900 transition flex items-center gap-2">
            <span>✍️</span> Operational Scratchpad
        </a>
    </div>

    <!-- ============================================================== -->
    <!-- SECTION 1: CROP IMAGE & MEDIA SPECIFICATIONS (Primary Focus)   -->
    <!-- ============================================================== -->
    <div id="media-guidelines" class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-800/80 gap-3">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">🖼️</span>
                    <h2 class="text-lg font-black text-white">Preferred Crop Image Specifications</h2>
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-mono font-bold bg-emerald-950 text-emerald-300 border border-emerald-800/80">
                        Official Standard
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    Follow these guidelines when uploading or selecting crop photos to ensure optimal rendering across mobile PWA and desktop screens.
                </p>
            </div>
            
            <a href="{{ route('admin.crops.index') }}" class="text-xs font-bold text-emerald-400 hover:text-emerald-300 transition flex items-center gap-1 self-start sm:self-auto">
                <span>Apply to Crop</span> →
            </a>
        </div>

        <!-- 4 Key Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Aspect Ratio -->
            <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 flex flex-col justify-between">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Aspect Ratio</div>
                    <div class="text-xl font-extrabold text-white flex items-center gap-2">
                        <span>1:1</span>
                        <span class="text-xs font-bold text-emerald-400">(Square)</span>
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 mt-3 leading-relaxed">
                    All farmer discovery tiles, category cards, and gallery grids use square containers with <code class="text-emerald-300 font-mono">object-cover</code>. Square photos prevent unwanted cropping.
                </p>
            </div>

            <!-- Dimensions -->
            <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 flex flex-col justify-between">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Dimensions</div>
                    <div class="text-xl font-extrabold text-white flex items-center gap-2">
                        <span>600 × 600</span>
                        <span class="text-xs font-bold text-slate-400">to 800px</span>
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 mt-3 leading-relaxed">
                    Delivers sharp visual fidelity on High-DPI / Retina smartphone screens (2x/3x display densities). Minimum acceptable: <strong class="text-slate-300">400×400 px</strong>.
                </p>
            </div>

            <!-- Target File Size -->
            <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 flex flex-col justify-between">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Target File Size</div>
                    <div class="text-xl font-extrabold text-emerald-400 flex items-center gap-2">
                        <span>50 – 200 KB</span>
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 mt-3 leading-relaxed">
                    Max allowed upload: <strong class="text-slate-300">5 MB</strong>. However, rural 3G/4G connectivity requires lightweight assets so farmer price feeds load instantly without lag.
                </p>
            </div>

            <!-- File Format -->
            <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 flex flex-col justify-between">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Supported Formats</div>
                    <div class="text-xl font-extrabold text-white flex items-center gap-2">
                        <span>WEBP / JPG</span>
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 mt-3 leading-relaxed">
                    <strong class="text-emerald-400">WEBP</strong> provides ~30% smaller file size with zero quality loss. Also accepted: <strong class="text-slate-300">JPG, PNG, SVG</strong>.
                </p>
            </div>
        </div>

        <!-- Dos & Don'ts Breakdown -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
            <!-- DOs -->
            <div class="p-4 sm:p-5 rounded-2xl bg-emerald-950/20 border border-emerald-900/40 space-y-3">
                <div class="flex items-center gap-2 text-sm font-extrabold text-emerald-400">
                    <span>✅</span> Best Practices (What to Do)
                </div>
                <ul class="text-xs text-slate-300 space-y-2.5 leading-relaxed">
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-400 mt-0.5">•</span>
                        <span><strong>Keep the crop centered:</strong> Ensure the primary commodity (e.g. arecanut bunch, tomato, coffee cherries) is centered in the middle 70% of the frame.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-400 mt-0.5">•</span>
                        <span><strong>High contrast & natural lighting:</strong> Use clear agricultural photos that remain easily recognizable even when viewed as a 40×40 px thumbnail in APMC market tables.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-400 mt-0.5">•</span>
                        <span><strong>Resize before uploading:</strong> Scale down large smartphone camera shots (often 4000×3000 px, 8 MB) to 800×800 px before upload to save server disk space and bandwidth.</span>
                    </li>
                </ul>
            </div>

            <!-- DONTs -->
            <div class="p-4 sm:p-5 rounded-2xl bg-rose-950/20 border border-rose-900/40 space-y-3">
                <div class="flex items-center gap-2 text-sm font-extrabold text-rose-400">
                    <span>❌</span> Practices to Avoid (What Not to Do)
                </div>
                <ul class="text-xs text-slate-300 space-y-2.5 leading-relaxed">
                    <li class="flex items-start gap-2">
                        <span class="text-rose-400 mt-0.5">•</span>
                        <span><strong>No extreme aspect ratios:</strong> Avoid panoramic banners (16:9) or tall phone screenshots (9:16) as icons; they will be clipped severely.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-rose-400 mt-0.5">•</span>
                        <span><strong>No embedded text or price watermarks:</strong> Do not place text, prices, or marketing logos directly on the photo; prices change daily and text becomes illegible when scaled.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-rose-400 mt-0.5">•</span>
                        <span><strong>No uncompressed 5MB+ files:</strong> Heavy files slow down the entire dashboard and mobile apps for farmers in weak signal areas.</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Free Compression Utilities Box -->
        <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="text-2xl">⚡</span>
                <div>
                    <div class="text-xs font-bold text-white">Recommended Free Image Compression & Resizing Tools</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">Use these web tools to quickly optimize photos before adding them to the gallery:</div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="https://squoosh.app" target="_blank" rel="noopener noreferrer" 
                   class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 text-slate-200 hover:text-white hover:bg-slate-700 border border-slate-700 transition">
                    Squoosh (Google) ↗
                </a>
                <a href="https://tinypng.com" target="_blank" rel="noopener noreferrer" 
                   class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 text-slate-200 hover:text-white hover:bg-slate-700 border border-slate-700 transition">
                    TinyPNG ↗
                </a>
                <a href="https://ezgif.com/resize" target="_blank" rel="noopener noreferrer" 
                   class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 text-slate-200 hover:text-white hover:bg-slate-700 border border-slate-700 transition">
                    Ezgif Resizer ↗
                </a>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- SECTION 2: CROP INGESTION & DATA SYNC RULES                     -->
    <!-- ============================================================== -->
    <div id="crop-ingestion" class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
        <div class="flex items-center gap-2.5 pb-3 border-b border-slate-800">
            <span class="text-xl">🔄</span>
            <div>
                <h2 class="text-base font-black text-white">Crop Ingestion & Variety Mapping Rules</h2>
                <p class="text-xs text-slate-400">Rules governing daily price syncs, KRAMA, and Agmarknet feeds.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-emerald-400 flex items-center gap-1.5">
                    <span>1.</span> Configured Sync Crops
                </div>
                <p class="text-slate-300 leading-relaxed">
                    In <strong>Run Ingestion Sync</strong>, the Target Crop dropdown only displays crops configured in the source settings. If a crop is missing from manual sync, enable it in <em>Data Sources & APIs → Configure Sync Crops</em>.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-cyan-400 flex items-center gap-1.5">
                    <span>2.</span> Variety & Grade Inspection
                </div>
                <p class="text-slate-300 leading-relaxed">
                    Always use the <strong>Live API Inspector</strong> in the crop form to see exact variety strings returned by KRAMA / Agmarknet before creating alias mappings.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-amber-400 flex items-center gap-1.5">
                    <span>3.</span> Zero Data Waste Recycling
                </div>
                <p class="text-slate-300 leading-relaxed">
                    When custom uploaded images are replaced or batch-deleted, unreferenced files on disk are automatically unlinked and sanitized to prevent broken images.
                </p>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- SECTION 3: APMC & GEOLOCATION STANDARDS                         -->
    <!-- ============================================================== -->
    <div id="apmc-standards" class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
        <div class="flex items-center gap-2.5 pb-3 border-b border-slate-800">
            <span class="text-xl">🏛️</span>
            <div>
                <h2 class="text-base font-black text-white">APMC Mandi & Proximity Standards</h2>
                <p class="text-xs text-slate-400">Rules for geographic radius and market price discovery.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-1.5">
                <div class="font-bold text-white">Standard Market Discovery Radius</div>
                <p class="text-slate-300 leading-relaxed">
                    Default proximity radius is set to <strong>300 km</strong>. High-value plantation crops (Arecanut, Coffee, Black Pepper) can search statewide hubs across Malnad and Coastal Karnataka.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-1.5">
                <div class="font-bold text-white">Default Sorting</div>
                <p class="text-slate-300 leading-relaxed">
                    Set to <strong>Nearest Mandi First</strong> using Haversine GPS calculations. High Price First can be selected for regional comparisons.
                </p>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- SECTION 4: ADMIN OPERATIONAL SCRATCHPAD & CUSTOM MEMOS          -->
    <!-- ============================================================== -->
    <div id="custom-notes" class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-800 gap-2">
            <div class="flex items-center gap-2.5">
                <span class="text-xl">✍️</span>
                <div>
                    <h2 class="text-base font-black text-white">Admin Operational Scratchpad</h2>
                    <p class="text-xs text-slate-400">Save internal memos, APMC contact numbers, or team reminders. Visible to all admins.</p>
                </div>
            </div>
            <span class="text-[11px] text-slate-500 font-mono">Persisted in System Settings</span>
        </div>

        <form action="{{ route('admin.notes.update') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="admin_notes" class="block text-xs font-bold text-slate-300 mb-2">Team Operational Memos & Scratchpad</label>
                <textarea id="admin_notes" name="admin_notes" rows="6"
                          placeholder="Type internal reminders, APMC secretary contact details, market holiday schedules, or notes for other admins..."
                          class="w-full p-4 rounded-2xl bg-slate-950 border border-slate-800 text-white text-xs font-sans leading-relaxed focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">{{ old('admin_notes', $customNotes) }}</textarea>
            </div>

            <div class="flex items-center justify-between pt-1">
                <p class="text-[11px] text-slate-500">Supports plain text or Markdown style notes. Updates immediately for all staff.</p>
                <button type="submit" 
                        class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-md shadow-emerald-950/40 border border-emerald-500/40 transition cursor-pointer">
                    💾 Save Operational Notes
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
