{{-- Shared field renderer for master forms (used by modal & insert page) --}}
@php
    $id = $id ?? 'form';
    $record = $record ?? null;
@endphp
<div class="row g-3">
    @foreach($fields as $field)
        @php
            $name = $field['name'];
            $type = $field['type'] ?? 'text';
            $value = old($name, data_get($record, $name) ?? ($field['default'] ?? null));
            $isWide = in_array($type, ['textarea', 'select-multiple'], true);
        @endphp
        <div class="{{ $isWide ? 'col-12' : 'col-md-6' }}">
            @if($type === 'checkbox')
                <div class="form-check form-switch mt-md-4 pt-md-2">
                    <input type="hidden" name="{{ $name }}" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="{{ $id }}-{{ $name }}" name="{{ $name }}" value="1" @checked((bool) $value)>
                    <label class="form-check-label" for="{{ $id }}-{{ $name }}">{{ $field['label'] }}</label>
                </div>
            @else
                <label class="form-label" for="{{ $id }}-{{ $name }}">
                    {{ $field['label'] }}
                    @if($field['required'] ?? false)<span class="text-danger">*</span>@endif
                </label>
                @if($type === 'select')
                    <select name="{{ $name }}" id="{{ $id }}-{{ $name }}" class="form-select" @required($field['required'] ?? false)>
                        <option value="">Pilih {{ strtolower($field['label']) }}</option>
                        @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                        @endforeach
                    </select>
                @elseif($type === 'select-multiple')
                    <select name="{{ $name }}[]" id="{{ $id }}-{{ $name }}" class="form-select" multiple size="{{ $field['size'] ?? 8 }}">
                        @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                            <option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, array_map('strval', (array) ($value ?? [])), true))>{{ $optionLabel }}</option>
                        @endforeach
                    </select>
                    @if($field['hint'] ?? false)
                        <div class="form-text">{{ $field['hint'] }}</div>
                    @endif
                @elseif($type === 'textarea')
                    <textarea name="{{ $name }}" id="{{ $id }}-{{ $name }}" class="form-control" rows="3" @required($field['required'] ?? false)>{{ $value }}</textarea>
                @else
                    <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}-{{ $name }}" class="form-control" value="{{ $value }}" @required($field['required'] ?? false) @if($type === 'number') step="any" min="0" @endif>
                @endif
            @endif
        </div>
    @endforeach
</div>
