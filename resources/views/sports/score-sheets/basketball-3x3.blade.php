@include('sports.score-sheets.teams')
<div class="ss-grid ss-block">
@foreach(['a'=>'A', 'b'=>'B'] as $side=>$letter)
    <div><h3>Team {{ $letter }} · 3 players + 1 substitute</h3><table class="ss-tall"><thead><tr><th style="width:10mm">No.</th><th>Player name</th><th style="width:12mm">1 pt</th><th style="width:12mm">2 pt</th><th style="width:12mm">FT</th></tr></thead><tbody>
        @for($row=0;$row<4;$row++)<tr><td><x-score-sheet.cell label="{{ $letter }} player {{ $row+1 }} number" /></td><td><x-score-sheet.cell label="{{ $letter }} player {{ $row+1 }} name" :side="$side" :row="$row" kind="name" /></td>@foreach(['1 point','2 point','Free throws'] as $stat)<td><x-score-sheet.cell label="{{ $letter }} player {{ $row+1 }} {{ $stat }}" /></td>@endforeach</tr>@endfor
    </tbody></table></div>
@endforeach
</div>
<h3>Team fouls</h3><table class="ss-score-grid"><thead><tr><th style="width:12mm">Team</th>@foreach(range(1,12) as $foul)<th>{{ $foul }}</th>@endforeach<th style="width:17mm">Timeout</th></tr></thead><tbody>@foreach(['A','B'] as $letter)<tr><th>{{ $letter }}</th>@foreach(range(1,12) as $foul)<td><x-score-sheet.cell label="Team {{ $letter }} foul {{ $foul }}" /></td>@endforeach<td><x-score-sheet.cell label="Team {{ $letter }} timeout" /></td></tr>@endforeach</tbody></table>
<h3>Running score · mark the scorer's number at each point</h3>
@foreach([[1,16],[17,32]] as [$first,$last])
<table class="ss-score-grid ss-block"><thead><tr><th style="width:12mm">Score</th>@foreach(range($first,$last) as $point)<th>{{ $point }}</th>@endforeach</tr></thead><tbody>@foreach(['A','B'] as $letter)<tr><th>{{ $letter }}</th>@foreach(range($first,$last) as $point)<td><x-score-sheet.cell label="Team {{ $letter }} running score {{ $point }}" /></td>@endforeach</tr>@endforeach</tbody></table>
@endforeach
<div class="ss-grid ss-block"><label>Game start / scheduled playing time<x-score-sheet.cell label="Game start and scheduled playing time" value="10 minutes" /></label><label>First possession / overtime possession<x-score-sheet.cell label="First possession and overtime possession" /></label></div>
<h3>Score summary</h3><table class="ss-tall"><thead><tr><th>Team</th><th>Regular time</th><th>Overtime</th><th>Final score</th></tr></thead><tbody>@foreach(['A','B'] as $letter)<tr><th>{{ $letter }}</th>@foreach(['Regular time','Overtime','Final score'] as $period)<td><x-score-sheet.cell label="{{ $letter }} {{ $period }}" /></td>@endforeach</tr>@endforeach</tbody></table>
<div class="ss-note">Scoring notation: 1-point field goals / free throws; 2-point field goals beyond the arc. Record overtime separately. No quarter-by-quarter scoring.</div>
@include('sports.score-sheets.result')
<label class="ss-block">Unsportsmanlike / disqualifying fouls, injuries and remarks<x-score-sheet.cell label="3x3 game remarks" /></label>
