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
    list.addEventListener('change', refresh);
    list.addEventListener('click', (event) => {
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
