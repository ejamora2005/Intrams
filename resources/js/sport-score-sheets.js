import '../css/sport-score-sheets.css';

const form = document.querySelector('[data-sport-score-sheet]');
const source = document.getElementById('sport-score-sheet-data');
if (form && source) {
    const { teams } = JSON.parse(source.textContent);
    const perSide = Number(form.dataset.playersPerSide);
    const paper = form.querySelector('.ss-paper');
    const content = paper.querySelector('.ss-content');
    const footer = paper.querySelector('.ss-signatures');
    // Keep the full sport form above its signature area, including long event titles.
    // Scaling is also captured in exports; A4 paper itself is never resized.
    const fitSheet = () => {
        if (!content.offsetHeight) return;
        content.style.transform = 'none';
        content.style.marginLeft = '0';
        const available = footer.offsetTop - content.offsetTop - 8;
        const scale = Math.min(1, available / content.offsetHeight);
        content.style.transform = `translateX(${content.offsetWidth * (1 - scale) / 2}px) scale(${scale})`;
    };
    new ResizeObserver(fitSheet).observe(content);
    form.addEventListener('score-sheet:prepare', fitSheet);
    const measure = document.createElement('canvas').getContext('2d');
    const baseFonts = new WeakMap();
    const fitField = (field) => {
        if (!measure || !field.clientWidth) return;
        const style = getComputedStyle(field);
        if (!baseFonts.has(field)) baseFonts.set(field, parseFloat(style.fontSize));
        const fontSize = baseFonts.get(field);
        measure.font = `${fontSize}px Arial`;
        const space = field.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight) - 3;
        const width = measure.measureText(field.value).width;
        field.style.setProperty('font-size', `${Math.max(8, Math.min(fontSize, width ? fontSize * space / width : fontSize))}px`, 'important');
    };
    const fields = [...form.querySelectorAll('.ss-paper input')];
    fields.forEach((field) => field.addEventListener('input', () => fitField(field)));
    document.fonts.ready.then(() => { fields.forEach(fitField); fitSheet(); });
    const teamFor = (side) => teams.find((team) => String(team.id) === form.querySelector(`[data-sheet-team-picker="${side}"]`)?.value);
    const fillPlayers = (side, players) => {
        form.querySelectorAll(`[data-roster-side="${side}"]`).forEach((field) => {
            const player = players[Number(field.dataset.rosterRow)];
            field.value = player?.[field.dataset.rosterKind || 'name'] || '';
            fitField(field);
        });
    };
    const updateSelected = (side) => {
        const team = teamFor(side);
        const pickers = [...form.querySelectorAll(`[data-sheet-player-pickers="${side}"] select`)];
        fillPlayers(side, pickers.map((picker) => team?.players.find((player) => String(player.id) === picker.value)));
        const chosen = pickers.map((picker) => picker.value).filter(Boolean);
        pickers.forEach((picker) => [...picker.options].forEach((option) => { option.disabled = Boolean(option.value && option.value !== picker.value && chosen.includes(option.value)); }));
    };
    form.querySelectorAll('[data-sheet-team-picker]').forEach((picker) => {
        picker.addEventListener('change', () => {
            const side = picker.dataset.sheetTeamPicker;
            const team = teamFor(side);
            form.querySelectorAll(`[data-sheet-team-name="${side}"]`).forEach((field) => { field.value = team?.name || ''; fitField(field); });
            const container = form.querySelector(`[data-sheet-player-pickers="${side}"]`);
            if (!container) {
                fillPlayers(side, team?.players || []);
                const note = form.querySelector(`[data-sheet-roster-note="${side}"]`);
                const capacity = Math.max(0, ...[...form.querySelectorAll(`[data-roster-side="${side}"][data-roster-kind="name"]`)].map((field) => Number(field.dataset.rosterRow) + 1));
                if (note) note.textContent = team ? `${team.players.length} registered. ${team.players.length > capacity ? `This sheet has ${capacity} lines; edit the lineup or use another sheet for remaining players.` : 'Roster loaded. Edit the lineup on the sheet.'}` : '';
                return;
            }
            container.replaceChildren();
            for (let row = 0; row < perSide; row += 1) {
                const label = document.createElement('label');
                label.textContent = `Player ${row + 1}`;
                const select = document.createElement('select');
                select.setAttribute('aria-label', `${side.toUpperCase()} player ${row + 1}`);
                select.add(new Option('Choose registered player', ''));
                (team?.players || []).forEach((player) => select.add(new Option(player.name, String(player.id))));
                select.addEventListener('change', () => updateSelected(side));
                label.append(select);
                container.append(label);
            }
            fillPlayers(side, []);
        });
    });
    form.addEventListener('reset', () => {
        form.querySelectorAll('[data-sheet-player-pickers]').forEach((container) => container.replaceChildren());
        form.querySelectorAll('[data-sheet-roster-note]').forEach((note) => { note.textContent = ''; });
        requestAnimationFrame(() => fields.forEach(fitField));
    });
}
