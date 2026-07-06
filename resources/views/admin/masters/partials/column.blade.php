@php
    $relationColumnsConfig = $relationColumnsConfig ?? 'masters.relation_columns';
    $relationMeta = config("{$relationColumnsConfig}.{$column}");
@endphp

@if($column === 'code')
    <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded-sm">{{ $record->code }}</code>
@elseif($column === 'is_active')
    @if($record->is_active)
        <span class="text-xs font-semibold px-2 py-0.5 rounded-sm bg-green-50 text-green-700">Active</span>
    @else
        <span class="text-xs font-semibold px-2 py-0.5 rounded-sm bg-gray-100 text-gray-500">Inactive</span>
    @endif
@elseif($column === 'image' && $record->image)
    <img src="{{ $record->imageUrl() }}" alt="" class="w-10 h-10 object-cover rounded-sm border border-gray-200">
@elseif($column === 'image')
    <span class="text-xs text-gray-300">—</span>
@elseif($column === 'hex_code' && $record->hex_code)
    <span class="inline-flex items-center gap-2">
        <span class="w-4 h-4 rounded-sm border border-gray-200" style="background-color: {{ $record->hex_code }}"></span>
        <span class="text-gray-600">{{ $record->hex_code }}</span>
    </span>
@elseif($relationMeta)
    @php
        $rel = $relationMeta['relation'];
        $display = $relationMeta['display'];
        $relRecord = $record->{$rel};
        $relLabel = $relRecord
            ? \App\Support\RelationDisplay::label($relRecord, $display, $relationMeta['display_with'] ?? null)
            : ($column === 'department_id' && $record instanceof \App\Models\Designation ? 'Shared (all units)' : '—');
    @endphp
    <span class="text-gray-700">{{ $relLabel !== '' ? $relLabel : '—' }}</span>
@elseif($column === 'description' && $record->description)
    <span class="text-gray-600 text-xs line-clamp-2 max-w-xs">{{ $record->description }}</span>
@elseif($column === 'is_night' || $column === 'is_paid' || $column === 'is_optional')
    @if($record->{$column})
        <span class="text-xs font-semibold px-2 py-0.5 rounded-sm bg-blue-50 text-blue-700">Yes</span>
    @else
        <span class="text-xs text-gray-400">No</span>
    @endif
@elseif($column === 'date' && $record->{$column})
    <span class="text-gray-600">{{ $record->{$column}->format('d M Y') }}</span>
@elseif(in_array($column, ['start_time', 'end_time', 'break_start_time', 'break_end_time', 'out_time', 'expected_in_time'], true) && $record->{$column})
    <span class="text-gray-600">{{ \App\Support\TimeInput::formatForDisplay($record->{$column}) }}</span>
@elseif(str_ends_with($column, '_time') && $record->{$column})
    <span class="text-gray-600">{{ \App\Support\TimeInput::formatForDisplay($record->{$column}) }}</span>
@elseif(in_array($column, ['calendar_type', 'branch', 'account_number', 'swift_code'], true) && $record->{$column})
    <span class="text-gray-600">{{ $record->{$column} }}</span>
@else
    <span class="text-gray-700">{{ $record->{$column} ?? '—' }}</span>
@endif
