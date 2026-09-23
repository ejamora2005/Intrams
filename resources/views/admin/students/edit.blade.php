@extends('layouts.admin', ['title' => 'Edit student', 'subtitle' => 'Update the athlete record while keeping a secure activity history.'])

@section('content')
    <form method="POST" action="{{ route('admin.students.update', $student) }}" class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @method('PUT')
        @include('admin.students.form', ['submitLabel' => 'Save changes'])
    </form>
@endsection
