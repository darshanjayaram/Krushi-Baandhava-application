@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="w-full">
        
        {{-- ============================================================== --}}
        {{-- 1. MOBILE VIEW (sm:hidden) — STRICT SINGLE HORIZONTAL ROW      --}}
        {{-- Never wraps into two lines, even with thousands of pages       --}}
        {{-- ============================================================== --}}
        <div class="sm:hidden flex flex-col gap-2.5 w-full">
            <!-- Mobile Results Counter & Current Page Summary -->
            <div class="flex items-center justify-between text-[11px] text-slate-400 font-medium px-0.5">
                <div>
                    {!! __('Showing') !!}
                    @if ($paginator->firstItem())
                        <span class="font-bold text-white">{{ number_format($paginator->firstItem()) }}</span>–<span class="font-bold text-white">{{ number_format($paginator->lastItem()) }}</span>
                    @else
                        <span class="font-bold text-white">{{ number_format($paginator->count()) }}</span>
                    @endif
                    {!! __('of') !!}
                    <span class="font-bold text-emerald-400">{{ number_format($paginator->total()) }}</span>
                </div>
                <div class="text-slate-400">
                    Page <span class="font-bold text-white">{{ number_format($paginator->currentPage()) }}</span> / {{ number_format($paginator->lastPage()) }}
                </div>
            </div>

            <!-- Mobile Controls Bar: STRICT flex-nowrap to guarantee ONE single line -->
            <div class="flex items-center justify-between gap-1 w-full flex-nowrap overflow-x-auto no-scrollbar py-0.5">
                {{-- Mobile Previous Button --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-slate-500 bg-slate-950/40 border border-slate-800/60 rounded-xl cursor-not-allowed opacity-50 select-none shrink-0 whitespace-nowrap">
                        <span>‹</span>
                        <span>Prev</span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-slate-200 bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-xl transition shadow-xs shrink-0 whitespace-nowrap">
                        <span>‹</span>
                        <span>Prev</span>
                    </a>
                @endif

                {{-- Mobile Number Pills (Max 5 items, always fits on one row) --}}
                @php
                    $current = $paginator->currentPage();
                    $last = $paginator->lastPage();
                    $mobileItems = [];
                    if ($last <= 5) {
                        for ($p = 1; $p <= $last; $p++) {
                            $mobileItems[] = ['type' => 'page', 'page' => $p, 'url' => $paginator->url($p), 'active' => ($p === $current)];
                        }
                    } elseif ($current <= 3) {
                        for ($p = 1; $p <= 3; $p++) {
                            $mobileItems[] = ['type' => 'page', 'page' => $p, 'url' => $paginator->url($p), 'active' => ($p === $current)];
                        }
                        $mobileItems[] = ['type' => 'dots'];
                        $mobileItems[] = ['type' => 'page', 'page' => $last, 'url' => $paginator->url($last), 'active' => false];
                    } elseif ($current >= $last - 2) {
                        $mobileItems[] = ['type' => 'page', 'page' => 1, 'url' => $paginator->url(1), 'active' => false];
                        $mobileItems[] = ['type' => 'dots'];
                        for ($p = $last - 2; $p <= $last; $p++) {
                            $mobileItems[] = ['type' => 'page', 'page' => $p, 'url' => $paginator->url($p), 'active' => ($p === $current)];
                        }
                    } else {
                        $mobileItems[] = ['type' => 'page', 'page' => 1, 'url' => $paginator->url(1), 'active' => false];
                        $mobileItems[] = ['type' => 'dots'];
                        $mobileItems[] = ['type' => 'page', 'page' => $current, 'url' => $paginator->url($current), 'active' => true];
                        $mobileItems[] = ['type' => 'dots'];
                        $mobileItems[] = ['type' => 'page', 'page' => $last, 'url' => $paginator->url($last), 'active' => false];
                    }
                @endphp

                <div class="flex items-center justify-center gap-1 shrink-0 flex-nowrap">
                    @foreach ($mobileItems as $item)
                        @if ($item['type'] === 'dots')
                            <span aria-disabled="true" class="px-1 py-1 text-xs font-bold text-slate-500 select-none shrink-0">…</span>
                        @elseif ($item['active'])
                            <span aria-current="page" class="inline-flex items-center justify-center min-w-[30px] h-7 px-2 text-xs font-black text-white bg-emerald-600 border border-emerald-500 rounded-xl shadow-md ring-2 ring-emerald-500/25 select-none shrink-0 whitespace-nowrap">
                                {{ $item['page'] }}
                            </span>
                        @else
                            <a href="{{ $item['url'] }}" aria-label="{{ __('Go to page :page', ['page' => $item['page']]) }}" class="inline-flex items-center justify-center min-w-[30px] h-7 px-2 text-xs font-bold text-slate-300 bg-slate-900/90 hover:bg-slate-800 hover:text-white border border-slate-800 rounded-xl transition shadow-xs shrink-0 whitespace-nowrap">
                                {{ $item['page'] }}
                            </a>
                        @endif
                    @endforeach
                </div>

                {{-- Mobile Next Button --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-slate-200 bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-xl transition shadow-xs shrink-0 whitespace-nowrap">
                        <span>Next</span>
                        <span>›</span>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="{{ __('pagination.next') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-slate-500 bg-slate-950/40 border border-slate-800/60 rounded-xl cursor-not-allowed opacity-50 select-none shrink-0 whitespace-nowrap">
                        <span>Next</span>
                        <span>›</span>
                    </span>
                @endif
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- 2. DESKTOP VIEW (hidden sm:flex) — FULL FEATURED PAGINATION     --}}
        {{-- ============================================================== --}}
        <div class="hidden sm:flex sm:items-center sm:justify-between gap-4 text-xs w-full">
            <!-- Results Counter -->
            <div class="text-slate-400 font-medium shrink-0">
                {!! __('Showing') !!}
                @if ($paginator->firstItem())
                    <span class="font-bold text-white">{{ number_format($paginator->firstItem()) }}</span>
                    {!! __('to') !!}
                    <span class="font-bold text-white">{{ number_format($paginator->lastItem()) }}</span>
                @else
                    <span class="font-bold text-white">{{ number_format($paginator->count()) }}</span>
                @endif
                {!! __('of') !!}
                <span class="font-bold text-emerald-400">{{ number_format($paginator->total()) }}</span>
                {!! __('results') !!}
            </div>

            <!-- Desktop Pagination Controls with Numbers -->
            <div class="flex items-center justify-end gap-1 flex-wrap shrink-0">
                {{-- Desktop Previous Page Button --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-slate-500 bg-slate-950/40 border border-slate-800/60 rounded-xl cursor-not-allowed opacity-50 select-none">
                        <span>‹</span>
                        <span>Prev</span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-slate-200 bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-xl transition shadow-xs">
                        <span>‹</span>
                        <span>Prev</span>
                    </a>
                @endif

                {{-- Desktop Numbered Page Links --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span aria-disabled="true" class="px-2 py-1.5 text-xs font-bold text-slate-500 select-none">{{ $element }}</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex items-center justify-center min-w-[32px] px-2.5 py-1.5 text-xs font-black text-white bg-emerald-600 border border-emerald-500 rounded-xl shadow-md ring-2 ring-emerald-500/25 select-none">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}" class="inline-flex items-center justify-center min-w-[32px] px-2.5 py-1.5 text-xs font-bold text-slate-300 bg-slate-900/90 hover:bg-slate-800 hover:text-white border border-slate-800 rounded-xl transition shadow-xs">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Desktop Next Page Button --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-slate-200 bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-xl transition shadow-xs">
                        <span>Next</span>
                        <span>›</span>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="{{ __('pagination.next') }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-slate-500 bg-slate-950/40 border border-slate-800/60 rounded-xl cursor-not-allowed opacity-50 select-none">
                        <span>Next</span>
                        <span>›</span>
                    </span>
                @endif
            </div>
        </div>

    </nav>
@endif
