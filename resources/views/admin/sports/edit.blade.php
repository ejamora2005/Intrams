@extends('layouts.admin', ['title' => 'Edit sport'])
@section('content')<form method="POST" action="{{ route('admin.sports.update',$sport) }}" class="max-w-3xl rounded-xl border bg-white p-6">@method('PUT')<input type="hidden" name="edition_id" value="{{ $edition?->id }}">@include('admin.sports.form',['submitLabel'=>'Save changes'])</form>@endsection
