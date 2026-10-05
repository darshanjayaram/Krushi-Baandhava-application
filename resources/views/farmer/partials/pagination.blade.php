@if(isset($paginator) && method_exists($paginator, 'hasPages') && $paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $total = $paginator->total();
        $firstItem = $paginator->firstItem();
        $lastItem = $paginator->lastItem();
        $activeLocale = $activeLocale ?? app()->getLocale();
    @endphp

    <nav role="navigation" aria-label="Pagination Navigation" class="pt-4 border-t border-[#E5DECE] mt-3">
        <div class="bg-white/95 rounded-2xl border-2 border-[#E2DAC8] p-2.5 sm:p-3 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
            
            <!-- Summary Info Badge -->
            <div class="flex items-center gap-2 text-xs font-semibold text-stone-600 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#EAF4EC] text-[#1C5A2C] border border-[#B8DEC0] font-black text-[11px] shadow-2xs">
                    <span>📄</span>
                    <span>
                        @if($activeLocale === 'kn')
                            ಪುಟ {{ $currentPage }} / {{ $lastPage }}
                        @else
                            Page {{ $currentPage }} of {{ $lastPage }}
                        @endif
                    </span>
                </span>
                <span class="text-stone-500 text-[11px] font-medium hidden sm:inline">
                    @if($activeLocale === 'kn')
                        ({{ $firstItem }}-{{ $lastItem }} / ಒಟ್ಟು {{ $total }} ಬೆಳೆಗಳು)
                    @else
                        (Showing {{ $firstItem }}-{{ $lastItem }} of {{ $total }} crops)
                    @endif
                </span>
            </div>

            <!-- Page Buttons Group -->
            <div class="flex items-center gap-1.5 flex-wrap justify-center">
                
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Previous Page"
                          class="px-3 py-1.5 rounded-xl text-xs font-extrabold bg-[#FAF8F5] text-stone-300 border-2 border-stone-200 cursor-not-allowed select-none flex items-center gap-1">
                        <span>‹</span>
                        <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Prev' : 'ಹಿಂದಿನ' }}</span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" 
                       @click.prevent="goToPage('{{ $paginator->previousPageUrl() }}', {{ $currentPage - 1 }})"
                       aria-label="Previous Page"
                       class="px-3 py-1.5 rounded-xl text-xs font-black bg-white text-stone-700 border-2 border-[#D9CEB8] hover:border-[#1C5A2C] hover:text-[#1C5A2C] hover:bg-emerald-50/50 active:scale-95 transition-all shadow-2xs flex items-center gap-1 cursor-pointer">
                        <span>‹</span>
                        <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Prev' : 'ಹಿಂದಿನ' }}</span>
                    </a>
                @endif

                {{-- Page Numbers (Compact Sliding Window) --}}
                @php
                    $start = max(1, $currentPage - 1);
                    $end = min($lastPage, $currentPage + 1);
                @endphp

                @if($start > 1)
                    <a href="{{ $paginator->url(1) }}" 
                       @click.prevent="goToPage('{{ $paginator->url(1) }}', 1)"
                       class="w-8 h-8 rounded-xl text-xs font-extrabold bg-white text-stone-700 border-2 border-[#D9CEB8] hover:border-[#1C5A2C] hover:text-[#1C5A2C] hover:bg-emerald-50/50 active:scale-95 transition-all shadow-2xs flex items-center justify-center cursor-pointer">
                        1
                    </a>
                    @if($start > 2)
                        <span class="w-4 text-center text-xs font-bold text-stone-400 select-none">…</span>
                    @endif
                @endif

                @for($p = $start; $p <= $end; $p++)
                    @if($p == $currentPage)
                        <span aria-current="page"
                              class="w-8 h-8 rounded-xl text-xs font-black bg-[#1C5A2C] text-white border-2 border-[#1C5A2C] shadow-xs ring-2 ring-[#1C5A2C]/25 flex items-center justify-center select-none">
                            {{ $p }}
                        </span>
                    @else
                        <a href="{{ $paginator->url($p) }}" 
                           @click.prevent="goToPage('{{ $paginator->url($p) }}', {{ $p }})"
                           class="w-8 h-8 rounded-xl text-xs font-extrabold bg-white text-stone-700 border-2 border-[#D9CEB8] hover:border-[#1C5A2C] hover:text-[#1C5A2C] hover:bg-emerald-50/50 active:scale-95 transition-all shadow-2xs flex items-center justify-center cursor-pointer">
                            {{ $p }}
                        </a>
                    @endif
                @endfor

                @if($end < $lastPage)
                    @if($end < $lastPage - 1)
                        <span class="w-4 text-center text-xs font-bold text-stone-400 select-none">…</span>
                    @endif
                    <a href="{{ $paginator->url($lastPage) }}" 
                       @click.prevent="goToPage('{{ $paginator->url($lastPage) }}', {{ $lastPage }})"
                       class="w-8 h-8 rounded-xl text-xs font-extrabold bg-white text-stone-700 border-2 border-[#D9CEB8] hover:border-[#1C5A2C] hover:text-[#1C5A2C] hover:bg-emerald-50/50 active:scale-95 transition-all shadow-2xs flex items-center justify-center cursor-pointer">
                        {{ $lastPage }}
                    </a>
                @endif

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" 
                       @click.prevent="goToPage('{{ $paginator->nextPageUrl() }}', {{ $currentPage + 1 }})"
                       aria-label="Next Page"
                       class="px-3 py-1.5 rounded-xl text-xs font-black bg-white text-stone-700 border-2 border-[#D9CEB8] hover:border-[#1C5A2C] hover:text-[#1C5A2C] hover:bg-emerald-50/50 active:scale-95 transition-all shadow-2xs flex items-center gap-1 cursor-pointer">
                        <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Next' : 'ಮುಂದಿನ' }}</span>
                        <span>›</span>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="Next Page"
                          class="px-3 py-1.5 rounded-xl text-xs font-extrabold bg-[#FAF8F5] text-stone-300 border-2 border-stone-200 cursor-not-allowed select-none flex items-center gap-1">
                        <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Next' : 'ಮುಂದಿನ' }}</span>
                        <span>›</span>
                    </span>
                @endif

            </div>
        </div>
    </nav>
@endif
