<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 4mm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body { color: #000; font-family: Arial, sans-serif; font-size: 8px; line-height: 1; margin: 0; }
        .sheet { margin: 0 auto; width: 202mm; }
        table { border-collapse: collapse; width: 100%; }
        .header { height: 22mm; }
        .header .logo { padding: 2mm 0 1mm 1mm; vertical-align: middle; width: 34mm; }
        .header img { height: 18mm; object-fit: contain; width: 30mm; }
        .header .title { padding-right: 26mm; text-align: center; vertical-align: middle; }
        .title p { font-size: 9px; font-weight: bold; margin: 0 0 1mm; }
        .title h1 { font-size: 13px; letter-spacing: .5px; margin: 0; }
        .boxed { border: 1px solid #000; }
        .top td { border-bottom: 1px solid #000; border-right: 1px solid #000; font-weight: bold; height: 6mm; padding: 1mm; vertical-align: bottom; }
        .top tr:last-child td { border-bottom: 0; }
        .top td:last-child { border-right: 0; }
        .field { border-bottom: 1px solid #000; display: inline-block; font-weight: normal; margin-left: 1mm; min-height: 3mm; vertical-align: bottom; }
        .main { border: 1px solid #000; border-top: 0; table-layout: fixed; }
        .main > tbody > tr > td { vertical-align: top; width: 50%; }
        .teams { border-right: 1px solid #000; }
        .team + .team { border-top: 1px solid #000; }
        .team-title { font-size: 10px; font-weight: bold; height: 5mm; padding: 1mm; }
        .team-meta { border-bottom: 1px solid #000; table-layout: fixed; }
        .team-meta td { height: 17mm; padding: 1mm; vertical-align: top; }
        .team-meta td:first-child { border-right: 1px solid #000; width: 27mm; }
        .tiny { font-size: 7px; }
        .timeout-grid { margin: 1mm 0 0 2mm; width: 14mm; }
        .timeout-grid td { border: 1px solid #000; height: 4mm; padding: 0; }
        .foul-line { height: 3.2mm; white-space: nowrap; }
        .foul-line strong { display: inline-block; width: 16mm; }
        .foul-box { border: 1px solid #000; display: inline-block; height: 3.2mm; margin-right: 1mm; width: 20px; }
        .foul-line.extra .field { width: 20mm; }
        .roster { font-size: 7px; table-layout: fixed; }
        .roster th, .roster td { border: 1px solid #000; height: 3.35mm; padding: 0; }
        .roster thead th { font-weight: bold; height: 7mm; text-align: center; vertical-align: bottom; }
        .roster .yr { width: 9mm; }
        .roster .player { text-align: left; }
        .roster .number { text-align: center; width: 30px; }
        .roster .fouls { width: 25px; }
        .roster .cell-value { display: block; overflow: hidden; padding: .2mm; white-space: nowrap; }
        .roster .player-name { font-size: 7px; letter-spacing: -.08px; }
        .roster .player-name.medium { font-size: 6px; }
        .roster .player-name.long { font-size: 5px; }
        .coaches td { border-top: 1px solid #000; font-size: 7px; font-weight: bold; height: 4mm; padding: 0 1mm; }
        .coaches .field { width: 82%; }
        .running-title { border-bottom: 1px solid #000; font-size: 10px; font-weight: bold; height: 5mm; padding: 1.5mm 0 0; text-align: center; }
        .score-groups { table-layout: fixed; }
        .score-groups > tbody > tr > td { border-right: 1px solid #000; padding: 0; vertical-align: top; width: 25%; }
        .score-groups > tbody > tr > td:last-child { border-right: 0; }
        .score-group { font-size: 7px; table-layout: fixed; }
        .score-group th, .score-group td { border-bottom: 1px solid #000; border-right: 1px solid #000; height: 3.35mm; padding: 0; text-align: center; }
        .score-group tr > *:last-child { border-right: 0; }
        .score-group thead th { font-weight: bold; height: 4mm; }
        .score-group td { padding: 0; white-space: nowrap; }
        .score-entry { border: 1px solid #000; display: inline-block; height: 3mm; text-align: center; vertical-align: middle; width: 5.25mm; }
        .footer { border: 1px solid #000; border-top: 0; table-layout: fixed; }
        .footer > tbody > tr > td { vertical-align: top; width: 50%; }
        .officials { border-right: 1px solid #000; padding-top: 1mm; }
        .officials div { font-size: 8px; font-weight: bold; height: 4.3mm; padding: 0 1mm; }
        .officials .line { border-bottom: 1px solid #000; display: inline-block; margin-left: 4mm; width: 66%; }
        .officials .protest { border-top: 1px solid #000; font-size: 6px; height: 5mm; margin-top: 1mm; }
        .scores { padding-top: 1mm; }
        .scores h2 { font-size: 9px; margin: 0 1mm 1mm; }
        .scores .score-row { height: 4.5mm; padding: 0 1mm; text-align: right; }
        .scores strong { display: inline-block; font-size: 8px; width: 52%; }
        .scores .score-value { display: inline-block; font-size: 8px; font-weight: bold; text-align: left; width: 21%; }
        .scores .score-value .field { margin-left: 1mm; width: 7mm; }
        .scores .final { margin-top: 1mm; }
        .scores .winner { font-size: 8px; font-weight: bold; margin: 1mm; }
        .scores .winner .field { width: 58%; }
    </style>
</head>
<body>
    @php
        $periods = ['1', '2', '3', '4', 'ET'];
        $playerNumbers = range(4, 15);
        $value = static fn (string $path): string => (string) data_get($form, $path, '');
        $playerNameSize = static fn (string $name): string => mb_strlen($name) > 28 ? 'long' : (mb_strlen($name) > 20 ? 'medium' : '');
    @endphp
    <div class="sheet">
        <table class="header"><tr><td class="logo"><img src="{{ $logoDataUri }}" alt="FIBA Basketball logo"></td><td class="title"><p>FEDERATION INTERNATIONALE DE BASKETBALL</p><p>INTERNATIONAL BASKETBALL FEDERATION</p><h1>SCORESHEET</h1></td></tr></table>

        <table class="boxed top">
            <tr>
                <td style="width:30%">Team A<span class="field" style="width:70%">{{ $value('team_a_name') }}</span></td>
                <td colspan="3">Team B<span class="field" style="width:82%">{{ $value('team_b_name') }}</span></td>
            </tr>
            <tr><td>Competition<span class="field" style="width:63%">{{ $value('competition') }}</span></td><td style="width:16%">Date<span class="field" style="width:58%">{{ $value('game_date') }}</span></td><td style="width:16%">Time<span class="field" style="width:58%">{{ $value('game_time') }}</span></td><td>Referee<span class="field" style="width:74%">{{ $value('referee') }}</span></td></tr>
            <tr><td>Game No.<span class="field" style="width:63%">{{ $value('game_number') }}</span></td><td colspan="2">Place<span class="field" style="width:80%">{{ $value('venue') }}</span></td><td>Umpire 1<span class="field" style="width:31%">{{ $value('umpire_one') }}</span> Umpire 2<span class="field" style="width:30%">{{ $value('umpire_two') }}</span></td></tr>
        </table>

        <table class="main"><tr>
            <td class="teams">
                @foreach (['a' => 'A', 'b' => 'B'] as $side => $teamLetter)
                    <div class="team">
                        <div class="team-title">Team {{ $teamLetter }}</div>
                        <table class="team-meta"><tr>
                            <td><strong>Time-outs</strong><table class="timeout-grid"><tr><td></td><td></td></tr><tr><td></td><td></td></tr><tr><td></td><td></td></tr></table></td>
                            <td class="tiny">
                                @foreach (['1' => '①', '2' => '②', '3' => '③', '4' => '④'] as $period => $symbol)<div class="foul-line"><strong>Period {{ $symbol }}</strong>@for ($box = 1; $box <= 5; $box++)<span class="foul-box"></span>@endfor</div>@endforeach
                                <div class="foul-line extra"><strong>Extra periods</strong><span class="field">{{ $value('team_fouls.'.$side.'.ET') }}</span></div>
                            </td>
                        </tr></table>
                        <table class="roster"><thead><tr><th class="yr">Yr. &amp;<br>Sec.</th><th>Players</th><th class="number">No.</th><th colspan="5" class="fouls">Fouls<br><span>1&nbsp;&nbsp;&nbsp;&nbsp;2&nbsp;&nbsp;&nbsp;&nbsp;3&nbsp;&nbsp;&nbsp;&nbsp;4&nbsp;&nbsp;&nbsp;&nbsp;5</span></th></tr></thead><tbody>
                            @foreach ($playerNumbers as $playerNumber)
                                @php
                                    $playerName = $value('players.'.$side.'.'.$playerNumber.'.name');
                                @endphp
                                <tr><td><span class="cell-value">{{ $value('players.'.$side.'.'.$playerNumber.'.year_section') }}</span></td><td class="player"><span class="cell-value player-name {{ $playerNameSize($playerName) }}">{{ $playerName }}</span></td><td class="number"><span class="cell-value">{{ $value('players.'.$side.'.'.$playerNumber.'.number') }}</span></td>@for ($foul = 1; $foul <= 5; $foul++)<td><span class="cell-value">{{ $value('players.'.$side.'.'.$playerNumber.'.fouls.'.$foul) }}</span></td>@endfor</tr>
                            @endforeach
                        </tbody></table>
                        <table class="coaches"><tr><td>Coach<span class="field">{{ $value('team_'.$side.'_coach') }}</span></td></tr><tr><td>Assistant Coach<span class="field">{{ $value('team_'.$side.'_assistant_coach') }}</span></td></tr></table>
                    </div>
                @endforeach
            </td>
            <td>
                <div class="running-title">RUNNING SCORE</div>
                <table class="score-groups"><tr>
                    @for ($block = 0; $block < 4; $block++)
                        <td><table class="score-group"><thead><tr><th>A</th><th>B</th></tr></thead><tbody>
                            @for ($row = 1; $row <= 40; $row++)
                                @php
                                    $point = ($block * 40) + $row;
                                @endphp
                                <tr><td><span class="score-entry">{{ $value('running_score.'.$point.'.a') ?: $point }}</span></td><td><span class="score-entry">{{ $value('running_score.'.$point.'.b') ?: $point }}</span></td></tr>
                            @endfor
                        </tbody></table></td>
                    @endfor
                </tr></table>
            </td>
        </tr></table>

        <table class="footer"><tr>
            <td class="officials">
                @foreach (['Scorekeeper', 'Assistant Scorekeeper', 'Timekeeper', '24” operator', 'Referee', 'Umpire 1', 'Umpire 2'] as $official)
                    <div>{{ $official }}<span class="line">{{ $value('officials.'.Str::slug($official, '_')) }}</span></div>
                @endforeach
                <div class="protest">Captain’s signature in case of protest<span class="line">{{ $value('captain_protest_signature') }}</span></div>
            </td>
            <td class="scores">
                <h2>Scores</h2>
                @foreach ($periods as $period)
                    <div class="score-row"><strong>{{ $period === 'ET' ? 'Extra periods' : 'Period '.$period }}</strong><span class="score-value">A<span class="field">{{ $value('period_scores.'.$period.'.a') }}</span></span><span class="score-value">B<span class="field">{{ $value('period_scores.'.$period.'.b') }}</span></span></div>
                @endforeach
                <div class="score-row final"><strong>Final Score</strong><span class="score-value">Team A<span class="field">{{ $value('final_score_a') }}</span></span><span class="score-value">Team B<span class="field">{{ $value('final_score_b') }}</span></span></div>
                <div class="winner">Name of winning team<span class="field">{{ $value('winning_team') }}</span></div>
            </td>
        </tr></table>
    </div>
</body>
</html>
