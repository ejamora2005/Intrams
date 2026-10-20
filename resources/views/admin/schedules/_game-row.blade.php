<fieldset data-programme-game-row class="rounded-lg border border-slate-200 p-3">
    <legend data-programme-game-label class="px-1 text-sm font-semibold">Game {{ $index + 1 }}</legend>
    <div class="mb-3 flex justify-end">
        <button type="button" data-programme-game-remove class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Remove game</button>
    </div>
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
</fieldset>
