{{-- Tab Target Bulanan / Target Triwulan (keduanya dihitung otomatis dari pertemuan aktif). --}}
<div class="flex items-center gap-1 border-b border-gray-200 dark:border-zinc-800">
    @foreach (['hafalan-targets.index' => 'Target Bulanan', 'hafalan-targets.term' => 'Target Triwulan'] as $routeName => $label)
        <a href="{{ route($routeName) }}"
           class="px-4 py-2.5 text-sm font-bold border-b-2 transition {{ request()->routeIs($routeName) ? 'border-indigo-600 text-indigo-700 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-zinc-300' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
