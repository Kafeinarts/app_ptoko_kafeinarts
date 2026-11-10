@props(['name'])
<span class="avatar-circle" {{ $attributes }}>{{ strtoupper(substr($name, 0, 1)) }}</span>
