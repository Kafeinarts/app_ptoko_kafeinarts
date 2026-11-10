@props(['icon' => 'bi bi-inbox', 'message' => null])
<div class="empty-state {{ $attributes->get('class') }}">
    <i class="{{ $icon }}"></i>
    @if($slot->isNotEmpty())
        {{ $slot }}
    @else
        {{ $message }}
    @endif
</div>
