import { scoreSheetCaptureOptions } from './score-sheet-capture';

const form = document.querySelector('[data-score-sheet-export]');
const drawer = document.getElementById('score-sheet-download-drawer');

if (form && drawer) {
    const paper = form.querySelector('.score-sheet-paper');
    const sheet = form.querySelector('.fiba-sheet');
    const content = form.querySelector('.score-sheet-paper-content');
    const fitPaper = () => {
        if (!sheet || !content) return;
        const scale = Math.min(content.clientWidth / sheet.offsetWidth, content.clientHeight / sheet.offsetHeight);
        sheet.style.transform = `scale(${scale})`;
        sheet.style.left = `${(content.clientWidth - sheet.offsetWidth * scale) / 2}px`;
    };
    fitPaper();
    if (sheet) new ResizeObserver(fitPaper).observe(sheet);
    document.fonts.ready.then(fitPaper);
    const open = document.getElementById('open-score-sheet-download');
    open.addEventListener('click', () => drawer.showModal());
    document.getElementById('close-score-sheet-download').addEventListener('click', () => drawer.close());
    drawer.addEventListener('click', event => {
        if (event.target === drawer && event.clientX < drawer.getBoundingClientRect().left) drawer.close();
    });
    drawer.addEventListener('close', () => open.focus());
    let exporting = false;
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (exporting) return;
        const format = event.submitter?.value;
        if (!['docx', 'xlsx', 'pdf'].includes(format)) {
            if (!drawer.open) drawer.showModal();
            return;
        }
        exporting = true;
        const buttons = drawer.querySelectorAll('[name="format"]');
        buttons.forEach(button => button.disabled = true);
        const status = document.getElementById('score-sheet-export-status');
        status.textContent = 'Preparing ' + format.toUpperCase() + ' download…';
        try {
            const data = new FormData();
            data.append('_token', form.elements._token.value);
            data.append('edition_id', form.elements.edition_id.value);
            data.append('format', format);
                document.activeElement?.blur();
                await document.fonts.ready;
                await Promise.all([...paper.querySelectorAll('img')].map(img => img.decode()));
                fitPaper();
                const { toBlob } = await import('html-to-image');
                const image = await toBlob(paper, scoreSheetCaptureOptions);
                if (!image) throw new Error('Could not capture the sheet. Please try again.');
                data.append('preview_image', image, 'score-sheet.png');
            status.textContent = 'Generating ' + format.toUpperCase() + '…';
            const response = await fetch(form.action, { method: 'POST', body: data, headers: { Accept: 'application/json' } });
            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || 'Download failed. Please try again.');
            }
            const url = URL.createObjectURL(await response.blob());
            const link = document.createElement('a');
            link.href = url;
            link.download = form.dataset.downloadPrefix + '-' + form.elements.edition_id.value + '.' + format;
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(url), 300000);
            status.textContent = '';
            drawer.close();
        } catch (error) {
            status.textContent = error.message;
        } finally {
            exporting = false;
            buttons.forEach(button => button.disabled = false);
        }
    });
}
