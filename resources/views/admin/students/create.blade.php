@extends('layouts.admin', ['title' => 'Add student', 'subtitle' => 'Create an athlete record before assigning them to an event.'])

@section('content')
    <form method="POST" action="{{ route('admin.students.store') }}" class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @include('admin.students.form', ['submitLabel' => 'Save student'])
    </form>
@endsection
