@extends('layouts.admin', ['title' => 'Add sport'])
@section('content')<form method="POST" action="{{ route('admin.sports.store') }}" class="max-w-3xl rounded-xl border bg-white p-6"><input type="hidden" name="edition_id" value="{{ $edition->id }}">@include('admin.sports.form',['submitLabel'=>'Save sport'])</form>@endsection
