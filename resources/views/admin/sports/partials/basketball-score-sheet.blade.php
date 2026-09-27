@php
    $isPdf = $isPdf ?? false;
    $form = $form ?? [];
    $periods = ['1', '2', '3', '4', 'ET'];
    $playerNumbers = range(4, 15);
    $value = static fn (string $path, string $default = ''): string => (string) data_get($form, $path, $default);
    $playerNameSize = static fn (string $name): string => mb_strlen($name) > 28 ? 'long' : (mb_strlen($name) > 20 ? 'medium' : '');
    $control = static function (string $name, string $currentValue = '', array $attributes = [], string $fallback = '') use ($isPdf): string {
        $classes = trim('sheet-control '.($attributes['class'] ?? ''));
        unset($attributes['class']);

        if ($isPdf) {
            return '<span class="'.e($classes).'">'.e($currentValue !== '' ? $currentValue : $fallback).'</span>';
        }

        $html = '<input class="'.e($classes).'" name="'.e($name).'" value="'.e($currentValue).'"';
        foreach ($attributes as $attribute => $attributeValue) {
            $html .= ' '.e($attribute).'="'.e((string) $attributeValue).'"';
        }

        return $html.' />';
    };
@endphp

<div class="fiba-sheet">
    <table class="fiba-header"><tr><td class="logo"><img src="{{ $logoSource }}" alt="FIBA Basketball logo"></td><td class="title"><p>FEDERATION INTERNATIONALE DE BASKETBALL</p><p>INTERNATIONAL BASKETBALL FEDERATION</p><h2>SCORESHEET</h2></td></tr></table>

    <table class="boxed fiba-top">
        <tr>
            <td style="width:30%">Team A<span class="field" style="width:70%">{!! $control('team_a_name', $value('team_a_name'), ['data-team-name' => 'a']) !!}</span></td>
            <td colspan="3">Competition<span class="field" style="width:82%">{!! $control('competition', $value('competition')) !!}</span></td>
        </tr>
        <tr><td>Team B<span class="field" style="width:70%">{!! $control('team_b_name', $value('team_b_name'), ['data-team-name' => 'b']) !!}</span></td><td style="width:16%">Date<span class="field" style="width:58%">{!! $control('game_date', $value('game_date'), ['type' => 'date']) !!}</span></td><td style="width:16%">Time<span class="field" style="width:58%">{!! $control('game_time', $value('game_time'), ['type' => 'time']) !!}</span></td><td>Referee<span class="field" style="width:74%">{!! $control('referee', $value('referee')) !!}</span></td></tr>
        <tr><td>Game No.<span class="field" style="width:63%">{!! $control('game_number', $value('game_number')) !!}</span></td><td colspan="2">Place<span class="field" style="width:80%">{!! $control('venue', $value('venue')) !!}</span></td><td>Umpire 1<span class="field" style="width:31%">{!! $control('umpire_one', $value('umpire_one')) !!}</span> Umpire 2<span class="field" style="width:30%">{!! $control('umpire_two', $value('umpire_two')) !!}</span></td></tr>
    </table>

    <table class="fiba-main"><tr>
        <td class="teams">
            @foreach (['a' => 'A', 'b' => 'B'] as $side => $teamLetter)
                <div class="team">
                    <div class="team-title">Team {{ $teamLetter }}</div>
                    <table class="team-meta"><tr>
                        <td><strong>Time-outs</strong><table class="timeout-grid">
                            @for ($period = 1; $period <= 3; $period++)
                                <tr>@for ($box = 1; $box <= 2; $box++)<td>{!! $control("timeouts[{$side}][{$period}][{$box}]", $value("timeouts.{$side}.{$period}.{$box}"), ['aria-label' => "Team {$teamLetter} timeout {$period}-{$box}"]) !!}</td>@endfor</tr>
                            @endfor
                        </table></td>
                        <td class="tiny">
                            @foreach (['1' => '①', '2' => '②', '3' => '③', '4' => '④'] as $period => $symbol)<div class="foul-line"><strong>Period {{ $symbol }}</strong>@for ($box = 1; $box <= 5; $box++){!! $control("team_fouls[{$side}][{$period}][{$box}]", $value("team_fouls.{$side}.{$period}.{$box}"), ['class' => 'foul-box', 'aria-label' => "Team {$teamLetter} period {$period} foul {$box}"]) !!}@endfor</div>@endforeach
                            <div class="foul-line extra"><strong>Extra periods</strong><span class="field">{!! $control("team_fouls[{$side}][ET]", $value("team_fouls.{$side}.ET")) !!}</span></div>
                        </td>
                    </tr></table>
                    <table class="fiba-roster"><thead><tr><th class="player-in" aria-label="Row number"></th><th>Players</th><th class="player-no">No.</th><th class="course">Course</th><th colspan="4" class="fouls">Fouls<br><span>1&nbsp;&nbsp;&nbsp;&nbsp;2&nbsp;&nbsp;&nbsp;&nbsp;3&nbsp;&nbsp;&nbsp;&nbsp;4</span></th></tr></thead><tbody>
                        @foreach ($playerNumbers as $playerNumber)
                            @php $playerName = $value("players.{$side}.{$playerNumber}.name"); @endphp
                            <tr data-player-row="{{ $side }}">
                                <td class="player-in">{{ $loop->iteration }}</td>
                                <td class="player">{!! $control("players[{$side}][{$playerNumber}][name]", $playerName, ['class' => 'player-name '.$playerNameSize($playerName), 'data-player-name' => $side]) !!}</td>
                                <td class="player-no">{!! $control("players[{$side}][{$playerNumber}][number]", $value("players.{$side}.{$playerNumber}.number"), ['aria-label' => 'Player number']) !!}</td>
                                <td class="course">{!! $control("players[{$side}][{$playerNumber}][course]", $value("players.{$side}.{$playerNumber}.course"), ['data-player-course' => $side]) !!}</td>
                                @for ($foul = 1; $foul <= 4; $foul++)<td>{!! $control("players[{$side}][{$playerNumber}][fouls][{$foul}]", $value("players.{$side}.{$playerNumber}.fouls.{$foul}"), ['maxlength' => '2']) !!}</td>@endfor
                            </tr>
                        @endforeach
                    </tbody></table>
                    <table class="coaches"><tr><td>Coach<span class="field">{!! $control("team_{$side}_coach", $value("team_{$side}_coach")) !!}</span></td></tr><tr><td>Assistant Coach<span class="field">{!! $control("team_{$side}_assistant_coach", $value("team_{$side}_assistant_coach")) !!}</span></td></tr></table>
                </div>
            @endforeach
        </td>
        <td>
            <div class="running-title">RUNNING SCORE</div>
            <table class="score-groups"><tr>
                @for ($block = 0; $block < 4; $block++)
                    <td><table class="score-group"><thead><tr><th>A</th><th>B</th></tr></thead><tbody>
                        @for ($row = 1; $row <= 40; $row++)
                            @php $point = ($block * 40) + $row; @endphp
                            <tr><td>{!! $control("running_score[{$point}][a]", $value("running_score.{$point}.a"), ['class' => 'score-entry', 'aria-label' => "Team A mark at {$point}", 'maxlength' => '2', 'placeholder' => (string) $point], (string) $point) !!}</td><td>{!! $control("running_score[{$point}][b]", $value("running_score.{$point}.b"), ['class' => 'score-entry', 'aria-label' => "Team B mark at {$point}", 'maxlength' => '2', 'placeholder' => (string) $point], (string) $point) !!}</td></tr>
                        @endfor
                    </tbody></table></td>
                @endfor
            </tr></table>
        </td>
    </tr></table>

    <table class="fiba-footer"><tr>
        <td class="officials">
            @foreach (['Scorekeeper', 'Assistant Scorekeeper', 'Timekeeper', '24” operator', 'Referee', 'Umpire 1', 'Umpire 2'] as $official)
                <div>{{ $official }}<span class="line">{!! $control('officials['.Str::slug($official, '_').']', $value('officials.'.Str::slug($official, '_'))) !!}</span></div>
            @endforeach
            <div class="protest">Captain’s signature in case of protest<span class="line">{!! $control('captain_protest_signature', $value('captain_protest_signature')) !!}</span></div>
        </td>
        <td class="scores">
            <h3>Scores</h3>
            @foreach ($periods as $period)
                <div class="score-row"><strong>{{ $period === 'ET' ? 'Extra periods' : 'Period '.$period }}</strong><span class="score-value">A<span class="field">{!! $control("period_scores[{$period}][a]", $value("period_scores.{$period}.a")) !!}</span></span><span class="score-value">B<span class="field">{!! $control("period_scores[{$period}][b]", $value("period_scores.{$period}.b")) !!}</span></span></div>
            @endforeach
            <div class="score-row final"><strong>Final Score</strong><span class="score-value">Team A<span class="field">{!! $control('final_score_a', $value('final_score_a')) !!}</span></span><span class="score-value">Team B<span class="field">{!! $control('final_score_b', $value('final_score_b')) !!}</span></span></div>
            <div class="winner">Name of winning team<span class="field">{!! $control('winning_team', $value('winning_team')) !!}</span></div>
        </td>
    </tr></table>
