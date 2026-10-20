<fieldset data-programme-game-row class="rounded-lg border border-slate-200 p-3">
    <legend data-programme-game-label class="px-1 text-sm font-semibold">Game {{ $index + 1 }}</legend>
    <div class="mb-3 flex justify-end">
        <button type="button" data-programme-game-remove class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Remove game</button>
    </div>
    @php $linkedIds = is_array($game['bracket_match_ids'] ?? null) ? array_map('intval', $game['bracket_match_ids']) : []; @endphp
    <label class="mb-3 block text-sm font-medium">Link bracket matches (optional)
        <input type="search" data-programme-bracket-search placeholder="Find sport, round, or team" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2 text-sm">
        <select multiple data-programme-bracket-match data-programme-game-field="bracket_match_ids" name="games[{{ $index }}][bracket_match_ids][]" class="mt-2 block min-h-28 w-full rounded-lg border-slate-300 text-sm">
            @foreach (($bracketOptions ?? []) as $matchId => $option)
                <option value="{{ $matchId }}" title="{{ $option['label'] }}" @selected(in_array((int) $matchId, $linkedIds, true)) data-home-name="{{ $option['home']['name'] }}" data-home-logo="{{ $option['home']['logo_path'] ? asset($option['home']['logo_path']) : '' }}" data-away-name="{{ $option['away']['name'] }}" data-away-logo="{{ $option['away']['logo_path'] ? asset($option['away']['logo_path']) : '' }}" data-start="{{ $option['starts_at'] }}" data-end="{{ $option['ends_at'] }}" data-venue="{{ $option['venue'] }}">{{ $option['label'] }}</option>
            @endforeach
        </select>
    </label>
    <button type="button" data-programme-bracket-clear class="mb-2 rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700">Clear bracket links</button>
    <p class="mb-3 text-xs text-slate-500">Select a match to follow its actual teams and winner. Select multiple for Men/Women divisions. Ctrl/Cmd-click to select or clear individual matches.</p>
    <input type="hidden" data-programme-game-field="manual_teams" name="games[{{ $index }}][manual_teams]" value="0">
    <label class="mb-3 flex items-start gap-2 text-sm"><input type="checkbox" data-programme-manual-teams data-programme-game-field="manual_teams" name="games[{{ $index }}][manual_teams]" value="1" @checked(!empty($game['manual_teams'])) class="mt-0.5 rounded border-slate-300"><span>Use my opponent choices below instead of the linked bracket teams</span></label>
    <div class="grid gap-3 sm:grid-cols-2">
        @foreach (['home' => 'First opponent', 'away' => 'Second opponent'] as $side => $sideLabel)
            <label class="block text-sm font-medium">{{ $sideLabel }}
                <select data-programme-game-side="{{ $side }}" name="games[{{ $index }}][{{ $side }}]" class="mt-1 block w-full rounded-lg border-slate-300 py-2 text-sm">
                    <option value="">To be announced</option>
                    @foreach ($teamOptions as $reference => $team)
                        <option value="{{ $reference }}" data-logo="{{ $team['logo_path'] ? asset($team['logo_path']) : '' }}" @selected(($game[$side] ?? '') === $reference)>{{ $team['name'] }}</option>
                    @endforeach
                    @for ($prior = 1; $prior <= 12; $prior++)
                        @foreach (['winner' => 'Winner', 'loser' => 'Loser'] as $type => $label)
                            <option value="{{ $type }}:{{ $prior }}" @selected(($game[$side] ?? '') === $type.':'.$prior) @disabled($prior >= $index + 1)>{{ $label }} of Game {{ $prior }}</option>
                        @endforeach
                    @endfor
                </select>
            </label>
        @endforeach
    </div>
    <div data-programme-game-preview class="programme-matchups"></div>
    <details class="mt-3 text-sm">
        <summary class="cursor-pointer font-semibold">Set linked bracket date, hours and venue</summary>
        <label class="mt-3 flex items-start gap-2"><input type="checkbox" data-programme-game-field="update_bracket_schedule" name="games[{{ $index }}][update_bracket_schedule]" value="1" @checked(!empty($game['update_bracket_schedule'])) class="mt-0.5 rounded border-slate-300"><span>Save these times to the selected bracket matches</span></label>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            @foreach (['bracket_starts_at' => 'Match starts (Philippine time)', 'bracket_ends_at' => 'Match ends (Philippine time)'] as $field => $label)
                <label class="block text-xs font-medium">{{ $label }}<input type="datetime-local" data-programme-game-field="{{ $field }}" name="games[{{ $index }}][{{ $field }}]" value="{{ is_string($game[$field] ?? null) ? $game[$field] : '' }}" class="mt-1 block w-full min-w-0 rounded-lg border-slate-300 text-sm"></label>
            @endforeach
            <label class="block text-xs font-medium sm:col-span-2">Match venue<input data-programme-game-field="bracket_venue" name="games[{{ $index }}][bracket_venue]" value="{{ is_string($game['bracket_venue'] ?? null) ? $game['bracket_venue'] : '' }}" maxlength="160" placeholder="Use public event venue" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
        </div>
    </details>
</fieldset>
