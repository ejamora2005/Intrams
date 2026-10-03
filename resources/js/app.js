import './bootstrap';
import './pwa';

if (document.querySelector('[data-score-sheet-export]')) {
    void import('./score-sheet-export');
}

if (document.querySelector('[data-volleyball-score-sheet]')) {
    void import('./volleyball-score-sheet');
}

if (document.querySelector('[data-login-modal]')) {
    void import('./login-modal');
}
