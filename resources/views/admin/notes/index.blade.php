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
            <span>🏛️</span> Mandi Naming & Single-Town Consolidation
        </a>
        <a href="#freshness-window" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition flex items-center gap-2">
            <span>⏱️</span> 4-Day Freshness Window & Staleness
        </a>
        <a href="#cron-scheduling" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition flex items-center gap-2">
            <span>⚙️</span> Cron Controls & Ingestion Feeds
        </a>
        <a href="#bilingual-fallback" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition flex items-center gap-2">
            <span>🌐</span> Bilingual Locality & Fallback
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
                <p class="text-xs text-slate-400">Rules governing daily price syncs, multi-source ingestion, and automated grade resolution.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-emerald-400 flex items-center gap-1.5">
                    <span>1.</span> Configured Sync Crops
                </div>
                <p class="text-slate-300 leading-relaxed">
                    In <strong>Run Ingestion Sync</strong>, the Target Crop dropdown only displays crops configured in active source settings. If a crop is missing from manual sync, enable it in <em>Data Sources & APIs → Configure Sync Crops</em>.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-cyan-400 flex items-center gap-1.5">
                    <span>2.</span> Live API Inspector & Raw Strings
                </div>
                <p class="text-slate-300 leading-relaxed">
                    Always use the <strong>Live API Inspector</strong> in the crop form to see exact variety/grade strings returned by KRAMA, Coffee Board, or CDB before creating alias mappings in the crop catalog.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-amber-400 flex items-center gap-1.5">
                    <span>3.</span> Zero Data Waste Recycling
                </div>
                <p class="text-slate-300 leading-relaxed">
                    When custom uploaded images are replaced or batch-deleted, unreferenced files on disk are automatically unlinked and sanitized to prevent orphaned storage bloat or broken image links.
                </p>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- SECTION 3: MANDI CATALOG & SINGLE-TOWN CONSOLIDATION POLICY     -->
    <!-- ============================================================== -->
    <div id="apmc-standards" class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-5">
        <div class="flex items-center gap-2.5 pb-3 border-b border-slate-800">
            <span class="text-xl">🏛️</span>
            <div>
                <h2 class="text-base font-black text-white">Mandi Naming & Single Canonical Town Consolidation</h2>
                <p class="text-xs text-slate-400">Official architectural standards for deduplicated market catalog entries across Karnataka.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
            <!-- Policy 1: Clean Town Names -->
            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-emerald-400 flex items-center gap-1.5">
                    <span>🏷️</span> Clean Town Names (No Suffixes)
                </div>
                <p class="text-slate-300 leading-relaxed">
                    Market names must be stored as plain, canonical town names (e.g. <strong class="text-white">Madikeri</strong>, <strong class="text-white">Sakleshpur</strong>, <strong class="text-white">Tumakuru</strong>, <strong class="text-white">Shivamogga</strong>, <strong class="text-white">Arsikere</strong>).
                </p>
                <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-[11px] text-slate-400 space-y-1">
                    <div><span class="text-rose-400 font-bold">❌ Do Not:</span> Store <em>"Sakleshpur APMC"</em> or <em>"ಹಾಸನ ಎಪಿಎಂಸಿ"</em></div>
                    <div><span class="text-emerald-400 font-bold">✅ Standard:</span> Store <em>"Sakleshpur"</em> / <em>"ಹಾಸನ"</em></div>
                </div>
            </div>

            <!-- Policy 2: Commodity Board Consolidation -->
            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-cyan-400 flex items-center gap-1.5">
                    <span>🤝</span> Single Canonical Town Mandi
                </div>
                <p class="text-slate-300 leading-relaxed">
                    Never create duplicate market records for Commodity Boards (e.g., Coffee Board or Coconut Development Board). Map all board feeds directly to the existing canonical town mandi.
                </p>
                <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-[11px] text-slate-400 space-y-1">
                    <div><span class="text-rose-400 font-bold">❌ Do Not:</span> Create <em>"Madikeri (Coffee Board Centre)"</em> alongside <em>"Madikeri"</em></div>
                    <div><span class="text-emerald-400 font-bold">✅ Standard:</span> Feed both coffee prices and APMC prices into canonical <em>"Madikeri"</em></div>
                </div>
            </div>

            <!-- Policy 3: Proximity Radius & Geolocation -->
            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-amber-400 flex items-center gap-1.5">
                    <span>📍</span> Proximity & GPS Discovery
                </div>
                <p class="text-slate-300 leading-relaxed">
                    Standard search radius is <strong class="text-white">300 km</strong> using Haversine GPS calculations. By maintaining single canonical mandis per town, distance calculations and "Nearest Mandi First" sorting remain accurate.
                </p>
                <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-[11px] text-slate-400">
                    <span class="text-slate-300 font-semibold">Benefit:</span> Farmers see a clean, single dropdown entry without confusing duplicates or fragmented history.
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- SECTION 4: 4-DAY FRESHNESS WINDOW & STALENESS ARCHITECTURE       -->
    <!-- ============================================================== -->
    <div id="freshness-window" class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
        <div class="flex items-center gap-2.5 pb-3 border-b border-slate-800">
            <span class="text-xl">⏱️</span>
            <div>
                <h2 class="text-base font-black text-white">Dynamic 4-Day Freshness Window & Staleness Architecture</h2>
                <p class="text-xs text-slate-400">How the platform prevents empty markets during weekends, holidays, and trading lulls.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-emerald-400 flex items-center gap-1.5">
                    <span>📅</span> Weekend & Holiday Anchor Window
                </div>
                <p class="text-slate-300 leading-relaxed">
                    Mandis halt auctions on Sundays, government holidays, and local festivals. If queries checked only <code class="text-emerald-300 font-mono">date = today</code>, crops like Black Pepper, Coffee, or Arecanut would display empty screens on Sunday and Monday mornings.
                </p>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                    The engine anchors to the latest verified session within a rolling <strong class="text-white">4-day window</strong> (<code class="text-emerald-300 font-mono">today</code>, <code class="text-emerald-300 font-mono">yesterday</code>, <code class="text-emerald-300 font-mono">-2d</code>, <code class="text-emerald-300 font-mono">-3d</code>).
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-cyan-400 flex items-center gap-1.5">
                    <span>🏷️</span> Transparent Date Badge Display
                </div>
                <p class="text-slate-300 leading-relaxed">
                    Every price card and modal renders the exact date of the recorded trade (e.g. <em>"Today"</em>, <em>"Yesterday"</em>, or <em>"02 Oct 2026"</em>). Farmers always know exactly how recent the modal price quote is.
                </p>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                    This completely prevents misleading outdated quotes while eliminating frustrating blank pages.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-amber-400 flex items-center gap-1.5">
                    <span>⚠️</span> Staleness Indicators (> 4 Days)
                </div>
                <p class="text-slate-300 leading-relaxed">
                    If an APMC mandi has not reported trades for more than 4 consecutive calendar days, the system marks the price session as <strong>Awaiting Fresh Market Feed</strong> with an amber indicator rather than guessing rates.
                </p>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                    Admins can view dormant mandis in the Price Ingestion Logs and trigger targeted on-demand syncs if needed.
                </p>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- SECTION 5: CRON SCHEDULING & DATA SOURCE AUTOMATION CONTROLS    -->
    <!-- ============================================================== -->
    <div id="cron-scheduling" class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
        <div class="flex items-center gap-2.5 pb-3 border-b border-slate-800">
            <span class="text-xl">⚙️</span>
            <div>
                <h2 class="text-base font-black text-white">Cron Scheduling & Independent Source Controls</h2>
                <p class="text-xs text-slate-400">Automated task scheduler configuration and provider-level toggles.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-emerald-400 flex items-center gap-1.5">
                    <span>🎛️</span> Independent <code class="text-emerald-300 font-mono">is_cron_enabled</code> Toggle
                </div>
                <p class="text-slate-300 leading-relaxed">
                    Each external data provider (KRAMA, Agmarknet, Coffee Board, CDB, Negilu) features an independent <strong class="text-white">is_cron_enabled</strong> flag in <em>Admin → Data Sources</em>.
                </p>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                    If an upstream API is under maintenance or rate-limiting requests, admins can disable its automated cron without impacting other working sources or manual sync.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-cyan-400 flex items-center gap-1.5">
                    <span>⏰</span> Staggered Execution Schedule
                </div>
                <p class="text-slate-300 leading-relaxed">
                    Scheduled sync jobs are staggered to avoid outbound bandwidth spikes and CPU throttling:
                </p>
                <ul class="text-[11px] text-slate-400 space-y-1 list-disc list-inside">
                    <li><strong>KRAMA Mandis:</strong> 06:00, 12:00, 18:00 IST</li>
                    <li><strong>Coffee Board Daily:</strong> 07:00 IST</li>
                    <li><strong>CDB Coconut Center:</strong> 08:00 IST</li>
                    <li><strong>Weather & Alerts:</strong> Hourly background sync</li>
                </ul>
            </div>

            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-amber-400 flex items-center gap-1.5">
                    <span>⚡</span> Sub-100ms Perceived Latency
                </div>
                <p class="text-slate-300 leading-relaxed">
                    When farmers switch mandis on the crop view, the UI uses lightweight async API endpoints paired with shimmer/skeleton placeholders.
                </p>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                    This architecture prevents full page reloads and maintains instant responsiveness even on slow rural 3G mobile connections.
                </p>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- SECTION 6: BILINGUAL LOCALITY RESOLUTION & FALLBACK POLICY      -->
    <!-- ============================================================== -->
    <div id="bilingual-fallback" class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
        <div class="flex items-center gap-2.5 pb-3 border-b border-slate-800">
            <span class="text-xl">🌐</span>
            <div>
                <h2 class="text-base font-black text-white">Bilingual Locality Resolution & Fallback Policy</h2>
                <p class="text-xs text-slate-400">Rules for Kannada and English district/taluk translations and graceful degradation.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-white flex items-center gap-1.5">
                    <span>📖</span> Centralized Directory Mapping
                </div>
                <p class="text-slate-300 leading-relaxed">
                    Mandi and locality mappings reside in <code class="text-emerald-300 font-mono">KarnatakaMandiDirectory.php</code> with dual keys (<code class="text-slate-300 font-mono">name</code> in English and <code class="text-slate-300 font-mono">name_kn</code> in Kannada).
                </p>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                    All 31 districts, major taluks, and regulated mandis have curated native Kannada spellings to ensure cultural familiarity for Kannada-first farmers.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-2">
                <div class="font-bold text-white flex items-center gap-1.5">
                    <span>🛡️</span> Zero Blank Fallback Guarantee
                </div>
                <p class="text-slate-300 leading-relaxed">
                    If a GPS reverse-geocoded locality or new sub-mandi does not yet have a Kannada translation entry, the localization helper automatically falls back to the canonical English name.
                </p>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                    The interface never displays raw system keys, <code class="text-rose-400 font-mono">NULL</code>, or empty spaces in headers or badges. Admins can update missing translations anytime in the directory.
                </p>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- SECTION 7: ADMIN OPERATIONAL SCRATCHPAD & CUSTOM MEMOS          -->
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
