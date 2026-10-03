@props(['objects' => []])

{{-- Supporting photography only. The separate team image/placeholder stays in front. --}}
<div class="department-objects" aria-hidden="true">
    @foreach ($objects as $object)
        @if (! empty($object['image']) && is_file(public_path($object['image'])))
            <img class="department-object department-object--{{ $object['placement'] }}" src="{{ asset($object['image']) }}" alt="" decoding="async" draggable="false">
        @endif
    @endforeach
</div>
