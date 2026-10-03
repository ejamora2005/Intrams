@extends('layouts.admin', ['title' => 'Manage Operations Account', 'subtitle' => 'Update GAM or Tabulator access.'])

@section('content')
    @if (session('success'))<div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>@endif

    <form method="POST" action="{{ route('admin.operations-accounts.update', $account) }}" class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @method('PUT')
        @include('admin.operations-accounts.form', ['submitLabel' => 'Save account'])
    </form>
@endsection
