@extends('layouts.admin', ['title' => 'Add athletes', 'subtitle' => 'Choose a sport first. Team and dual events only show athletes on the selected team roster.'])

@section('content')
    <form method="POST" action="{{ route('admin.registrations.store') }}" class="max-w-3xl rounded-xl border bg-white p-6">
        @csrf
        <div class="grid gap-5">
            <div>
                <label for="sport_id" class="text-sm font-medium">Sport</label>
                <select id="sport_id" class="mt-1 block w-full rounded-lg border-slate-300" required>
                    <option value="">Select sport</option>
                    @foreach ($events->pluck('sport')->unique('id')->sortBy('name') as $sport)
                        <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                    @endforeach
                </select>
            </div>
            <div id="event-field" class="hidden">
                <label for="event_id" class="text-sm font-medium">Event</label>
                <select id="event_id" name="event_id" class="mt-1 block w-full rounded-lg border-slate-300" disabled required><option value="">Select event</option></select>
            </div>
            <div id="team-field" class="hidden">
                <label for="team_id" class="text-sm font-medium">Team</label>
                <select id="team_id" name="team_id" class="mt-1 block w-full rounded-lg border-slate-300" disabled><option value="">Select team</option></select>
            </div>
            <div id="athlete-field" class="hidden">
                <div class="flex items-center justify-between gap-4"><label class="text-sm font-medium">Athletes <span id="selection-hint" class="font-normal text-slate-500"></span></label><input id="athlete-search" type="search" class="w-52 rounded-lg border-slate-300 text-sm" placeholder="Search name or student no."></div>
                <p id="athlete-empty" class="mt-3 text-sm text-slate-500">Choose an event to see athletes.</p>
                <div id="athlete-list" class="mt-3 max-h-72 divide-y overflow-y-auto rounded-lg border"></div>
            </div>
        </div>
        <button id="submit-button" disabled class="mt-6 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Add athlete</button>
    </form>

    <script>
        const events = @json($eventOptions);
        const students = @json($studentOptions);
        const teams = @json($teamOptions);
        const sportSelect = document.getElementById('sport_id'), eventSelect = document.getElementById('event_id'), teamSelect = document.getElementById('team_id');
        const eventField = document.getElementById('event-field'), teamField = document.getElementById('team-field'), athleteField = document.getElementById('athlete-field');
        const athleteList = document.getElementById('athlete-list'), athleteEmpty = document.getElementById('athlete-empty'), athleteSearch = document.getElementById('athlete-search');
        const selectionHint = document.getElementById('selection-hint'), submitButton = document.getElementById('submit-button');
        const selectedEvent = () => events.find(event => event.id === Number(eventSelect.value));
        const selectedTeam = () => teams.find(team => team.id === Number(teamSelect.value));
        const usesTeam = () => ['team', 'dual'].includes(selectedEvent()?.type);

        function clearAthletes(message = 'Choose an event to see athletes.') { athleteList.innerHTML = ''; athleteEmpty.textContent = message; athleteEmpty.classList.remove('hidden'); athleteField.classList.add('hidden'); submitButton.disabled = true; }
        function validateSelection() { const event = selectedEvent(); if (event?.type === 'dual' && athleteList.querySelectorAll('input:checked').length > 2) athleteList.querySelectorAll('input:checked').forEach((input, index) => { if (index > 1) input.checked = false; }); const count = athleteList.querySelectorAll('input:checked').length; submitButton.disabled = !event || (event.type === 'dual' ? count !== 2 : count < 1); }
        function renderAthletes() {
            const event = selectedEvent();
            if (!event || (usesTeam() && !selectedTeam())) return clearAthletes(usesTeam() ? 'Choose a team to see its roster.' : 'Choose an event to see athletes.');
            const allowedIds = usesTeam() ? selectedTeam().student_ids : students.map(student => student.id);
            const choices = students.filter(student => allowedIds.includes(student.id));
            athleteField.classList.remove('hidden'); athleteEmpty.classList.toggle('hidden', choices.length > 0); athleteEmpty.textContent = choices.length ? '' : 'No active athletes are assigned to this team.';
            selectionHint.textContent = event.type === 'dual' ? '(select exactly 2)' : event.type === 'team' ? '(select one or more)' : '(select 1)';
            submitButton.textContent = event.type === 'dual' ? 'Add dual pair' : event.type === 'team' ? 'Add athletes' : 'Add athlete';
            athleteList.innerHTML = choices.map(student => `<label class="athlete-row flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-slate-50" data-search="${`${student.name} ${student.number}`.toLowerCase()}"><input type="${event.type === 'individual' ? 'radio' : 'checkbox'}" name="student_ids[]" value="${student.id}" class="rounded border-slate-300 text-blue-700"><span><span class="block font-medium">${student.name}</span><span class="text-xs text-slate-500">${student.number}</span></span></label>`).join('');
            validateSelection();
        }
        sportSelect.addEventListener('change', () => { const choices = events.filter(event => event.sport_id === Number(sportSelect.value)); eventSelect.innerHTML = '<option value="">Select event</option>' + choices.map(event => `<option value="${event.id}">${event.name} (${event.type})</option>`).join(''); eventSelect.disabled = !choices.length; eventField.classList.toggle('hidden', !sportSelect.value); teamField.classList.add('hidden'); teamSelect.disabled = true; teamSelect.value = ''; clearAthletes(); });
        eventSelect.addEventListener('change', () => { const event = selectedEvent(); if (!event) return clearAthletes(); const choices = teams.filter(team => team.edition_id === event.edition_id); teamSelect.innerHTML = '<option value="">Select team</option>' + choices.map(team => `<option value="${team.id}">${team.name}</option>`).join(''); teamSelect.value = ''; teamSelect.disabled = !usesTeam(); teamField.classList.toggle('hidden', !usesTeam()); renderAthletes(); });
        teamSelect.addEventListener('change', renderAthletes); athleteList.addEventListener('change', validateSelection);
        athleteSearch.addEventListener('input', () => { const query = athleteSearch.value.trim().toLowerCase(); athleteList.querySelectorAll('.athlete-row').forEach(row => row.classList.toggle('hidden', !row.dataset.search.includes(query))); });
    </script>
@endsection
