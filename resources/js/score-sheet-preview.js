import '../css/score-sheet-preview.css';

document.querySelectorAll('[data-score-sheet-preview]').forEach((preview) => {
    const paper = preview.querySelector('.score-sheet-paper');
    const viewport = preview.querySelector('[data-preview-viewport]');
    const frame = preview.querySelector('[data-preview-frame]');
    const scaler = preview.querySelector('[data-preview-scaler]');
    if (!paper || !viewport || !frame || !scaler) return;
    const form = preview.closest('form');
    const output = preview.querySelector('[data-preview-scale]');
    const fit = preview.querySelector('[data-preview-fit]');
    const actual = preview.querySelector('[data-preview-actual]');
    const minus = preview.querySelector('[data-preview-zoom="out"]');
    const plus = preview.querySelector('[data-preview-zoom="in"]');
    let mode = 'fit';
    let scale = 1;
    const render = () => {
        // Only the surrounding display is scaled. The paper's physical dimensions,
        // inputs and internal sport layout remain unchanged for export and print.
        const paperStyle = getComputedStyle(paper);
        const width = parseFloat(paperStyle.width);
        const height = parseFloat(paperStyle.height);
        if (!viewport.clientWidth || !width || !height) return;
        const viewportStyle = getComputedStyle(viewport);
        const available = viewport.clientWidth - parseFloat(viewportStyle.paddingLeft) - parseFloat(viewportStyle.paddingRight);
        if (mode === 'fit') scale = Math.min(1, Math.max(.05, (available - 1) / width));
        scaler.style.width = `${width}px`;
        scaler.style.height = `${height}px`;
        scaler.style.transform = `scale(${scale})`;
        frame.style.width = `${width * scale}px`;
        frame.style.height = `${height * scale}px`;
        frame.dataset.previewReady = 'true';
        output.textContent = `${Math.round(scale * 100)}%`;
        fit.setAttribute('aria-pressed', String(mode === 'fit'));
        actual.setAttribute('aria-pressed', String(mode === 'manual' && scale === 1));
        minus.disabled = scale <= .2;
        plus.disabled = scale >= 2;
    };
    const setZoom = (next) => {
        mode = 'manual';
        scale = Math.max(.2, Math.min(2, next));
        render();
    };
    fit.addEventListener('click', () => { mode = 'fit'; render(); viewport.scrollTo(0, 0); });
    actual.addEventListener('click', () => setZoom(1));
    minus.addEventListener('click', () => setZoom((Math.ceil(scale * 4) - 1) / 4));
    plus.addEventListener('click', () => setZoom((Math.floor(scale * 4) + 1) / 4));
    let requested = false;
    const scheduleRender = () => {
        if (requested) return;
        requested = true;
        requestAnimationFrame(() => { requested = false; render(); });
    };
    const observer = new ResizeObserver(scheduleRender);
    observer.observe(viewport);
    observer.observe(paper);
    document.fonts.ready.then(scheduleRender);
    render();

    const clearPrint = () => {
        document.getElementById('score-sheet-print-root')?.remove();
        document.getElementById('score-sheet-page-style')?.remove();
        delete document.documentElement.dataset.scoreSheetPrinting;
    };
    const preparePrint = () => {
        clearPrint();
        if (paper.offsetWidth) form?.dispatchEvent(new Event('score-sheet:prepare'));
        const copy = paper.cloneNode(true);
        const originals = paper.querySelectorAll('input,textarea,select');
        copy.querySelectorAll('input,textarea,select').forEach((field, index) => {
            field.value = originals[index].value;
            if (field.tagName === 'INPUT') {
                field.setAttribute('value', originals[index].value);
                field.checked = originals[index].checked;
            }
        });
        const root = document.createElement('div');
        root.id = 'score-sheet-print-root';
        root.className = 'ops-dashboard';
        const workspace = document.createElement('div');
        workspace.className = 'ss-workspace';
        workspace.append(copy);
        root.append(workspace);
        const pageStyle = document.createElement('style');
        pageStyle.id = 'score-sheet-page-style';
        pageStyle.textContent = `@page { size: A4 ${preview.dataset.paperOrientation}; margin: 0; }`;
        document.head.append(pageStyle);
        document.body.append(root);
        document.documentElement.dataset.scoreSheetPrinting = 'true';
    };
    window.addEventListener('beforeprint', preparePrint);
    window.addEventListener('afterprint', clearPrint);
    preview.querySelector('[data-preview-print]').addEventListener('click', async () => {
        await document.fonts.ready;
        await Promise.all([...paper.querySelectorAll('img')].map((img) => img.decode().catch(() => {})));
        preparePrint();
        window.print();
    });
});
