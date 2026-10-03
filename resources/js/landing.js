import './pwa';

const bannerCloths = [...document.querySelectorAll('.banner-cloth')];

if (bannerCloths.length) {
    const svgNamespace = 'http://www.w3.org/2000/svg';
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const frameInterval = 1000 / 24;

    const svgElement = (name, attributes = {}) => {
        const element = document.createElementNS(svgNamespace, name);
        Object.entries(attributes).forEach(([attribute, value]) => {
            element.setAttribute(attribute, String(value));
        });
        return element;
    };

    const filterHost = svgElement('svg', {
        width: 0,
        height: 0,
        'aria-hidden': 'true',
        focusable: 'false',
    });
    filterHost.style.position = 'absolute';
    filterHost.style.pointerEvents = 'none';
    const definitions = svgElement('defs');
    filterHost.append(definitions);

    // The transparent top fifth keeps the rod attachment still. The alpha ramp
    // blends the moving noise into a neutral displacement map toward the hem.
    const rampSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100" preserveAspectRatio="none"><defs><linearGradient id="ramp" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="white" stop-opacity="0"/><stop offset=".2" stop-color="white" stop-opacity="0"/><stop offset=".58" stop-color="white" stop-opacity=".4"/><stop offset="1" stop-color="white" stop-opacity="1"/></linearGradient></defs><rect width="100" height="100" fill="url(#ramp)"/></svg>';
    const rampUrl = `data:image/svg+xml;base64,${window.btoa(rampSvg)}`;

    const banners = bannerCloths.map((cloth, index) => {
        const id = `landing-fabric-wave-${index}`;
        const filter = svgElement('filter', {
            id,
            x: '-4%',
            y: '0%',
            width: '108%',
            height: '105%',
            'color-interpolation-filters': 'sRGB',
        });
        const noise = svgElement('feTurbulence', {
            type: 'fractalNoise',
            baseFrequency: '0.016 0.029',
            numOctaves: 2,
            seed: 13 + index * 29,
            result: 'fabric-noise',
        });
        const travelingNoise = svgElement('feOffset', {
            in: 'fabric-noise',
            dx: 0,
            dy: 0,
            result: 'traveling-noise',
        });
        const ramp = svgElement('feImage', {
            href: rampUrl,
            x: 0,
            y: 0,
            width: '100%',
            height: '100%',
            preserveAspectRatio: 'none',
            result: 'hem-ramp',
        });
        const maskedNoise = svgElement('feComposite', {
            in: 'traveling-noise',
            in2: 'hem-ramp',
            operator: 'in',
            result: 'weighted-noise',
        });
        const neutral = svgElement('feFlood', {
            'flood-color': '#808080',
            result: 'neutral',
        });
        const displacementMap = svgElement('feMerge', { result: 'fabric-map' });
        displacementMap.append(
            svgElement('feMergeNode', { in: 'neutral' }),
            svgElement('feMergeNode', { in: 'weighted-noise' }),
        );
        const displacement = svgElement('feDisplacementMap', {
            in: 'SourceGraphic',
            in2: 'fabric-map',
            scale: 3,
            xChannelSelector: 'R',
            yChannelSelector: 'G',
        });
        filter.append(noise, travelingNoise, ramp, maskedNoise, neutral, displacementMap, displacement);
        definitions.append(filter);

        return {
            cloth,
            id,
            noise,
            travelingNoise,
            displacement,
            duration: 8.4 + index * 1.7,
            phase: 0.9 + index * 2.1,
            originalFilter: cloth.style.filter,
        };
    });

    let animationFrame = null;
    let lastDraw = -Infinity;
    let activeSince = null;
    let elapsed = 0;
    let pageActive = true;

    const draw = timestamp => {
        if (timestamp - lastDraw >= frameInterval) {
            const seconds = (elapsed + timestamp - activeSince) / 1000;
            banners.forEach(({ noise, travelingNoise, displacement, duration, phase }) => {
                const wave = seconds * (Math.PI * 2 / duration) + phase;
                const gust = Math.pow((1 + Math.sin(wave * 0.32 + phase)) / 2, 6);
                const frequencyX = 0.016 + Math.sin(wave * 0.71) * 0.0015;
                const frequencyY = 0.029 + Math.cos(wave * 0.53) * 0.002;

                // Keep each seed fixed: smoothly translated and stretched noise
                // creates traveling folds without suddenly changing their shape.
                noise.setAttribute('baseFrequency', `${frequencyX.toFixed(5)} ${frequencyY.toFixed(5)}`);
                travelingNoise.setAttribute('dx', (Math.sin(wave * 0.67) * 3).toFixed(2));
                travelingNoise.setAttribute('dy', (Math.sin(wave * 0.48) * 9).toFixed(2));
                displacement.setAttribute('scale', (3 + Math.sin(wave) ** 2 * 1.4 + gust * 1.6).toFixed(2));
            });
            lastDraw = timestamp;
        }
        animationFrame = window.requestAnimationFrame(draw);
    };

    const syncAnimation = () => {
        const enabled = pageActive && !document.hidden && !reducedMotion.matches;

        if (enabled && animationFrame === null) {
            document.body.append(filterHost);
            banners.forEach(({ cloth, id }) => {
                cloth.style.filter = `url(#${id})`;
            });
            activeSince = performance.now();
            lastDraw = -Infinity;
            animationFrame = window.requestAnimationFrame(draw);
        } else if (!enabled) {
            if (animationFrame !== null) {
                window.cancelAnimationFrame(animationFrame);
                elapsed += performance.now() - activeSince;
                animationFrame = null;
                activeSince = null;
            }
            banners.forEach(({ cloth, originalFilter }) => {
                if (originalFilter) cloth.style.filter = originalFilter;
                else cloth.style.removeProperty('filter');
            });
            filterHost.remove();
        }
    };

    reducedMotion.addEventListener('change', syncAnimation);
    document.addEventListener('visibilitychange', syncAnimation);
    window.addEventListener('pagehide', () => {
        pageActive = false;
        syncAnimation();
    });
    window.addEventListener('pageshow', () => {
        pageActive = true;
        syncAnimation();
    });
    syncAnimation();
}

const resultsControl = document.querySelector('.results-control');
const resultsToggle = resultsControl?.querySelector('[data-results-toggle]');
const resultsPopover = resultsControl?.querySelector('[data-results-popover]');

if (resultsToggle && resultsPopover) {
    const hoverPointer = window.matchMedia('(hover: hover) and (pointer: fine)');
    let pinned = false;
    let pointerInside = false;
    let dismissed = false;
    let closeTimer = null;

    const cancelClose = () => {
        window.clearTimeout(closeTimer);
        closeTimer = null;
    };

    const setOpen = open => {
        resultsPopover.hidden = !open;
        resultsToggle.setAttribute('aria-expanded', String(open));
        resultsControl.toggleAttribute('data-open', open);
    };

    const syncPreview = () => {
        const focusInside = resultsPopover.contains(document.activeElement);
        setOpen(pinned || (!dismissed && (pointerInside || focusInside)));
    };

    const dismiss = () => {
        cancelClose();
        pinned = false;
        dismissed = true;
        setOpen(false);
    };

    resultsControl.addEventListener('pointerenter', event => {
        if (!hoverPointer.matches || event.pointerType === 'touch') return;
        cancelClose();
        pointerInside = true;
        dismissed = false;
        syncPreview();
    });

    resultsControl.addEventListener('pointerleave', () => {
        pointerInside = false;
        cancelClose();
        // Allow crossing the small space between the button and its panel.
        closeTimer = window.setTimeout(syncPreview, 140);
    });

    resultsToggle.addEventListener('click', () => {
        cancelClose();
        if (pinned) {
            dismiss();
        } else {
            // A first click pins an existing hover preview instead of closing it.
            pinned = true;
            dismissed = false;
            setOpen(true);
        }
    });

    resultsControl.addEventListener('focusin', () => {
        cancelClose();
        syncPreview();
    });
    resultsControl.addEventListener('focusout', () => {
        queueMicrotask(syncPreview);
    });

    document.addEventListener('click', event => {
        if (!resultsControl.contains(event.target)) dismiss();
    });
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || resultsPopover.hidden) return;
        const restoreFocus = resultsControl.contains(document.activeElement);
        event.preventDefault();
        dismiss();
        if (restoreFocus) resultsToggle.focus();
    });

    hoverPointer.addEventListener('change', () => {
        if (!hoverPointer.matches) {
            pointerInside = false;
            syncPreview();
        }
    });

    setOpen(false);
}