</div>

<style>
    @if ($isPdf) @page { margin: 4mm; size: A4 portrait; } body { color: #000; margin: 0; } @endif
    .fiba-sheet, .fiba-sheet * { box-sizing: border-box; }
    .fiba-sheet { color: #000; font-family: Arial, sans-serif; font-size: 8px; line-height: 1; margin: 0 auto; padding: 0; width: 202mm; }
    .fiba-sheet table { border-collapse: collapse; width: 100%; }
    .fiba-sheet .fiba-header { height: 22mm; }
    .fiba-sheet .fiba-header .logo { padding: 2mm 0 1mm 1mm; vertical-align: middle; width: 34mm; }
    .fiba-sheet .fiba-header img { height: 18mm; object-fit: contain; width: 30mm; }
    .fiba-sheet .fiba-header .title { padding-right: 26mm; text-align: center; vertical-align: middle; }
    .fiba-sheet .title p { font-size: 9px; font-weight: bold; margin: 0 0 1mm; }
    .fiba-sheet .title h2 { font-size: 13px; letter-spacing: .5px; margin: 0; }
    .fiba-sheet .boxed { border: 1px solid #000; }
    .fiba-sheet .fiba-top td { border-bottom: 1px solid #000; border-right: 1px solid #000; font-weight: bold; height: 6mm; padding: 1mm; vertical-align: bottom; }
    .fiba-sheet .fiba-top tr:last-child td { border-bottom: 0; }
    .fiba-sheet .fiba-top td:last-child { border-right: 0; }
    .fiba-sheet .field { border-bottom: 1px solid #000; display: inline-block; font-weight: normal; margin-left: 1mm; min-height: 3mm; vertical-align: bottom; }
    .fiba-sheet .sheet-control { background: transparent; border: 0; border-radius: 0; color: #000; display: inline-block; font: inherit; line-height: 1; min-width: 0; outline: 0; overflow: hidden; padding: 0 .2mm; vertical-align: middle; white-space: nowrap; width: 100%; }
    .fiba-sheet input.sheet-control:focus { background: #e0f2fe; box-shadow: inset 0 0 0 1px #0c4a6e; }
    .fiba-sheet .fiba-main { border: 1px solid #000; border-top: 0; table-layout: fixed; }
    .fiba-sheet .fiba-main > tbody > tr > td { vertical-align: top; width: 50%; }
    .fiba-sheet .teams { border-right: 1px solid #000; }
    .fiba-sheet .team + .team { border-top: 1px solid #000; }
    .fiba-sheet .team-title { font-size: 10px; font-weight: bold; height: 5mm; padding: 1mm; }
    .fiba-sheet .team-meta { border-bottom: 1px solid #000; table-layout: fixed; }
    .fiba-sheet .team-meta td { height: 17mm; padding: 1mm; vertical-align: top; }
    .fiba-sheet .team-meta td:first-child { border-right: 1px solid #000; width: 27mm; }
    .fiba-sheet .tiny { font-size: 7px; }
    .fiba-sheet .timeout-grid { margin: 1mm 0 0 2mm; width: 14mm; }
    .fiba-sheet .timeout-grid td { border: 1px solid #000; height: 4mm; padding: 0; width: 7mm; }
    .fiba-sheet .foul-line { height: 3.2mm; white-space: nowrap; }
    .fiba-sheet .foul-line strong { display: inline-block; width: 16mm; }
    .fiba-sheet .foul-box { border: 1px solid #000; display: inline-block; height: 3.2mm; margin-right: 1mm; padding: 0; width: 20px; }
    .fiba-sheet .foul-line.extra .field { width: 20mm; }
    .fiba-sheet .fiba-roster { font-size: 7px; table-layout: fixed; }
    .fiba-sheet .fiba-roster th, .fiba-sheet .fiba-roster td { border: 1px solid #000; height: 3.35mm; padding: 0; }
    .fiba-sheet .fiba-roster thead th { font-weight: bold; height: 7mm; text-align: center; vertical-align: bottom; }
    .fiba-sheet .fiba-roster .course { width: 12mm; }
    .fiba-sheet .fiba-roster .player { text-align: left; }
    .fiba-sheet .fiba-roster .player-no { text-align: center; width: 5mm; }
    .fiba-sheet .fiba-roster .player-in { text-align: center; width: 6mm; }
    .fiba-sheet .fiba-roster .fouls { width: 20mm; }
    .fiba-sheet .fiba-roster .player-name { font-size: 7px; letter-spacing: -.08px; text-align: left; }
    .fiba-sheet .fiba-roster .player-name.medium { font-size: 6px; }
    .fiba-sheet .fiba-roster .player-name.long { font-size: 5px; }
    .fiba-sheet .coaches td { border-top: 1px solid #000; font-size: 7px; font-weight: bold; height: 4mm; padding: 0 1mm; }
    .fiba-sheet .coaches .field { width: 82%; }
    .fiba-sheet .running-title { border-bottom: 1px solid #000; font-size: 10px; font-weight: bold; height: 5mm; padding: 1.5mm 0 0; text-align: center; }
    .fiba-sheet .score-groups { table-layout: fixed; }
    .fiba-sheet .score-groups > tbody > tr > td { border-right: 1px solid #000; padding: 0; vertical-align: top; width: 25%; }
    .fiba-sheet .score-groups > tbody > tr > td:last-child { border-right: 0; }
    .fiba-sheet .score-group { font-size: 7px; table-layout: fixed; }
    .fiba-sheet .score-group th, .fiba-sheet .score-group td { border-bottom: 1px solid #000; border-right: 1px solid #000; height: 3.35mm; padding: 0; text-align: center; }
    .fiba-sheet .score-group tr > *:last-child { border-right: 0; }
    .fiba-sheet .score-group thead th { font-weight: bold; height: 4mm; }
    .fiba-sheet .score-entry { border: 1px solid #000; display: inline-block; height: 3mm; padding: 0; text-align: center; vertical-align: middle; width: 5.25mm; }
    .fiba-sheet input.score-entry::placeholder { color: #000; opacity: 1; }
    .fiba-sheet .fiba-footer { border: 1px solid #000; border-top: 0; table-layout: fixed; }
    .fiba-sheet .fiba-footer > tbody > tr > td { vertical-align: top; width: 50%; }
    .fiba-sheet .officials { border-right: 1px solid #000; padding-top: 1mm; }
    .fiba-sheet .officials div { font-size: 8px; font-weight: bold; height: 4.3mm; padding: 0 1mm; }
    .fiba-sheet .officials .line { border-bottom: 1px solid #000; display: inline-block; margin-left: 4mm; width: 66%; }
    .fiba-sheet .officials .protest { border-top: 1px solid #000; font-size: 6px; height: 5mm; margin-top: 1mm; }
    .fiba-sheet .scores { padding-top: 1mm; }
    .fiba-sheet .scores h3 { font-size: 9px; margin: 0 1mm 1mm; }
    .fiba-sheet .scores .score-row { height: 4.5mm; padding: 0 1mm; text-align: right; }
    .fiba-sheet .scores strong { display: inline-block; font-size: 8px; width: 52%; }
    .fiba-sheet .scores .score-value { display: inline-block; font-size: 8px; font-weight: bold; text-align: left; width: 21%; }
    .fiba-sheet .scores .score-value .field { margin-left: 1mm; width: 7mm; }
    .fiba-sheet .scores .final { margin-top: 1mm; }
    .fiba-sheet .scores .winner { font-size: 8px; font-weight: bold; margin: 1mm; }
    .fiba-sheet .scores .winner .field { width: 58%; }
    @media screen and (max-width: 850px) { .fiba-sheet { min-width: 202mm; } }
</style>
