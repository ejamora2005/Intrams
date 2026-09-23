@extends('layouts.admin', [
    'title' => 'Basketball score sheet',
    'subtitle' => 'Editable FIBA-style scoresheet for '.$edition->name.' — '.$sport->name,
])

@section('content')
    @php
        $periods = ['1', '2', '3', '4', 'ET'];
        $playerNumbers = range(4, 15);
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <a href="{{ route('admin.sports.index', ['edition_id' => $edition->id]) }}" class="w-fit text-sm font-semibold text-blue-700 hover:text-blue-900">← Back to Sports</a>
        <div class="flex gap-2">
            <button type="reset" form="basketball-score-sheet" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear form</button>
            <button type="submit" form="basketball-score-sheet" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Preview &amp; Download PDF</button>
        </div>
    </div>

    <form id="basketball-score-sheet" method="POST" action="{{ route('admin.sports.basketball-score-sheet.download', $sport) }}" target="_blank" class="fiba-sheet bg-white text-black shadow-sm" autocomplete="off">
        @csrf
        <input type="hidden" name="edition_id" value="{{ $edition->id }}" />
        <header class="fiba-head">
            <img class="fiba-logo" src="{{ asset('images/fiba-basketball.webp') }}" alt="FIBA Basketball logo" />
            <div class="fiba-heading">
                <p>FEDERATION INTERNATIONALE DE BASKETBALL</p>
                <p>INTERNATIONAL BASKETBALL FEDERATION</p>
                <h2>SCORESHEET</h2>
            </div>
        </header>

        <section class="fiba-top-fields">
            <div class="fiba-team-names">
                <label>Team A<input data-team-name="a" name="team_a_name" /></label>
                <label>Team B<input data-team-name="b" name="team_b_name" /></label>
            </div>
            <div class="fiba-game-fields">
                <label>Competition<input name="competition" value="{{ $edition->name }}" /></label>
                <label>Date<input type="date" name="game_date" value="{{ now()->format('Y-m-d') }}" /></label>
                <label>Time<input type="time" name="game_time" /></label>
                <label>Referee<input name="referee" /></label>
                <label>Game No.<input name="game_number" /></label>
                <label>Place<input name="venue" /></label>
                <label>Umpire 1<input name="umpire_one" /></label>
                <label>Umpire 2<input name="umpire_two" /></label>
            </div>
        </section>

        <main class="fiba-main">
            <section class="fiba-teams" aria-label="Team A and Team B records">
                @foreach (['a' => 'A', 'b' => 'B'] as $side => $teamLetter)
                    <section class="fiba-team-block" aria-labelledby="team-{{ $side }}-title">
                        <div class="fiba-team-title">
                            <strong id="team-{{ $side }}-title">Team {{ $teamLetter }}</strong>
                            <label>Prefill team<select data-team-picker="{{ $side }}"><option value="">Select team</option>@foreach ($teams as $team)<option value="{{ $team['id'] }}">{{ $team['name'] }}</option>@endforeach</select></label>
                        </div>

                        <div class="fiba-team-meta">
                            <div class="fiba-timeouts">
                                <strong>Time-outs</strong>
                                <div class="timeout-cells"><input name="timeouts[{{ $side }}][1][1]" /><input name="timeouts[{{ $side }}][1][2]" /><input name="timeouts[{{ $side }}][2][1]" /><input name="timeouts[{{ $side }}][2][2]" /><input name="timeouts[{{ $side }}][3][1]" /><input name="timeouts[{{ $side }}][3][2]" /></div>
                            </div>
                            <div class="fiba-foul-summary">
                                <div><span>Period ①</span>@for ($box = 1; $box <= 5; $box++)<input name="team_fouls[{{ $side }}][1][{{ $box }}]" />@endfor</div>
                                <div><span>Period ②</span>@for ($box = 1; $box <= 5; $box++)<input name="team_fouls[{{ $side }}][2][{{ $box }}]" />@endfor</div>
                                <div><span>Period ③</span>@for ($box = 1; $box <= 5; $box++)<input name="team_fouls[{{ $side }}][3][{{ $box }}]" />@endfor</div>
                                <div><span>Period ④</span>@for ($box = 1; $box <= 5; $box++)<input name="team_fouls[{{ $side }}][4][{{ $box }}]" />@endfor</div>
                                <div class="extra-period"><span>Extra periods</span><input name="team_fouls[{{ $side }}][ET]" /></div>
                            </div>
                        </div>

                        <table class="fiba-roster">
                            <thead>
                                <tr><th class="year-section">Yr. &amp;<br>Sec.</th><th>Players</th><th class="player-no">No.</th><th colspan="5" class="fouls">Fouls<br><span>1&nbsp;&nbsp;&nbsp;&nbsp;2&nbsp;&nbsp;&nbsp;&nbsp;3&nbsp;&nbsp;&nbsp;&nbsp;4&nbsp;&nbsp;&nbsp;&nbsp;5</span></th></tr>
                            </thead>
                            <tbody>
                                @foreach ($playerNumbers as $slot => $playerNumber)
                                    <tr data-player-row="{{ $side }}">
                                        <td><input data-player-year-section="{{ $side }}" name="players[{{ $side }}][{{ $playerNumber }}][year_section]" /></td>
                                        <td><input data-player-name="{{ $side }}" name="players[{{ $side }}][{{ $playerNumber }}][name]" /></td>
                                        <td class="player-no"><input name="players[{{ $side }}][{{ $playerNumber }}][number]" aria-label="Player number" /></td>
                                        @for ($foul = 1; $foul <= 5; $foul++)<td><input name="players[{{ $side }}][{{ $playerNumber }}][fouls][{{ $foul }}]" maxlength="2" /></td>@endfor
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="fiba-coaches">
                            <label>Coach<input name="team_{{ $side }}_coach" /></label>
                            <label>Assistant Coach<input name="team_{{ $side }}_assistant_coach" /></label>
                        </div>
                    </section>
                @endforeach
            </section>

            <section class="fiba-running-score" aria-label="Running score">
                <h3>RUNNING SCORE</h3>
                <div class="fiba-score-columns">
                    @for ($block = 0; $block < 4; $block++)
                        <table>
                            <thead><tr><th>A</th><th>B</th></tr></thead>
                            <tbody>
                                @for ($row = 1; $row <= 40; $row++)
                                    @php
                                        $point = ($block * 40) + $row;
                                    @endphp
                                    <tr><td><input class="score-entry" name="running_score[{{ $point }}][a]" aria-label="Team A mark at {{ $point }}" maxlength="2" placeholder="{{ $point }}" /></td><td><input class="score-entry" name="running_score[{{ $point }}][b]" aria-label="Team B mark at {{ $point }}" maxlength="2" placeholder="{{ $point }}" /></td></tr>
                                @endfor
                            </tbody>
                        </table>
                    @endfor
                </div>
            </section>
        </main>

        <footer class="fiba-footer">
            <section class="fiba-officials">
                @foreach (['Scorekeeper', 'Assistant Scorekeeper', 'Timekeeper', '24” operator', 'Referee', 'Umpire 1', 'Umpire 2'] as $official)
                    <label>{{ $official }}<input name="officials[{{ Str::slug($official, '_') }}]" /></label>
                @endforeach
                <label class="protest">Captain’s signature in case of protest<input name="captain_protest_signature" /></label>
            </section>
            <section class="fiba-final-scores">
                <h3>Scores</h3>
                @foreach ($periods as $period)
                    <div><strong>{{ $period === 'ET' ? 'Extra periods' : 'Period '.$period }}</strong><label>A<input name="period_scores[{{ $period }}][a]" /></label><label>B<input name="period_scores[{{ $period }}][b]" /></label></div>
                @endforeach
                <div class="final-line"><strong>Final Score</strong><label>Team A<input name="final_score_a" /></label><label>Team B<input name="final_score_b" /></label></div>
                <label class="winning-team">Name of winning team<input name="winning_team" /></label>
            </section>
        </footer>
    </form>

    <style>
        .fiba-sheet { font-family: "Arial Narrow", Arial, sans-serif; font-size: 10px; line-height: 1.05; margin: 0 auto; padding: 0; width: 202mm; }
        .fiba-sheet input, .fiba-sheet select { appearance: none; background: transparent; border: 0; border-bottom: 1px solid #000; border-radius: 0; color: #000; font: inherit; min-width: 0; outline: 0; padding: 0 2px; }
        .fiba-sheet input:focus, .fiba-sheet select:focus { background: #e0f2fe; box-shadow: inset 0 0 0 1px #0c4a6e; }
        .fiba-head { display: grid; grid-template-columns: 35mm 1fr; min-height: 22mm; padding: 4mm 0 1.5mm; }
        .fiba-logo { display: block; height: 18mm; margin-left: 1mm; object-fit: contain; object-position: left center; width: 30mm; }
        .fiba-heading { align-self: center; padding-right: 28mm; text-align: center; } .fiba-heading p { font-size: 10px; font-stretch: condensed; font-weight: 800; margin: 0; } .fiba-heading h2 { font-size: 14px; letter-spacing: .5px; margin: 1mm 0 0; }
        .fiba-top-fields { border: 1px solid #000; }
        .fiba-team-names { display: grid; grid-template-columns: 1fr 1fr; } .fiba-team-names label { border-bottom: 1px solid #000; font-size: 10px; font-weight: 800; padding: 1mm 1.5mm; } .fiba-team-names label + label { border-left: 1px solid #000; }
        .fiba-team-names input { margin-left: 2mm; width: calc(100% - 18mm); }
        .fiba-game-fields { display: grid; grid-template-columns: 1.2fr .55fr .7fr 2fr; } .fiba-game-fields label { align-items: end; border-bottom: 1px solid #000; display: flex; font-size: 9px; font-weight: 800; min-height: 6mm; padding: 1mm; } .fiba-game-fields label:nth-child(4n + 1) { border-left: 0; } .fiba-game-fields label:not(:nth-child(4n + 1)) { border-left: 1px solid #000; } .fiba-game-fields label:nth-last-child(-n + 4) { border-bottom: 0; } .fiba-game-fields input { flex: 1; margin-left: 1mm; }
        .fiba-main { border: 1px solid #000; border-top: 0; display: grid; grid-template-columns: 50% 50%; }
        .fiba-teams { border-right: 1px solid #000; } .fiba-team-block + .fiba-team-block { border-top: 1px solid #000; }
        .fiba-team-title { align-items: center; display: flex; font-size: 11px; height: 5mm; justify-content: space-between; padding: 0 1mm; } .fiba-team-title > strong { font-size: 11px; } .fiba-team-title label { align-items: center; display: flex; font-size: 7px; font-weight: 700; gap: 1mm; } .fiba-team-title select { border: 1px solid #000; height: 4mm; width: 26mm; }
        .fiba-team-meta { border-bottom: 1px solid #000; display: grid; grid-template-columns: 27mm 1fr; height: 17mm; } .fiba-timeouts { border-right: 1px solid #000; padding: 1mm; } .fiba-timeouts > strong { display: block; font-size: 8px; } .timeout-cells { display: grid; gap: 1px; grid-template-columns: repeat(2, 6mm); margin: 1mm 0 0 2mm; } .timeout-cells input { border: 1px solid #000; height: 4mm; width: 6mm; }
        .fiba-foul-summary { padding: .5mm 1mm; } .fiba-foul-summary > div { align-items: center; display: flex; height: 3.3mm; } .fiba-foul-summary span { font-size: 8px; font-weight: 700; min-width: 16mm; } .fiba-foul-summary input { border: 1px solid #000; height: 3.3mm; margin-right: 1mm; width: 4mm; } .fiba-foul-summary .extra-period input { flex: 1; }
        .fiba-roster { border-collapse: collapse; table-layout: fixed; width: 100%; } .fiba-roster th, .fiba-roster td { border: 1px solid #000; height: 3.35mm; padding: 0; } .fiba-roster thead th { font-size: 7px; height: 7mm; text-align: center; vertical-align: bottom; } .fiba-roster .year-section { width: 9mm; } .fiba-roster .player-no { text-align: center; width: 5px; } .fiba-roster .fouls { width: 25px; } .fiba-roster .fouls span { font-size: 6px; } .fiba-roster td:not(:nth-child(2)) { text-align: center; } .fiba-roster input { height: 100%; padding: 0; text-align: center; width: 100%; } .fiba-roster td:nth-child(2) input { font-size: 8px; letter-spacing: -.08px; text-align: left; }
        .fiba-coaches label { align-items: end; border-top: 1px solid #000; display: flex; font-size: 8px; font-weight: 800; height: 4mm; padding: 0 1mm; } .fiba-coaches input { flex: 1; margin-left: 2mm; }
        .fiba-running-score h3 { border-bottom: 1px solid #000; font-size: 11px; margin: 0; padding: 1.5mm 0 1mm; text-align: center; } .fiba-score-columns { display: grid; grid-template-columns: repeat(4, 1fr); } .fiba-score-columns table { border-collapse: collapse; table-layout: fixed; width: 100%; } .fiba-score-columns table + table { border-left: 1px solid #000; } .fiba-score-columns th, .fiba-score-columns td { border-bottom: 1px solid #000; border-right: 1px solid #000; height: 3.35mm; padding: 0; text-align: center; } .fiba-score-columns thead th { font-size: 7px; font-weight: 800; height: 4mm; } .fiba-score-columns th:last-child, .fiba-score-columns td:last-child { border-right: 0; } .fiba-score-columns td { padding: 0; white-space: nowrap; } .fiba-score-columns .score-entry { border: 1px solid #000; height: 3mm; padding: 0; text-align: center; vertical-align: middle; width: 5.25mm; } .fiba-score-columns .score-entry::placeholder { color: #000; opacity: 1; }
        .fiba-footer { border: 1px solid #000; border-top: 0; display: grid; grid-template-columns: 50% 50%; min-height: 42mm; } .fiba-officials { border-right: 1px solid #000; padding-top: 1mm; } .fiba-officials label { align-items: end; display: flex; font-size: 9px; font-weight: 800; height: 4.3mm; padding: 0 1mm; } .fiba-officials input { flex: 1; margin-left: 4mm; } .fiba-officials .protest { border-top: 1px solid #000; font-size: 7px; margin-top: 1mm; }
        .fiba-final-scores { padding-top: 1mm; } .fiba-final-scores h3 { font-size: 10px; margin: 0 1mm 1mm; } .fiba-final-scores > div { align-items: end; display: grid; grid-template-columns: 1fr 14mm 14mm; height: 4.5mm; padding: 0 1mm; } .fiba-final-scores strong { font-size: 8px; text-align: right; } .fiba-final-scores label { align-items: end; display: flex; font-size: 8px; font-weight: 800; padding-left: 2mm; } .fiba-final-scores label input { width: 7mm; } .fiba-final-scores .final-line { margin-top: 1mm; } .fiba-final-scores .winning-team { display: flex; margin: 1mm; padding-left: 0; } .fiba-final-scores .winning-team input { flex: 1; margin-left: 2mm; width: auto; }
        @media screen and (max-width: 850px) { .fiba-sheet { min-width: 202mm; } }
    </style>

    <script>
        const teamRosters = @json($teams);
        const fitPlayerName = (input) => {
            let size = 8;
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
                    row.querySelector(`[data-player-year-section="${side}"]`).value = player?.year_section ?? '';
                    row.querySelector(`[data-player-name="${side}"]`).value = player?.name ?? '';
                    fitPlayerName(row.querySelector(`[data-player-name="${side}"]`));
                });
            });
        });
    </script>
@endsection
