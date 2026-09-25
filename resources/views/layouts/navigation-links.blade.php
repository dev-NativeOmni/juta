{{-- Isi menu didefinisikan di App\Support\SidebarMenu; file ini hanya merender. --}}
@php
    $menuGroups = \App\Support\SidebarMenu::for(auth()->user());
@endphp

@foreach ($menuGroups as $group)
    @if (! $group['collapsible'])
        {{-- Grup tetap (Utama) atau grup dengan satu item: tampil tanpa lipatan. --}}
        <div class="{{ $loop->first ? '' : 'mt-2' }} space-y-1">
            @foreach ($group['items'] as $item)
                @include('layouts.partials.sidebar-link', ['item' => $item])
            @endforeach
        </div>
    @else
        {{-- Grup aktif selalu terbuka; grup lain tertutup kecuali user pernah membukanya. --}}
        <div class="mt-2"
             x-data="{
                 open: @js($group['active']),
                 key: 'sidebar-group-{{ $group['key'] }}',
                 init() {
                     if (this.open) return;
                     try { this.open = localStorage.getItem(this.key) === '1'; } catch (e) {}
                 },
                 toggle() {
                     this.open = ! this.open;
                     try { localStorage.setItem(this.key, this.open ? '1' : '0'); } catch (e) {}
                 },
             }">
            <button type="button" @click="toggle()" :aria-expanded="open.toString()"
                    class="w-full flex items-center px-3 py-2 text-xs font-semibold uppercase tracking-wider rounded-xl cursor-pointer transition-colors duration-150 {{ $group['active'] ? 'text-teal-700 dark:text-teal-400' : 'text-gray-400 hover:text-zinc-700 dark:hover:text-zinc-200 hover:bg-white/60 dark:hover:bg-white/5' }}">
                @if ($group['icon'])
                    <svg class="mr-3 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        @foreach (\App\Support\SidebarMenu::icon($group['icon']) as $d)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}" />
                        @endforeach
                    </svg>
                @endif
                <span class="flex-1 text-left">{{ $group['label'] }}</span>
                <svg class="h-4 w-4 flex-shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mt-1 space-y-1 pl-2"
                 @unless ($group['active']) style="display: none;" @endunless>
                @foreach ($group['items'] as $item)
                    @include('layouts.partials.sidebar-link', ['item' => $item])
                @endforeach
            </div>
        </div>
    @endif
@endforeach
