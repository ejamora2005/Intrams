// Copy the properties used by the sheet, not hundreds of unrelated browser defaults
// for each of its cells and inputs. Keep geometry and native form control styling.
export const scoreSheetCaptureOptions = {
    pixelRatio: 2,
    backgroundColor: '#ffffff',
    skipFonts: true,
    style: { margin: '0', transform: 'none', boxShadow: 'none' },
    includeStyleProperties: [
        'display', 'position', 'box-sizing', 'width', 'height',
        'min-width', 'min-height', 'max-width', 'max-height',
        'top', 'right', 'bottom', 'left', 'float', 'clear',
        'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
        'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
        'border-top', 'border-right', 'border-bottom', 'border-left',
        'border-radius', 'border-collapse', 'border-spacing', 'table-layout',
        'font-family', 'font-size', 'font-weight', 'font-style', 'font-variant',
        'line-height', 'letter-spacing', 'word-spacing', 'white-space',
        'text-align', 'text-indent', 'text-transform', 'text-decoration',
        'text-overflow', 'vertical-align', 'word-break', 'overflow-wrap',
        'color', 'background-color', 'background-image', 'background-size',
        'background-position', 'background-repeat', 'background-origin',
        'background-clip', 'opacity', 'visibility', 'overflow',
        'appearance', '-webkit-appearance', '-webkit-text-fill-color',
        'object-fit', 'object-position', 'box-shadow', 'outline',
        'direction', 'writing-mode', 'color-scheme', 'transform', 'transform-origin',
    ],
};
