@extends('layouts.admin', ['title' => 'Add Operations Account', 'subtitle' => 'Create a GAM or Tabulator account.'])

@section('content')
    <form method="POST" action="{{ route('admin.operations-accounts.store') }}" class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @include('admin.operations-accounts.form', ['submitLabel' => 'Create account'])
    </form>
@endsection
