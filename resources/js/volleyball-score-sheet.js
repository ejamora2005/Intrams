const form = document.querySelector('[data-volleyball-score-sheet]');
const teamsData = document.getElementById('volleyball-teams-data');

if (form && teamsData) {
    const teams = JSON.parse(teamsData.textContent);
    const teamsById = new Map(teams.map(team => [String(team.id), team]));
    teamsData.remove();

    form.querySelectorAll('[data-volleyball-team-picker]').forEach((picker) => {
        const side = picker.dataset.volleyballTeamPicker;
        const teamName = form.querySelector(`[data-volleyball-team-name="${side}"]`);
        const playerNames = form.querySelectorAll(`[data-volleyball-player-name="${side}"]`);
        const roster = form.querySelector(`[data-volleyball-roster="${side}"]`);

        picker.addEventListener('change', () => {
            const team = teamsById.get(picker.value);
            teamName.value = team?.name ?? '';
            playerNames.forEach((input, index) => {
                input.value = team?.players[index]?.sheet_name ?? '';
            });
            roster.textContent = team
                ? team.players.map(player => `${player.name}${player.course ? ` (${player.course})` : ''}`).join(' · ')
                : 'Select a team to load its active roster.';
        });
    });

    form.addEventListener('reset', () => {
        form.querySelectorAll('[data-volleyball-roster]').forEach((preview) => {
            preview.textContent = 'Select a team to load its active roster.';
        });
    });
}
