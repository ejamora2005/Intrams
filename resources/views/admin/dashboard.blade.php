@extends('layouts.admin')

@section('content')
    <div class="ops-hero p-5 sm:p-7 lg:p-8">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div class="min-w-0">
                <p class="ops-eyebrow">Intramurals command center</p>
                <h1 class="ops-display mt-2">Admin dashboard</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Use the sidebar to manage the people, events, scoring rules, and system activity for your intramurals program.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('admin.rules.index') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-800 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 sm:w-fit">Rules & Guidelines</a>
                <a href="{{ route('admin.editions.index') }}" class="inline-flex w-full items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 sm:w-fit">Manage editions</a>
            </div>
        </div>
    </div>

    <section class="mt-8" aria-labelledby="overview-heading">
        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
            <div>
                <p class="ops-eyebrow">Live modules</p>
                <h2 id="overview-heading" class="text-base font-semibold text-slate-900">Program overview</h2>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-3 min-[480px]:grid-cols-2 sm:gap-4 xl:grid-cols-4">
            @foreach ($metrics as $metric)
                <a href="{{ route($metric['route']) }}" class="ops-stat-card group min-w-0 rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md sm:p-5">
                    <p class="text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                    <p class="mt-3 break-words text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">{{ number_format($metric['value']) }}</p>
                    <p class="mt-4 text-sm font-medium text-blue-700 group-hover:text-blue-800">Open module</p>
                </a>
            @endforeach
        </div>
    </section>

    <section class="mt-6 rounded-lg border border-blue-100 bg-blue-50 p-4 sm:mt-8 sm:p-7" aria-labelledby="start-heading">
        <h2 id="start-heading" class="text-lg font-semibold text-blue-950">Start managing your intramurals program</h2>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-blue-900/75">Create an edition, configure its sports, form teams, then add athlete entries before scheduling live competition.</p>
        <div class="mt-5 grid gap-3 min-[420px]:flex min-[420px]:flex-wrap">
            <a href="{{ route('admin.students.index') }}" class="rounded-lg bg-blue-700 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-blue-800">Students</a>
            <a href="{{ route('admin.editions.index') }}" class="rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-center text-sm font-semibold text-blue-800 transition hover:border-blue-300 hover:bg-blue-50">Events / Editions</a>
            <a href="{{ route('admin.rules.index') }}" class="rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-center text-sm font-semibold text-blue-800 transition hover:border-blue-300 hover:bg-blue-50">Rules & Guidelines</a>
        </div>
    </section>
@endsection
