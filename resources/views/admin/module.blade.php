@extends('layouts.admin', ['title' => $title, 'subtitle' => $description])

@section('content')
    <section class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h2 class="text-lg font-semibold text-slate-900">{{ $title }} module</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p>
        <div class="mt-6 border-t border-slate-100 pt-6">
            <p class="text-sm text-slate-500">This module shell and navigation are ready. Its management forms and workflows will be delivered in the next implementation steps.</p>
        </div>
    </section>
@endsection
