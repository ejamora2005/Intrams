@props(['value'])

@if ($value)
    @php($localTime = $value->copy()->setTimezone('Asia/Manila'))
    <time datetime="{{ $localTime->toIso8601String() }}" title="Philippine time (UTC+8)">{{ $localTime->format('M j, Y g:i:s A') }} PHT (UTC+8)</time>
@else
    <span>Time unavailable</span>
@endif
