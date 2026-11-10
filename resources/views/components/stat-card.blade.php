@props([
    'label',
    'value',
    'hint' => null,
    'valueClass' => '',
])
<div class="stat-card h-100">
    <div class="stat-label">{{ $label }}</div>
    <div class="stat-value {{ $valueClass }}">{{ $value }}</div>
    @if($hint)
        <div class="stat-hint">{{ $hint }}</div>
    @endif
</div>
