@extends('layouts.admin', ['title' => 'Manage event'])
@section('content')<form method="POST" action="{{ route('admin.events.update',$event) }}" class="rounded-xl border bg-white p-6">@method('PUT')@include('admin.events.form',['submitLabel'=>'Save event'])</form>@endsection
