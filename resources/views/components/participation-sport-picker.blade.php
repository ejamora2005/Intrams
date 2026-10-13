@props([
    'sports' => collect(),
    'teamSportNote' => 'Team-sport selections are assigned to the student’s current faction.',
])

@if ($sports->isNotEmpty())
    @php($selectedSportIds = collect(old('edition_sport_ids', []))->map(fn ($id) => (string) $id))

    <fieldset data-sport-picker class="mt-7 border-t border-slate-100 pt-6">
        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
            <div>
                <legend class="text-sm font-semibold text-slate-800">Sports joined <span class="text-red-600">*</span></legend>
                <p class="mt-1 text-sm text-slate-500">Choose the sports this student will join in the active intramurals edition. The participation rules are applied as you select.</p>
            </div>
            <p data-sport-picker-summary class="text-xs font-medium text-slate-600" aria-live="polite"></p>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach ($sports as $editionSport)
                @php($sport = $editionSport->sport)
                @php($slot = $editionSport->participation_slot)
                <label data-sport-option class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4 transition hover:border-blue-300 hover:bg-blue-50">
                    <input
                        type="checkbox"
                        name="edition_sport_ids[]"
                        value="{{ $editionSport->id }}"
                        data-sport-choice
                        data-slot="{{ $slot }}"
                        @checked($selectedSportIds->contains((string) $editionSport->id))
                        class="mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-600"
                    >
                    <span>
                        <span class="block font-medium text-slate-900">{{ $sport?->name }}</span>
                        <span class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-700">{{ $slot === 'individual_or_dual' ? 'Individual / dual' : ucfirst($slot) }}</span>
                            <span>{{ ucfirst($editionSport->participant_type) }} event</span>
                            @if (in_array($sport?->participant_category, ['male', 'female'], true))
                                <span>{{ $sport->participant_category_label }}</span>
                            @endif
                        </span>
                    </span>
                </label>
            @endforeach
        </div>

        <p class="mt-3 text-xs text-slate-500">Allowed combinations: 1 Major + 1 Minor, 1 Major + 1 Individual/Dual, 1 Minor + 1 Individual/Dual, or up to 3 Individual/Dual events. A second Major or Minor is locked after the first selection. {{ $teamSportNote }} Dual events are assigned separately with both partners.</p>
        <p data-sport-picker-client-error hidden class="mt-2 text-sm text-red-600">Choose at least one sport.</p>
        @error('edition_sport_ids')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        @error('edition_sport_ids.*')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </fieldset>

    <script>
        (() => {
            const picker = document.querySelector('[data-sport-picker]');
            if (!picker) return;

            const inputs = [...picker.querySelectorAll('[data-sport-choice]')];
            const summary = picker.querySelector('[data-sport-picker-summary]');
            const error = picker.querySelector('[data-sport-picker-client-error]');
            const form = picker.closest('form');
            const emptyCounts = () => ({ major: 0, minor: 0, individual_or_dual: 0 });
            const selectedCounts = () => inputs.filter(input => input.checked).reduce((counts, input) => {
                counts[input.dataset.slot] = (counts[input.dataset.slot] || 0) + 1;
                return counts;
            }, emptyCounts());
            const total = counts => Object.values(counts).reduce((sum, count) => sum + count, 0);
            const canUse = (counts, slot) => {
                const next = { ...counts, [slot]: (counts[slot] || 0) + 1 };
                const maximum = next.major || next.minor ? 2 : 3;

                return next.major <= 1 && next.minor <= 1 && total(next) <= maximum;
            };

            const sync = () => {
                const counts = selectedCounts();

                inputs.forEach(input => {
                    if (input.checked) return;

                    const allowed = canUse(counts, input.dataset.slot);
                    input.disabled = !allowed;
                    input.closest('[data-sport-option]').classList.toggle('cursor-not-allowed', !allowed);
                    input.closest('[data-sport-option]').classList.toggle('opacity-50', !allowed);
                });

                summary.textContent = `Selected: Major ${counts.major} / Minor ${counts.minor} / Individual-Dual ${counts.individual_or_dual}`;
                if (inputs.some(input => input.checked)) error.hidden = true;
            };

            inputs.forEach(input => input.addEventListener('change', sync));
            form?.addEventListener('submit', event => {
                if (inputs.some(input => input.checked)) return;

                event.preventDefault();
                error.hidden = false;
                inputs[0]?.focus();
            });

            sync();
        })();
    </script>
@endif
