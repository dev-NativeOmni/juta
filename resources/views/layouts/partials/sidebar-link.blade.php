{{-- Satu item menu sidebar. $item berasal dari App\Support\SidebarMenu::for(). --}}
<a href="{{ $item['url'] }}"
   @if ($item['active']) aria-current="page" @endif
   class="{{ $item['active']
        ? 'flex items-center px-3 py-2 text-sm font-bold rounded-xl bg-gradient-to-r from-teal-500/15 to-emerald-500/10 text-teal-800 dark:text-teal-300 border border-teal-500/30 dark:border-teal-500/20 group transition-all duration-200 shadow-sm shadow-teal-500/10'
        : 'flex items-center px-3 py-2 text-sm font-medium rounded-xl text-zinc-600 dark:text-zinc-400 hover:bg-white/60 dark:hover:bg-white/5 hover:text-zinc-900 dark:hover:text-white border border-transparent hover:border-zinc-200/60 dark:hover:border-white/5 group transition-all duration-150' }}">
    <svg class="{{ $item['active']
            ? 'mr-3 h-5 w-5 text-teal-600 dark:text-teal-400 flex-shrink-0 transition-colors duration-150'
            : 'mr-3 h-5 w-5 text-zinc-400 dark:text-zinc-500 group-hover:text-teal-600 dark:group-hover:text-teal-400 flex-shrink-0 transition-colors duration-150' }}"
         fill="none" viewBox="0 0 24 24" stroke="currentColor">
        @foreach (\App\Support\SidebarMenu::icon($item['icon']) as $d)
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}" />
        @endforeach
    </svg>
    @if ($item['badge'] > 0)
        <span class="flex-1 flex justify-between items-center">
            <span>{{ $item['label'] }}</span>
            <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
            </span>
        </span>
    @else
        <span>{{ $item['label'] }}</span>
    @endif
</a>
