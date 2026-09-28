@extends('layouts.admin', [
    'title' => 'Volleyball score sheet',
    'subtitle' => 'Reference score sheet for '.$edition->name.' — '.$sport->name,
])

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <a href="{{ route('admin.sports.index', ['edition_id' => $edition->id]) }}" class="w-fit text-sm font-semibold text-blue-700 hover:text-blue-900">← Back to Sports</a>
        <div class="flex gap-2">
            <button type="reset" form="volleyball-score-sheet" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear form</button>
            <button type="button" id="open-score-sheet-download" aria-haspopup="dialog" aria-controls="score-sheet-download-drawer" class="w-fit rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Download</button>
        </div>
    </div>

    <form id="volleyball-score-sheet" data-score-sheet-export data-volleyball-score-sheet data-download-prefix="volleyball-score-sheet" method="POST" action="{{ route('admin.sports.volleyball-score-sheet.download', $sport) }}">
        @csrf
        <input type="hidden" name="edition_id" value="{{ $edition->id }}" />

        <div class="mx-auto mb-4 grid max-w-4xl gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4 sm:grid-cols-2">
            @foreach (['home' => 'Home', 'visitor' => 'Visitor'] as $side => $label)
                <div>
                    <label class="text-sm font-semibold text-slate-700">Prefill {{ $label }} Team
                        <select data-volleyball-team-picker="{{ $side }}" class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select team</option>
                            @foreach ($teams as $team)<option value="{{ $team['id'] }}">{{ $team['name'] }}</option>@endforeach
                        </select>
                    </label>
                    <p data-volleyball-roster="{{ $side }}" class="mt-2 min-h-10 text-xs leading-5 text-slate-600">Select a team to load its active roster.</p>
                </div>
            @endforeach
        </div>

        <p class="mb-2 text-center text-xs text-slate-500">A4 landscape · 297 × 210 mm</p>
        <div class="overflow-x-auto rounded-lg bg-slate-200 p-4 sm:p-6">
            <div class="score-sheet-paper" aria-label="A4 landscape paper preview">
                <div class="volleyball-sheet-art">
                    <img src="{{ asset('images/volleyball-score-sheet-reference.png') }}" width="1584" height="1224" decoding="async" fetchpriority="high" alt="Volleyball score sheet reference layout" />
                    <input name="home_team" data-volleyball-team-name="home" class="volleyball-team-field volleyball-home-team" aria-label="Home team" />
                    <input name="visitor_team" data-volleyball-team-name="visitor" class="volleyball-team-field volleyball-visitor-team" aria-label="Visitor team" />
                    <span class="volleyball-player-heading volleyball-home-player-heading">Player<br>Name</span>
                    <span class="volleyball-player-heading volleyball-visitor-player-heading">Player<br>Name</span>
                    @foreach (['home', 'visitor'] as $side)
                        @foreach (range(1, 6) as $position)
                            <input name="{{ $side }}_player_name_{{ $position }}" data-volleyball-player-name="{{ $side }}" class="volleyball-player-field volleyball-{{ $side }}-player-{{ $position }}" aria-label="{{ ucfirst($side) }} player name {{ $position }}" />
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </form>

    <dialog id="score-sheet-download-drawer" aria-labelledby="download-drawer-title" class="fixed inset-y-0 left-auto right-0 m-0 h-screen max-h-none w-full max-w-sm border-l border-slate-200 bg-white p-6 shadow-xl backdrop:bg-slate-900/40">
        <div class="flex items-center justify-between gap-3">
            <h2 id="download-drawer-title" class="text-lg font-semibold">Download score sheet</h2>
            <button type="button" id="close-score-sheet-download" aria-label="Close download options" class="rounded p-2 text-slate-600 hover:bg-slate-100">✕</button>
        </div>
        <p class="mt-3 text-sm text-slate-600">Choose a file format for the A4 landscape sheet.</p>
        <div class="mt-6 space-y-3">
            @foreach (['docx' => ['Word (.docx)', 'A4 landscape image preserving the sheet layout'], 'xlsx' => ['Excel (.xlsx)', 'A4 landscape image preserving the sheet layout'], 'pdf' => ['PDF (.pdf)', 'A4 landscape page matching the preview']] as $format => [$label, $description])
                <button type="submit" form="volleyball-score-sheet" name="format" value="{{ $format }}" class="block w-full rounded-lg border border-blue-200 p-4 text-left hover:bg-blue-50 disabled:opacity-50"><span class="block font-semibold text-blue-800">{{ $label }}</span><span class="mt-1 block text-sm text-slate-600">{{ $description }}</span></button>
            @endforeach
        </div>
        <p id="score-sheet-export-status" role="status" class="mt-4 text-sm text-slate-700"></p>
    </dialog>
    <noscript>JavaScript is required to download the score sheet.</noscript>
    <script type="application/json" id="volleyball-teams-data">{!! $teams->toJson(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <style>
        .score-sheet-paper { box-sizing: border-box; position: relative; width: 297mm; height: 210mm; margin: 0 auto; background: #fff; overflow: hidden; box-shadow: 0 3px 16px #0002; }
        .volleyball-sheet-art { position: relative; width: 271.765mm; height: 210mm; margin: 0 auto; }
        .volleyball-sheet-art img { display: block; width: 100%; height: 100%; }
        .volleyball-team-field, .volleyball-player-field { box-sizing: border-box; position: absolute; z-index: 1; border: 0; background: transparent; color: #000; font-family: Arial, sans-serif; outline: none; }
        .volleyball-team-field { top: 9.45%; height: 2.75%; padding: 0 .5mm; font-size: 3.2mm; line-height: 1; }
        .volleyball-home-team { left: 20.65%; width: 28.2%; }
        .volleyball-visitor-team { left: 53.85%; width: 26.6%; }
        .volleyball-player-heading { box-sizing: border-box; position: absolute; top: 17.9%; z-index: 1; width: 6.05%; height: 3.75%; padding-top: .35mm; background: #fff; color: #000; text-align: left; font-family: Arial, sans-serif; font-size: 2.8mm; line-height: 1.05; }
        .volleyball-home-player-heading { left: 7.55%; }
        .volleyball-visitor-player-heading { left: 57.5%; }
        .volleyball-player-field { width: 6.1%; height: 5.15%; padding: 0 .2mm; text-align: center; font-size: 1.85mm; font-weight: 600; line-height: 1; }
        .volleyball-home-player-1, .volleyball-visitor-player-1 { top: 21.85%; }
        .volleyball-home-player-2, .volleyball-visitor-player-2 { top: 27.15%; }
        .volleyball-home-player-3, .volleyball-visitor-player-3 { top: 32.55%; }
        .volleyball-home-player-4, .volleyball-visitor-player-4 { top: 37.95%; }
        .volleyball-home-player-5, .volleyball-visitor-player-5 { top: 43.35%; }
        .volleyball-home-player-6, .volleyball-visitor-player-6 { top: 48.75%; }
        .volleyball-home-player-1, .volleyball-home-player-2, .volleyball-home-player-3, .volleyball-home-player-4, .volleyball-home-player-5, .volleyball-home-player-6 { left: 7.5%; }
        .volleyball-visitor-player-1, .volleyball-visitor-player-2, .volleyball-visitor-player-3, .volleyball-visitor-player-4, .volleyball-visitor-player-5, .volleyball-visitor-player-6 { left: 57.45%; }
    </style>
@endsection
