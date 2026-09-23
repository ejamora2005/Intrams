@extends('layouts.admin', ['title' => 'Add event'])
@section('content')<form method="POST" action="{{ route('admin.events.store') }}" class="rounded-xl border bg-white p-6">@include('admin.events.form',['submitLabel'=>'Create draft event'])</form>@endsection
