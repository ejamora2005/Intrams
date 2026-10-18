@props(['orientation' => 'portrait'])
<section class="score-sheet-preview" data-score-sheet-preview data-paper-orientation="{{ $orientation === 'landscape' ? 'landscape' : 'portrait' }}" aria-label="Scoresheet preview controls">
    <div class="score-sheet-preview-toolbar">
        <div class="score-sheet-preview-zoom" role="group" aria-label="Preview zoom">
            <button type="button" data-preview-zoom="out" aria-label="Zoom out">−</button>
            <output data-preview-scale aria-live="polite" aria-label="Current preview zoom">Fit width</output>
            <button type="button" data-preview-zoom="in" aria-label="Zoom in">+</button>
        </div>
        <div class="score-sheet-preview-actions" role="group" aria-label="Preview size and print">
            <button type="button" data-preview-fit aria-pressed="true">Fit width</button>
            <button type="button" data-preview-actual aria-pressed="false">100%</button>
            <button type="button" data-preview-print aria-label="Print A4 sheet">Print</button>
        </div>
    </div>
    <p class="score-sheet-preview-help">Zoom in to fill small fields. Printing and downloads stay A4 {{ $orientation === 'landscape' ? 'landscape' : 'portrait' }}.</p>
    <div class="score-sheet-preview-viewport" data-preview-viewport tabindex="0" role="region" aria-label="Scrollable scoresheet preview">
        <div class="score-sheet-preview-frame" data-preview-frame>
            <div class="score-sheet-preview-scaler" data-preview-scaler>{{ $slot }}</div>
        </div>
    </div>
</section>
