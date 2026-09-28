import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

const brandPalette = {
    50: '#F7F8F0',
    100: '#EAF5FC',
    200: '#D2EBFC',
    300: '#9CD5FF',
    400: '#8BC3EA',
    500: '#7AAACE',
    600: '#5E88A7',
    700: '#355872',
    800: '#2E4C61',
    900: '#263F52',
    950: '#355872',
};

const inkPalette = {
    50: '#F7F8F0',
    100: '#EEF3F1',
    200: '#DDE7E7',
    300: '#C5D5D9',
    400: '#9EB3BC',
    500: '#758D98',
    600: '#58717E',
    700: '#405E6D',
    800: '#2E4A5B',
    900: '#233C4D',
    950: '#172D3D',
};

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                brand: brandPalette,
                blue: brandPalette,
                indigo: brandPalette,
                slate: inkPalette,
                gray: inkPalette,
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, typography],
};
