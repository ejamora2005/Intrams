const pointSystem = document.querySelector('[data-cultural-point-system]');
const preview = document.querySelector('[data-cultural-points-preview]');

if (pointSystem && preview) {
    const render = () => {
        const rules = JSON.parse(pointSystem.selectedOptions[0]?.dataset.pointRules || '[]');
        const rows = rules.map((rule) => {
            const row = document.createElement('li');
            row.className = 'flex justify-between gap-3 text-sm';
            const label = document.createElement('span');
            label.className = 'text-slate-500';
            label.textContent = rule.label;
            const points = document.createElement('strong');
            points.className = 'text-slate-900';
            points.textContent = `${Number(rule.points).toFixed(2)} pts`;
            row.append(label, points);
            return row;
        });
        preview.replaceChildren(...rows);
    };
    pointSystem.addEventListener('change', render);
    render();
}
