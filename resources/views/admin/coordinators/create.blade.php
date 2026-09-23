@extends('layouts.admin', ['title' => 'Add coordinator', 'subtitle' => 'Create a staff account for event coordination.'])
@section('content')<form method="POST" action="{{ route('admin.coordinators.store') }}" class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">@include('admin.coordinators.form', ['submitLabel' => 'Create coordinator'])</form>@endsection
