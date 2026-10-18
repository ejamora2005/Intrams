@include('sports.score-sheets.teams')
<div class="ss-grid ss-block">@foreach(['a'=>'A','b'=>'B'] as $side=>$letter)<div><h3>{{ $letter }} · {{ $profile['players_per_side']===2 ? 'Doubles' : 'Singles' }}</h3>@for($row=0;$row<$profile['players_per_side'];$row++)<label>Player {{ $row+1 }}<x-score-sheet.cell label="{{ $letter }} badminton player {{ $row+1 }}" :side="$side" :row="$row" kind="name" /></label>@endfor</div>@endforeach</div>
@foreach(range(1,3) as $game)
<section class="ss-block"><h3>Game {{ $game }} · service sequence</h3>
<table class="ss-score-grid"><thead><tr><th style="width:18mm">Side / S-R</th>@foreach(range(0,30) as $point)<th>{{ $point }}</th>@endforeach<th style="width:15mm">Score</th></tr></thead><tbody>
@foreach(['A','B'] as $letter)<tr><th>{{ $letter }} <x-score-sheet.cell label="Badminton game {{ $game }} {{ $letter }} starting server or receiver" placeholder="S / R" /></th>@foreach(range(0,30) as $point)<td><x-score-sheet.cell label="Badminton game {{ $game }} {{ $letter }} score {{ $point }}" /></td>@endforeach<td><x-score-sheet.cell label="Badminton game {{ $game }} {{ $letter }} final score" /></td></tr>@endforeach
</tbody></table><div class="ss-grid ss-three"><label>Start time<x-score-sheet.cell label="Badminton game {{ $game }} start time" /></label><label>End time / duration<x-score-sheet.cell label="Badminton game {{ $game }} end time and duration" /></label><label>Interval / change of ends / notes<x-score-sheet.cell label="Badminton game {{ $game }} interval and change of ends" /></label></div></section>
@endforeach
<div class="ss-grid ss-three ss-block"><label>Toss / first server / receiving court<x-score-sheet.cell label="Badminton toss and first server" /></label><label>Shuttles used<x-score-sheet.cell label="Shuttles used" /></label><label>Service judge<x-score-sheet.cell label="Service judge" /></label></div>
<label class="ss-block">Warnings, faults, injuries and remarks<x-score-sheet.cell label="Badminton warnings and remarks" /></label>
@include('sports.score-sheets.result')
