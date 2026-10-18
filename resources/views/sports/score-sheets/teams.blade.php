<div class="ss-grid ss-block">
    @foreach(['a' => ($profile['key'] === 'chess' ? 'White faction' : 'Team A'), 'b' => ($profile['key'] === 'chess' ? 'Black faction' : 'Team B')] as $side => $sideLabel)
        <label>{{ $sideLabel }}<x-score-sheet.cell :label="$sideLabel" data-sheet-team-name="{{ $side }}" /></label>
    @endforeach
</div>
