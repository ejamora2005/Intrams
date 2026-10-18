@props(['label', 'value' => '', 'side' => null, 'row' => null, 'kind' => null])
<input type="text" aria-label="{{ $label }}" value="{{ $value }}" {{ $attributes->class(['ss-input']) }} @if($side !== null) data-roster-side="{{ $side }}" @endif @if($row !== null) data-roster-row="{{ $row }}" @endif @if($kind !== null) data-roster-kind="{{ $kind }}" @endif />
