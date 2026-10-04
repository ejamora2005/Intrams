@extends('layouts.admin', [
    'title' => 'Basketball score sheet',
    'subtitle' => 'Editable FIBA-style scoresheet for '.$edition->name.' — '.$sport->name,
])

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <a href="{{ route('admin.sports.index', ['edition_id' => $edition->id]) }}" class="w-fit text-sm font-semibold text-blue-700 hover:text-blue-900">← Back to Sports</a>
        <div class="flex gap-2">
            <button type="reset" form="basketball-score-sheet" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear form</button>
            <button type="button" id="open-score-sheet-download" aria-haspopup="dialog" aria-controls="score-sheet-download-drawer" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Download</button>
        </div>
    </div>

    <form id="basketball-score-sheet" data-score-sheet-export data-download-prefix="basketball-score-sheet" method="POST" action="{{ route('admin.sports.basketball-score-sheet.download', $sport) }}" autocomplete="off">
        @csrf
        <input type="hidden" name="edition_id" value="{{ $edition->id }}" />

        <div class="mx-auto mb-4 grid max-w-3xl gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4 sm:grid-cols-2">
            @if ($teams->isNotEmpty())
                <div class="flex flex-wrap gap-2 sm:col-span-2">
                    @foreach ($teams as $team)
                        <x-team-badge :team="$team" size="xs" class="rounded-full border border-blue-100 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700" />
                    @endforeach
                </div>
            @endif
            @foreach (['a' => 'A', 'b' => 'B'] as $side => $teamLetter)
                <label class="text-sm font-semibold text-slate-700">Prefill Team {{ $teamLetter }}
                    <select data-team-picker="{{ $side }}" class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select team</option>
                        @foreach ($teams as $team)<option value="{{ $team['id'] }}">{{ $team['name'] }}</option>@endforeach
                    </select>
                </label>
            @endforeach
        </div>

        <p class="mb-2 text-center text-xs text-slate-500">A4 portrait · 210 × 297 mm</p>
        <div class="overflow-x-auto rounded-lg bg-slate-200 p-4 sm:p-6">
          <div class="score-sheet-paper" aria-label="A4 paper preview">
            <div class="score-sheet-paper-content">
        @include('admin.sports.partials.basketball-score-sheet', [
            'isPdf' => false,
            'logoSource' => asset('images/fiba-basketball.webp'),
            'form' => [
                'competition' => $edition->name,
                'game_date' => now()->format('Y-m-d'),
            ],
        ])
            </div>
          </div>
        </div>
    </form>

    <dialog id="score-sheet-download-drawer" aria-labelledby="download-drawer-title" class="fixed inset-y-0 left-auto right-0 m-0 h-screen max-h-none w-full max-w-sm border-l border-slate-200 bg-white p-6 shadow-xl backdrop:bg-slate-900/40">
        <div class="flex items-center justify-between gap-3">
            <h2 id="download-drawer-title" class="text-lg font-semibold">Download score sheet</h2>
            <button type="button" id="close-score-sheet-download" aria-label="Close download options" class="rounded p-2 text-slate-600 hover:bg-slate-100">✕</button>
        </div>
        <p class="mt-3 text-sm text-slate-600">Choose a file format for the completed sheet.</p>
        <div class="mt-6 space-y-3">
            @foreach (['docx' => ['Word (.docx)', 'A4 image preserving the sheet layout'], 'xlsx' => ['Excel (.xlsx)', 'A4 image preserving the sheet layout'], 'pdf' => ['PDF (.pdf)', 'A4 page matching the paper preview']] as $format => [$label, $description])
                <button type="submit" form="basketball-score-sheet" name="format" value="{{ $format }}" class="block w-full rounded-lg border border-blue-200 p-4 text-left hover:bg-blue-50 disabled:opacity-50"><span class="block font-semibold text-blue-800">{{ $label }}</span><span class="mt-1 block text-sm text-slate-600">{{ $description }}</span></button>
            @endforeach
        </div>
        <p id="score-sheet-export-status" role="status" class="mt-4 text-sm text-slate-700"></p>
    </dialog>
    <noscript>JavaScript is required to download the score sheet.</noscript>
    <style>
        .score-sheet-paper { box-sizing: border-box; position: relative; width: 210mm; height: 297mm; padding: 4.25mm; margin: 0 auto; background: #fff; overflow: hidden; box-shadow: 0 3px 16px #0002; }
        .score-sheet-paper-content { position: relative; width: 201.5mm; height: 288.5mm; }
        .score-sheet-paper .fiba-sheet { position: absolute; top: 0; left: 0; margin: 0; transform-origin: top left; }
    </style>
    <script>
        const teamRosters = @json($teams);
        const fitPlayerName = (input) => {
            let size = 7;
            input.style.fontSize = `${size}px`;

            while (input.scrollWidth > input.clientWidth && size > 5) {
                size -= .25;
                input.style.fontSize = `${size}px`;
            }
        };

        document.querySelectorAll('[data-player-name]').forEach((input) => input.addEventListener('input', () => fitPlayerName(input)));

        document.querySelectorAll('[data-team-picker]').forEach((picker) => {
            picker.addEventListener('change', () => {
                const side = picker.dataset.teamPicker;
                const team = teamRosters.find((option) => String(option.id) === picker.value);
                document.querySelector(`[data-team-name="${side}"]`).value = team?.name ?? '';
                document.querySelectorAll(`[data-player-row="${side}"]`).forEach((row, index) => {
                    const player = team?.players[index];
                    row.querySelector(`[data-player-course="${side}"]`).value = player?.course ?? '';
                    row.querySelector(`[data-player-name="${side}"]`).value = player?.name ?? '';
                    fitPlayerName(row.querySelector(`[data-player-name="${side}"]`));
                });
            });
        });
    </script>
@endsection
