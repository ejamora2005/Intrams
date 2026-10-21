@csrf
<div class="grid gap-5 sm:grid-cols-2">
    <div><label class="text-sm font-medium">Sport name</label><input name="name" required value="{{ old('name', $sport->name) }}" class="mt-1 block w-full rounded-lg border-slate-300"><x-input-error for="name" class="mt-1" /></div>
    <div><label class="text-sm font-medium">Catalogue status</label><select name="status" class="mt-1 block w-full rounded-lg border-slate-300"><option value="active" @selected(old('status', $sport->status ?: 'active') === 'active')>Active</option><option value="inactive" @selected(old('status', $sport->status) === 'inactive')>Inactive</option></select></div>
    @if ($sport->isRelay())
        <div>
            <label class="text-sm font-medium" for="participant_category">Participant category</label>
            <select id="participant_category" disabled class="mt-1 block w-full rounded-lg border-slate-300"><option value="open" selected>Open</option></select>
            <input type="hidden" name="participant_category" value="open">
            <p class="mt-1 text-xs text-slate-500">Relay events accept all genders.</p>
        </div>
    @else
        <div><label class="text-sm font-medium" for="participant_category">Participant category</label><select id="participant_category" name="participant_category" class="mt-1 block w-full rounded-lg border-slate-300"><option value="male" @selected(old('participant_category', $sport->participant_category ?: 'open') === 'male')>Male</option><option value="female" @selected(old('participant_category', $sport->participant_category ?: 'open') === 'female')>Female</option><option value="mixed" @selected(old('participant_category', $sport->participant_category ?: 'open') === 'mixed')>Mixed</option><option value="open" @selected(old('participant_category', $sport->participant_category ?: 'open') === 'open')>Open</option><option value="custom" @selected(old('participant_category', $sport->participant_category ?: 'open') === 'custom')>Other / custom</option></select><p class="mt-1 text-xs text-slate-500">Male and Female categories enforce matching student gender. Mixed and Open accept all students.</p><x-input-error for="participant_category" class="mt-1" /></div>
    @endif
    <div id="participant-category-label"><label class="text-sm font-medium" for="participant_category_label">Custom category name</label><input id="participant_category_label" name="participant_category_label" value="{{ old('participant_category_label', $sport->participant_category === 'custom' ? $sport->getRawOriginal('participant_category_label') : '') }}" class="mt-1 block w-full rounded-lg border-slate-300" placeholder="e.g., Para sports"><x-input-error for="participant_category_label" class="mt-1" /></div>
    <div><label class="text-sm font-medium">Participant type</label><select name="participant_type" class="mt-1 block w-full rounded border"><option value="team" @selected(old('participant_type', $editionSport?->participant_type) === 'team')>Team</option><option value="dual" @selected(old('participant_type', $editionSport?->participant_type) === 'dual')>Dual</option><option value="individual" @selected(old('participant_type', $editionSport?->participant_type) === 'individual')>Individual</option></select></div>
    <div><label class="text-sm font-medium">Game mechanic</label>
        @if ($sport->isTrackAndField())
            <select disabled class="mt-1 block w-full rounded border bg-slate-100"><option selected>Custom / manual results</option></select>
            <input type="hidden" name="game_mechanic" value="custom">
            <p class="mt-1 text-xs text-slate-500">Track and field uses timed or measured results. It does not generate tournament brackets.</p>
        @else
            <select name="game_mechanic" class="mt-1 block w-full rounded border"><option value="round_robin" @selected(old('game_mechanic', $editionSport?->game_mechanic ?: 'round_robin') === 'round_robin')>Round robin (default)</option><option value="single_elimination" @selected(old('game_mechanic', $editionSport?->game_mechanic) === 'single_elimination')>Single elimination</option><option value="double_elimination" @selected(old('game_mechanic', $editionSport?->game_mechanic) === 'double_elimination')>Double elimination</option><option value="custom" @selected(old('game_mechanic', $editionSport?->game_mechanic) === 'custom')>Custom league schedule</option></select><p class="mt-1 text-xs text-slate-500">Round robin is generated automatically. Custom keeps your rules while using the same working league schedule.</p>
        @endif
    </div>
    <label class="text-sm font-medium">Edition event status<select name="configuration_status" required class="mt-1 block w-full rounded-lg border-slate-300"><option value="preparation" @selected(old('configuration_status', $editionSport?->status ?? 'preparation') === 'preparation')>Preparation</option><option value="active" @selected(old('configuration_status', $editionSport?->status) === 'active')>Active</option></select><x-input-error for="configuration_status" class="mt-1" /></label>
    <label class="text-sm font-medium sm:col-span-2">Description <span class="font-normal text-slate-500">(optional)</span><textarea name="description" rows="3" maxlength="2000" class="mt-1 block w-full rounded-lg border-slate-300">{{ old('description', $sport->description) }}</textarea><x-input-error for="description" class="mt-1" /></label>
    <label class="text-sm font-medium sm:col-span-2">Sport rules<textarea name="rules" rows="4" maxlength="5000" class="mt-1 block w-full rounded-lg border-slate-300">{{ old('rules', $editionSport?->rules) }}</textarea><x-input-error for="rules" class="mt-1" /></label>
</div>
<div class="mt-6"><x-linked-point-system :point-systems="$pointSystems" :selected-system="$selectedSystem" :current-rules="$currentPointRules" :has-result="$editionSport?->sportResult !== null" :allow-unlinked="$sport->exists && ! $selectedSystem" /></div>
<div class="mt-6 flex flex-wrap gap-3 border-t border-slate-100 pt-5"><button type="submit" data-loading-label="Saving sport..." class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white">{{ $submitLabel }}</button><a href="{{ route('admin.sports.index', ['edition_id' => $edition->id]) }}" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700">Cancel</a></div>
<script>
    const participantCategory = document.getElementById('participant_category');
    const participantCategoryLabel = document.getElementById('participant-category-label');
    const gameMechanic = document.querySelector('[name="game_mechanic"]');
    const toggleCustomCategory = () => participantCategoryLabel.classList.toggle('hidden', participantCategory.value !== 'custom');
    participantCategory.addEventListener('change', () => {
        toggleCustomCategory();
        if (participantCategory.value === 'custom' && gameMechanic?.value === 'round_robin') {
            gameMechanic.value = 'custom';
        }
    });
    toggleCustomCategory();
</script>
