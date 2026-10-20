document.querySelectorAll('[data-programme-editor]').forEach((form) => {
    const list = form.querySelector('[data-programme-game-list]');
    const template = form.querySelector('[data-programme-game-template]');
    const add = form.querySelector('[data-programme-game-add]');
    const empty = form.querySelector('[data-programme-game-empty]');
    const rows = () => [...list.querySelectorAll('[data-programme-game-row]')];
    const preview = (row, number) => {
        const wrap = row.querySelector('[data-programme-game-preview]');
        const game = document.createElement('div');
        game.className = 'programme-matchup';
        const label = document.createElement('span');
        label.className = 'programme-game-number'; label.textContent = `G${number}`;
        game.append(label);
        row.querySelectorAll('[data-programme-game-side]').forEach((select, index) => {
            if (index) {
                const vs = document.createElement('span'); vs.className = 'programme-game-vs'; vs.textContent = 'vs'; game.append(vs);
            }
            const team = document.createElement('span'); team.className = 'programme-game-team';
            const option = select.selectedOptions[0];
            if (option?.dataset.logo) {
                const img = document.createElement('img'); img.src = option.dataset.logo; img.alt = ''; img.width = 30; img.height = 30; team.append(img);
            } else {
                const placeholder = document.createElement('span'); placeholder.className = 'programme-game-placeholder';
                placeholder.textContent = select.value ? select.value.replace('winner:', 'W').replace('loser:', 'L') : 'TBA';
                team.append(placeholder);
            }
            const name = document.createElement('span'); name.textContent = option?.textContent ?? 'To be announced'; team.append(name); game.append(team);
        });
        wrap.replaceChildren(game);
        const linked = [...row.querySelector('[data-programme-bracket-match]').selectedOptions];
        if (linked.length && !row.querySelector('[data-programme-manual-teams]').checked) {
            wrap.replaceChildren();
            linked.forEach(option => {
                const item = game.cloneNode(true);
                [...item.querySelectorAll('.programme-game-team')].forEach((team, index) => {
                    const side = index ? 'away' : 'home';
                    team.replaceChildren();
                    if (option.dataset[`${side}Logo`]) {
                        const img = document.createElement('img'); img.src = option.dataset[`${side}Logo`]; img.alt = ''; img.width = 30; img.height = 30; team.append(img);
                    }
                    const name = document.createElement('span'); name.textContent = option.dataset[`${side}Name`]; team.append(name);
                });
                const caption = document.createElement('p'); caption.className = 'programme-game-link'; caption.textContent = option.textContent;
                wrap.append(caption, item);
            });
        }
    };
    const refresh = () => {
        rows().forEach((row, index) => {
            row.querySelector('[data-programme-game-label]').textContent = `Game ${index + 1}`;
            row.querySelectorAll('[data-programme-game-side]').forEach((select) => {
                select.name = `games[${index}][${select.dataset.programmeGameSide}]`;
                [...select.options].forEach((option) => {
                    const match = option.value.match(/^(?:winner|loser):(\d+)$/);
                    if (match) option.disabled = Number(match[1]) >= index + 1;
                });
            });
            row.querySelectorAll('[data-programme-game-field]').forEach(field => {
                field.name = `games[${index}][${field.dataset.programmeGameField}]${field.multiple ? '[]' : ''}`;
            });
            if (!row.dataset.bracketHydrated) {
                const option = row.querySelector('[data-programme-bracket-match]').selectedOptions[0];
                if (option) for (const [field, attribute] of [['bracket_starts_at', 'start'], ['bracket_ends_at', 'end'], ['bracket_venue', 'venue']]) {
                    const input = row.querySelector(`[data-programme-game-field="${field}"]`);
                    if (!input.value) input.value = option.dataset[attribute] ?? '';
                }
                row.dataset.bracketHydrated = 'true';
            }
            preview(row, index + 1);
        });
        empty.hidden = rows().length > 0;
        add.disabled = rows().length >= 12;
    };
    add.addEventListener('click', () => {
        if (rows().length >= 12) return;
        list.append(template.content.cloneNode(true)); refresh();
        rows().at(-1)?.querySelector('select')?.focus();
    });
    list.addEventListener('change', event => {
        if (event.target.matches('[data-programme-bracket-match]')) {
            const option = event.target.selectedOptions[0];
            const row = event.target.closest('[data-programme-game-row]');
            if (option) for (const [field, attribute] of [['bracket_starts_at', 'start'], ['bracket_ends_at', 'end'], ['bracket_venue', 'venue']]) {
                const input = row.querySelector(`[data-programme-game-field="${field}"]`);
                if (!input.value) input.value = option.dataset[attribute] ?? '';
            }
        }
        refresh();
    });
    list.addEventListener('input', event => {
        if (!event.target.matches('[data-programme-bracket-search]')) return;
        const query = event.target.value.toLowerCase();
        event.target.closest('[data-programme-game-row]').querySelectorAll('[data-programme-bracket-match] option').forEach(option => { option.hidden = !option.textContent.toLowerCase().includes(query) && !option.selected; });
    });
    list.addEventListener('click', (event) => {
        const clear = event.target.closest('[data-programme-bracket-clear]');
        if (clear) {
            const row = clear.closest('[data-programme-game-row]');
            row.querySelectorAll('[data-programme-bracket-match] option').forEach(option => { option.selected = false; });
            row.querySelector('[data-programme-game-field="update_bracket_schedule"]').checked = false;
            refresh(); return;
        }
        const button = event.target.closest('[data-programme-game-remove]');
        if (!button) return;
        const row = button.closest('[data-programme-game-row]');
        const removed = rows().indexOf(row) + 1;
        row.remove();
        list.querySelectorAll('select').forEach((select) => {
            const match = select.value.match(/^(winner|loser):(\d+)$/);
            if (!match) return;
            const referenced = Number(match[2]);
            if (referenced === removed) select.value = '';
            else if (referenced > removed) select.value = `${match[1]}:${referenced - 1}`;
        });
        refresh();
    });
    refresh();
});
