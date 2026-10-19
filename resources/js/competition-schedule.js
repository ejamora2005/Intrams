const form = document.querySelector('[data-create-schedule]');
const payload = document.getElementById('schedule-create-options');

if (form && payload) {
    const { events, selected } = JSON.parse(payload.textContent);
    const eventSelect = form.elements.edition_sport_id;
    const list = form.querySelector('[data-schedule-participants]');
    const help = form.querySelector('[data-schedule-participant-help]');
    const tools = form.querySelector('[data-schedule-participant-tools]');
    const search = form.querySelector('[data-schedule-participant-search]');
    const count = form.querySelector('[data-schedule-participant-count]');
    const noMatches = form.querySelector('[data-schedule-participant-empty]');

    const updateCount = () => {
        count.textContent = list.children.length ? `${list.querySelectorAll('input:checked').length} included` : '';
    };
    const filterParticipants = () => {
        const query = search.value.trim().toLowerCase();
        let visible = 0;
        [...list.children].forEach((label) => {
            label.hidden = !label.textContent.toLowerCase().includes(query);
            if (!label.hidden) visible += 1;
        });
        noMatches.hidden = list.children.length === 0 || visible > 0;
    };
    const renderEvent = (initial = false) => {
        const event = events[eventSelect.value];
        list.replaceChildren();
        search.value = '';
        noMatches.hidden = true;
        const cultural = event?.kind === 'cultural';
        form.elements.title.required = event?.kind === 'activity';
        form.querySelector('[data-schedule-title-optional]').textContent = form.elements.title.required ? '(required)' : '(optional)';
        if (event && (!initial || !form.elements.venue.value)) form.elements.venue.value = event.venue;
        tools.hidden = !event || cultural || event.participants.length === 0;

        help.textContent = !event ? 'Choose an event to see its registered teams, pairs, or athletes.'
            : cultural ? 'All active factions are included automatically for cultural competitions.'
                : event.kind === 'activity' ? 'School activities appear on the public schedule without sport registrations or competition points.'
                    : event.participants.length ? 'Select the teams, pairs, or athletes taking part. Leave unselected to announce participants later.'
                        : 'No registered participants are available yet. You can add this schedule with participants to be announced.';

        (event?.participants ?? []).forEach((participant) => {
            const label = document.createElement('label');
            label.className = 'flex min-w-0 cursor-pointer items-center gap-3 rounded-lg border border-slate-200 p-3 text-sm text-slate-700';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'participants[]';
            checkbox.value = participant.value;
            checkbox.checked = cultural || (initial && selected.includes(participant.value));
            checkbox.disabled = cultural;
            checkbox.className = 'h-4 w-4 shrink-0 rounded border-slate-300 text-blue-700 focus:ring-blue-600';
            const name = document.createElement('span');
            name.className = 'min-w-0 break-words';
            name.textContent = participant.label;
            label.append(checkbox, name);
            list.append(label);
        });
        updateCount();
    };

    eventSelect.addEventListener('change', () => renderEvent());
    list.addEventListener('change', updateCount);
    search.addEventListener('input', filterParticipants);
    form.querySelector('[data-schedule-select-all]').addEventListener('click', () => {
        list.querySelectorAll('label:not([hidden]) input:not(:disabled)').forEach((checkbox) => { checkbox.checked = true; });
        updateCount();
    });
    form.querySelector('[data-schedule-clear]').addEventListener('click', () => {
        list.querySelectorAll('input:not(:disabled)').forEach((checkbox) => { checkbox.checked = false; });
        updateCount();
    });
    renderEvent(true);
}
