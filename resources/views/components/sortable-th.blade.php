@props([
    'column',           // sort key, e.g. "project"
    'label',            // header text
    'sort',             // current active sort key
    'direction',        // current direction: asc|desc
    'align' => 'left',  // left|right
])

@php
    $active   = $sort === $column;
    $nextDir  = $active && $direction === 'asc' ? 'desc' : 'asc';
    $arrow    = $active ? ($direction === 'asc' ? '▲' : '▼') : '';
    $url      = request()->fullUrlWithQuery(['sort' => $column, 'direction' => $nextDir, 'page' => 1]) . '#logs';
@endphp

<th class="px-6 py-3 text-{{ $align }} text-xs font-medium text-gray-500 uppercase tracking-wider">
    <a href="{{ $url }}" class="inline-flex items-center gap-1 hover:text-gray-700 {{ $active ? 'text-gray-900' : '' }}">
        {{ $label }}
        <span class="text-[10px]">{{ $arrow }}</span>
    </a>
</th>
