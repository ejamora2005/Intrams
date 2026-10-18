@extends($scoreSheetLayout, ['title' => $sport->name.' score sheet', 'subtitle' => $profile['title'].' · '.$edition->name])

@section('content')
<div class="ss-workspace">
    <div class="ss-toolbar">
        <a href="{{ $scoreSheetBackUrl }}">← Back to sports</a>
        <div><button type="reset" form="sport-score-sheet">Clear entries</button><button type="button" id="open-score-sheet-download" class="ss-primary" aria-haspopup="dialog" aria-controls="score-sheet-download-drawer">Download sheet</button></div>
    </div>
    <div class="ss-controls">
        <div><strong>{{ $profile['title'] }}</strong><p>Fill in the white sheet, then download it for the scorer. Entries here do not declare winners or change standings.</p>
            @if ($profile['source'])<a href="{{ $profile['source']['url'] }}" target="_blank" rel="noopener">Format reference: {{ $profile['source']['label'] }} ↗</a>@else<p>School competition record · scoring periods and local rules can be written on the sheet.</p>@endif
        </div>
        @if ($schedules->isNotEmpty())
            <form method="GET" action="{{ route($scoreSheetShowRoute, $sport) }}">
                <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                <label>Scheduled session<select name="schedule_id"><option value="">Blank session</option>@foreach($schedules as $schedule)<option value="{{ $schedule->id }}" @selected($sheetSchedule?->id === $schedule->id)>{{ $schedule->starts_at->format('M j, g:i A') }} · {{ $schedule->title }}</option>@endforeach</select></label>
                <button type="submit">Load session</button>
            </form>
        @endif
    </div>
    <form id="sport-score-sheet" data-sport-score-sheet data-score-sheet-export data-download-prefix="{{ Str::slug($sport->name) }}-score-sheet-page-{{ $sheetPage }}" data-players-per-side="{{ $profile['players_per_side'] }}" method="POST" action="{{ route($scoreSheetDownloadRoute, $sport) }}" autocomplete="off">
        @csrf
        <input type="hidden" name="edition_id" value="{{ $edition->id }}">
        @if (! $profile['is_athletics'])
            <div class="ss-controls ss-team-controls">
                @foreach(['a' => ($profile['key'] === 'chess' ? 'White' : 'Team A'), 'b' => ($profile['key'] === 'chess' ? 'Black' : 'Team B')] as $side => $label)
                    <div><label>{{ $label }} · registered faction<select data-sheet-team-picker="{{ $side }}"><option value="">Choose a faction or type on the sheet</option>@foreach($teams as $team)<option value="{{ $team['id'] }}">{{ $team['name'] }}</option>@endforeach</select></label>
                    @if($profile['players_per_side'] > 0)
                        <div data-sheet-player-pickers="{{ $side }}" class="ss-player-pickers"></div>
                    @else
                        <p data-sheet-roster-note="{{ $side }}" aria-live="polite"></p>
                    @endif</div>
                @endforeach
                <p class="ss-control-note">{{ $participantCount }} registered participants. @if($profile['players_per_side'] > 0)Choose the players for this match after selecting their faction.@else Registered names prefill the roster; edit the batting order or lineup on the sheet.@endif</p>
            </div>
        @elseif ($pageCount > 1 && $profile['key'] !== 'relay')
            <nav class="ss-controls ss-pagination" aria-label="Participant sheet pages"><span>{{ $participantCount }} athletes · sheet {{ $sheetPage }} of {{ $pageCount }} (16 athletes per sheet)</span>@foreach(range(1, $pageCount) as $page)<a href="{{ route($scoreSheetShowRoute, ['sport' => $sport, 'edition_id' => $edition->id, 'schedule_id' => $sheetSchedule?->id, 'sheet_page' => $page]) }}" @if($page === $sheetPage) aria-current="page" @endif>Sheet {{ $page }}</a>@endforeach</nav>
        @endif
        <p class="ss-preview-caption">A4 {{ $profile['orientation'] }} · editable preview · @if($profile['key'] === 'russian-softball') School variant: confirm local innings and pairing rules. @elseif($profile['template'] === 'scorebook') One batting team per sheet; download a separate sheet for the opponent. @else Blank fields may be completed by hand. @endif</p>
        <x-score-sheet.preview :orientation="$profile['orientation']">
            <div class="score-sheet-paper ss-paper ss-{{ $profile['orientation'] }}" aria-label="{{ $profile['title'] }} A4 preview" data-sheet-format="{{ $profile['key'] }}">
                <div class="ss-content">
                <header class="ss-heading"><div><p>{{ $edition->name }}</p><h1>{{ $profile['title'] }}</h1><h2>{{ $sport->name }}</h2></div><div class="ss-page-label">{{ strtoupper($profile['orientation']) }} · A4 @if($profile['is_athletics'] && $profile['key'] !== 'relay')<br>Sheet {{ $sheetPage }} / {{ $pageCount }}@endif</div></header>
                <div class="ss-meta">
                    <label>Date<x-score-sheet.cell label="Competition date" :value="$sheetSchedule?->starts_at?->format('Y-m-d') ?? now()->format('Y-m-d')" /></label>
                    <label>Time<x-score-sheet.cell label="Start time" :value="$sheetSchedule?->starts_at?->format('g:i A')" /></label>
                    <label>Venue / court<x-score-sheet.cell label="Venue or court" :value="$sheetSchedule?->venue ?? ($profile['is_athletics'] ? 'Field/Oval' : (str_starts_with($profile['key'], 'basketball') ? 'MPCC' : ''))" /></label>
                    <label>Round / heat / game<x-score-sheet.cell label="Round heat or game" :value="$sheetSchedule?->title" /></label>
                </div>
                @include('sports.score-sheets.'.$profile['template'])
                </div>
                <footer class="ss-signatures"><label>Scorer / recorder<x-score-sheet.cell label="Scorer name and signature" /></label><label>Umpire / referee / chief judge<x-score-sheet.cell label="Official name and signature" /></label><label>Checked by / protest notes<x-score-sheet.cell label="Checked by or protest notes" /></label></footer>
            </div>
        </x-score-sheet.preview>
    </form>
    <dialog id="score-sheet-download-drawer" aria-labelledby="download-drawer-title" class="ss-download">
        <div class="ss-toolbar"><h2 id="download-drawer-title">Download score sheet</h2><button type="button" id="close-score-sheet-download" aria-label="Close download options">✕</button></div>
        <p>Exports include the filled A4 sheet exactly as shown. Word and Excel contain a printable image of the sheet.</p>
        <div class="ss-download-options">@foreach(['pdf' => 'PDF', 'docx' => 'Word', 'xlsx' => 'Excel'] as $format => $label)<button type="submit" form="sport-score-sheet" name="format" value="{{ $format }}">{{ $label }}</button>@endforeach</div>
        <p id="score-sheet-export-status" role="status" aria-live="polite"></p>
    </dialog>
    <script type="application/json" id="sport-score-sheet-data">{!! json_encode(['teams' => $teams, 'participants' => $allParticipants], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</div>
@endsection
