@include('sports.score-sheets.teams')
<label class="ss-block">Competition rules / scoring unit / periods<x-score-sheet.cell label="Custom scoring rules and units" /></label>
<h3>Competitor results</h3><table class="ss-tall"><thead><tr><th style="width:12mm">Place</th><th>Competitor / team</th><th>Round / attempt 1</th><th>Round / attempt 2</th><th>Round / attempt 3</th><th>Total / best</th><th>Status</th></tr></thead><tbody>@foreach(range(1,12) as $row)<tr>@foreach(['Place','Competitor','Round 1','Round 2','Round 3','Total','Status'] as $field)<td><x-score-sheet.cell label="Custom row {{ $row }} {{ $field }}" /></td>@endforeach</tr>@endforeach</tbody></table>
@include('sports.score-sheets.result')
<label class="ss-block">Remarks<x-score-sheet.cell label="Custom event remarks" /></label>
