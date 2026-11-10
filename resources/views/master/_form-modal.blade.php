<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered {{ count($fields) > 4 ? 'modal-lg' : '' }}">
        <form method="POST" action="{{ $action }}" class="modal-content border-0">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif
            <div class="modal-header">
                <h5 class="modal-title">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    @foreach($fields as $field)
                        @php
                            $name = $field['name'];
                            $type = $field['type'] ?? 'text';
                            $value = old($name, data_get($record, $name) ?? ($field['default'] ?? null));
                            $isWide = in_array($type, ['textarea'], true);
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
                                @elseif($type === 'textarea')
                                    <textarea name="{{ $name }}" id="{{ $id }}-{{ $name }}" class="form-control" rows="3" @required($field['required'] ?? false)>{{ $value }}</textarea>
                                @else
                                    <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}-{{ $name }}" class="form-control" value="{{ $value }}" @required($field['required'] ?? false) @if($type === 'number') step="any" min="0" @endif>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-success"><i class="bi bi-check2 me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>
