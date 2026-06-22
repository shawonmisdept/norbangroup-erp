@php
    $relationMeta = config("masters.relation_columns.{$column}");
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
    @endphp
    <span class="text-gray-700">{{ $record->{$rel}?->{$display} ?? '—' }}</span>
@elseif($column === 'description' && $record->description)
    <span class="text-gray-600 text-xs line-clamp-2 max-w-xs">{{ $record->description }}</span>
@elseif(in_array($column, ['start_date', 'end_date'], true) && $record->{$column})
    <span class="text-gray-600">{{ $record->{$column}->format('d M Y') }}</span>
@elseif(in_array($column, ['calendar_type', 'branch', 'account_number', 'swift_code'], true) && $record->{$column})
    <span class="text-gray-600">{{ $record->{$column} }}</span>
@else
    <span class="text-gray-700">{{ $record->{$column} ?? '—' }}</span>
@endif
