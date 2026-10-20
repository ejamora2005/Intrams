@props([
    'studentGender' => null,
    'selectedIds' => [],
    'sports' => collect(),
    'teamSportNote' => 'Team-sport selections are assigned to the student’s current faction.',
])

@php
    $studentGender = old('gender', $studentGender ?? null);
    $selectedIds = $selectedIds ?? [];
    $sports = $sports->values();
    $selectedSportIds = collect(old('edition_sport_ids', $selectedIds))->map(fn ($id) => (string) $id);
@endphp

@if ($sports->isNotEmpty())

    <fieldset data-sport-picker data-list-search-container class="mt-7 border-t border-slate-100 pt-6">
        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
            <div>
                <legend class="text-sm font-semibold text-slate-800">Sports joined <span class="text-red-600">*</span></legend>
                <p class="mt-1 text-sm text-slate-500">Choose all sports this student will join. Participation-rule violations are saved and flagged on the Possible DQ Watchlist.</p>
            </div>
            <p data-sport-picker-summary class="text-xs font-medium text-slate-600" aria-live="polite"></p>
        </div>

        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <label class="relative block min-w-0 flex-1 sm:max-w-md">
                <span class="sr-only">Search sports</span>
                <input type="search" data-list-search-input placeholder="Search sports" class="block w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm focus:border-blue-600 focus:ring-blue-600">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4.5 4.5"/></svg>
            </label>
            <span data-list-search-count class="text-xs font-medium text-slate-500" aria-live="polite"></span>
        </div>

        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            @foreach ($sports as $editionSport)
                @php
                    $sport = $editionSport->sport;
                    $slot = $editionSport->participation_slot;
                    $countsTowardLimit = $editionSport->counts_toward_participation_limit;
                    $isRelay = $editionSport->is_relay_event;
                    $pairedPartnerExemptGenders = $editionSport->paired_partner_exempt_genders ?? [];
                    $isDual = $editionSport->participant_type === 'dual';
                    $defaultSlotLabel = $isRelay ? 'Relay (Individual/Dual)' : ($slot === 'individual_or_dual' ? 'Individual / dual' : ucfirst($slot));
                @endphp
                <label data-sport-option data-list-search-row data-search-text="{{ Str::lower($sport?->name ?? '') }}" data-sport-category="{{ $sport?->participant_category }}" class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4 transition hover:border-blue-300 hover:bg-blue-50">
                    <input
                        type="checkbox"
                        name="edition_sport_ids[]"
                        value="{{ $editionSport->id }}"
                        data-sport-choice
                        data-slot="{{ $slot }}"
                        data-counts-toward-limit="{{ $countsTowardLimit ? 'true' : 'false' }}"
                        data-relay="{{ $isRelay ? 'true' : 'false' }}"
                        data-participation-key="{{ $isRelay ? $sport->relayParticipationKey() : 'event:'.$editionSport->id }}"
                        data-qualifying-run="{{ in_array(Str::upper($sport?->code ?? ''), config('intramurals.participation_rules.relay_qualifying_run_sport_codes', []), true) ? 'true' : 'false' }}"
                        data-paired-partner-exempt-for="{{ implode(',', $pairedPartnerExemptGenders) }}"
                        @checked($selectedSportIds->contains((string) $editionSport->id))
                        class="mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-600"
                    >
                    <span>
                        <span class="block font-medium text-slate-900">{{ $sport?->name }}</span>
                        <span class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500">
                            <span data-sport-slot-label data-default-slot-label="{{ $defaultSlotLabel }}" class="rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-700">{{ $defaultSlotLabel }}</span>
                            <span>{{ ucfirst($editionSport->participant_type) }} event</span>
                            @if ($isRelay || in_array($sport?->participant_category, ['male', 'female'], true))
                                <span>{{ $sport->participant_category_label }}</span>
                            @endif
                            @if ($isDual)
                                <span>Partner assignments are managed in sport management</span>
                            @endif
                        </span>
                    </span>
                </label>
            @endforeach
            <p data-list-search-empty hidden class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 sm:col-span-2">No sports match your search.</p>
        </div>

        <p data-sport-picker-warning hidden role="status" aria-live="polite" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"></p>
        <p class="mt-3 text-xs text-slate-500">Allowed combinations: 1 Major + 1 Minor, 1 Major + 1 Individual/Dual, 1 Minor + 1 Individual/Dual, or up to 3 Individual/Dual events. You can select additional sports; violations are flagged for Possible DQ review after saving. Relays are Open. With a 100m, 200m, or 400m run, relays do not use an event slot. Without one, each relay distance counts once as Individual/Dual; duplicate relay entries do not add another slot. {{ $teamSportNote }} Doubles selections can be added or removed here; existing partner assignments are kept while selected.</p>
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
            const warning = picker.querySelector('[data-sport-picker-warning]');
            const maxEvents = @json(config('intramurals.participation_rules.max_events_per_student', 2));
            const individualMaxEvents = @json(config('intramurals.participation_rules.individual_dual_only_max_events', 3));
            const allowedCombinations = @json(config('intramurals.participation_rules.allowed_combinations', []));
            const form = picker.closest('form');
            const genderInput = form?.querySelector('[name="gender"]');
            const emptyCounts = () => ({ major: 0, minor: 0, individual_or_dual: 0 });
            const isPairedPartnerExempt = input => (input.dataset.pairedPartnerExemptFor || '').split(',').filter(Boolean).includes((genderInput?.value || '').toLowerCase());
            const hasQualifyingRun = () => inputs.some(input => input.checked && input.dataset.qualifyingRun === 'true');
            const countsTowardLimit = input => !isPairedPartnerExempt(input) && (input.dataset.relay === 'true' ? !hasQualifyingRun() : input.dataset.countsTowardLimit === 'true');
            const uniqueChoices = choices => [...new Map(choices.map(input => [input.dataset.participationKey, input])).values()];
            const selectedCounts = () => uniqueChoices(inputs.filter(input => input.checked && countsTowardLimit(input))).reduce((counts, input) => {
                counts[input.dataset.slot] = (counts[input.dataset.slot] || 0) + 1;
                return counts;
            }, emptyCounts());
            const selectedRelayCount = () => uniqueChoices(inputs.filter(input => input.checked && input.dataset.relay === 'true')).length;
            const selectedPairedPartnerCount = () => inputs.filter(input => input.checked && isPairedPartnerExempt(input)).length;
            const total = counts => Object.values(counts).reduce((sum, count) => sum + count, 0);
            const sync = () => {
                const gender = (genderInput?.value || '').toLowerCase();

                inputs.forEach(input => {
                    const option = input.closest('[data-sport-option]');
                    const category = option.dataset.sportCategory || '';
                    const genderAllowed = !['male', 'female'].includes(category) || !gender || category === gender;
                    option.hidden = !genderAllowed;
                    if (!genderAllowed) input.checked = false;
                });
                const counts = selectedCounts();

                inputs.forEach(input => {
                    const option = input.closest('[data-sport-option]');
                    const category = option.dataset.sportCategory || '';
                    const genderAllowed = !['male', 'female'].includes(category) || !gender || category === gender;
                    const slotLabel = option.querySelector('[data-sport-slot-label]');
                    if (slotLabel) slotLabel.textContent = input.dataset.relay === 'true' ? (hasQualifyingRun() ? 'Relay' : 'Relay (Individual/Dual)') : (isPairedPartnerExempt(input) ? 'Paired partner (exempt)' : slotLabel.dataset.defaultSlotLabel);
                    input.disabled = !genderAllowed;
                    option.classList.toggle('cursor-not-allowed', !genderAllowed);
                    option.classList.toggle('opacity-50', !genderAllowed);
                });

                summary.textContent = `Selected: Major ${counts.major} / Minor ${counts.minor} / Individual-Dual ${counts.individual_or_dual} / Relay ${selectedRelayCount()} (${hasQualifyingRun() ? 'not counted' : 'included in Individual-Dual'}) / Paired partner ${selectedPairedPartnerCount()} (exempt)`;
                const maximum = counts.major || counts.minor ? maxEvents : individualMaxEvents;
                const issues = [];
                if (total(counts) > maximum) issues.push(`More than ${maximum} events selected.`);
                if (counts.major > 1) issues.push('More than 1 major event.');
                if (counts.minor > 1) issues.push('More than 1 minor event.');
                if (total(counts) === maximum && !allowedCombinations.some(combination => Object.keys(counts).every(slot => Number(combination[slot] || 0) === counts[slot]))) issues.push('Event combination does not match the allowed pairings.');
                warning.hidden = issues.length === 0;
                warning.textContent = issues.length ? `Possible DQ: ${issues.join(' ')} You can still save these sports. This student will appear on the Possible DQ Watchlist.` : '';
                if (inputs.some(input => input.checked)) error.hidden = true;
                picker.dispatchEvent(new Event('list-search:refresh'));
            };

            inputs.forEach(input => input.addEventListener('change', sync));
            genderInput?.addEventListener('change', sync);
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
