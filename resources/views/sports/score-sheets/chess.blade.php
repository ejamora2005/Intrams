@include('sports.score-sheets.teams')
<div class="ss-grid ss-block">@foreach(['a'=>'White','b'=>'Black'] as $side=>$colour)<label>{{ $colour }} player<x-score-sheet.cell label="{{ $colour }} chess player" :side="$side" :row="0" kind="name" /></label>@endforeach</div>
<div class="ss-grid ss-block"><label>Board / section<x-score-sheet.cell label="Chess board and section" /></label><label>Time control / increment<x-score-sheet.cell label="Chess time control and increment" /></label></div>
<div class="ss-grid ss-block">
@foreach([0,40] as $offset)<table class="ss-chess"><thead><tr><th class="ss-move">Move</th><th>White</th><th>Black</th></tr></thead><tbody>@foreach(range(1,40) as $move)<tr><td class="ss-move">{{ $move+$offset }}.</td><td><x-score-sheet.cell label="Move {{ $move+$offset }} white" /></td><td><x-score-sheet.cell label="Move {{ $move+$offset }} black" /></td></tr>@endforeach</tbody></table>@endforeach
</div>
<div class="ss-grid ss-block"><label>Result (1–0 / 0–1 / ½–½)<x-score-sheet.cell label="Chess result" /></label><label>End time / continuation sheet no.<x-score-sheet.cell label="Chess end time and continuation sheet" /></label></div>
<div class="ss-grid ss-block"><label>White player signature<x-score-sheet.cell label="White player signature" /></label><label>Black player signature<x-score-sheet.cell label="Black player signature" /></label></div>
