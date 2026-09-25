@props([
    'name',
    'options',
    'current' => null,
    'colors' => [],
])

@php
    $current = (string) ($current ?? request($name, ''));
    $defaultColor = 'bg-indigo-600 text-white shadow-sm';
    $buildUrl = function ($value) use ($name) {
        $params = request()->except([$name, 'page']);
        if ($value !== '' && $value !== null) {
            $params[$name] = $value;
        }

        return request()->url().'?'.http_build_query($params);
    };
@endphp

{{-- Tablet & desktop: tombol pill sekali klik --}}
<div {{ $attributes->merge(['class' => 'hidden sm:flex flex-wrap gap-1.5']) }}>
    @foreach ($options as $value => $label)
        <a
            href="{{ $buildUrl($value) }}"
            class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition {{ $current === (string) $value ? ($colors[$value] ?? $defaultColor) : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}"
        >
            {{ $label }}
        </a>
    @endforeach
</div>

{{-- Handphone: dropdown --}}
<select
    onchange="window.location.href=this.value"
    {{ $attributes->merge(['class' => 'sm:hidden w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-xs text-zinc-900 dark:text-zinc-100 shadow-sm']) }}
>
    @foreach ($options as $value => $label)
        <option value="{{ $buildUrl($value) }}" @selected($current === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
