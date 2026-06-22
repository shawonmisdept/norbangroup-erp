@if($meta['type'] === 'relation')
    <label class="erp-form-label">
        {{ $meta['label'] }}
        @if($meta['required'] ?? false)<span class="text-red-400">*</span>@endif
    </label>
    <select name="{{ $field }}" {{ ($meta['required'] ?? false) ? 'required' : '' }} class="erp-input">
        @if($meta['nullable'] ?? false)
            <option value="">— Select —</option>
        @endif
        @foreach($relations[$field] ?? [] as $id => $label)
            <option value="{{ $id }}"
                {{ (string) old($field, isset($record) ? $record->{$field} : '') === (string) $id ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error($field)<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror

@elseif($meta['type'] === 'textarea')
    <label class="erp-form-label">
        {{ $meta['label'] }}
        @if($meta['required'] ?? false)<span class="text-red-400">*</span>@endif
    </label>
    <textarea name="{{ $field }}" rows="3" class="erp-input"
              placeholder="{{ $meta['placeholder'] ?? '' }}"
              {{ ($meta['required'] ?? false) ? 'required' : '' }}>{{ old($field, isset($record) ? $record->{$field} : '') }}</textarea>
    @error($field)<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror

@elseif($meta['type'] === 'select')
    <label class="erp-form-label">
        {{ $meta['label'] }}
        @if($meta['required'] ?? false)<span class="text-red-400">*</span>@endif
    </label>
    <select name="{{ $field }}" {{ ($meta['required'] ?? false) ? 'required' : '' }} class="erp-input">
        @if($meta['nullable'] ?? false)
            <option value="">— Select —</option>
        @endif
        @foreach($meta['options'] ?? [] as $option)
            <option value="{{ $option }}"
                {{ old($field, isset($record) ? $record->{$field} : '') === $option ? 'selected' : '' }}>
                {{ $option }}
            </option>
        @endforeach
    </select>
    @error($field)<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror

@elseif($meta['type'] === 'date')
    <label class="erp-form-label">{{ $meta['label'] }}</label>
    <input type="date" name="{{ $field }}" class="erp-input"
           value="{{ old($field, isset($record) && $record->{$field} ? $record->{$field}->format('Y-m-d') : '') }}">
    @error($field)<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror

@elseif($meta['type'] === 'number')
    <label class="erp-form-label">
        {{ $meta['label'] }}
        @if($meta['required'] ?? false)<span class="text-red-400">*</span>@endif
    </label>
    <input type="number" name="{{ $field }}" class="erp-input"
           value="{{ old($field, isset($record) ? $record->{$field} : ($meta['default'] ?? '')) }}"
           {{ ($meta['required'] ?? false) ? 'required' : '' }}
           @if(isset($meta['min'])) min="{{ $meta['min'] }}" @endif
           placeholder="{{ $meta['placeholder'] ?? '' }}">
    @error($field)<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror

@else
    <label class="erp-form-label">
        {{ $meta['label'] }}
        @if($meta['required'] ?? false)<span class="text-red-400">*</span>@endif
    </label>
    <input type="{{ $meta['type'] === 'email' ? 'email' : 'text' }}" name="{{ $field }}" class="erp-input"
           value="{{ old($field, isset($record) ? $record->{$field} : '') }}"
           placeholder="{{ $meta['placeholder'] ?? '' }}"
           {{ ($meta['required'] ?? false) ? 'required' : '' }}>
    @error($field)<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
@endif
