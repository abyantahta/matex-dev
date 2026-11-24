import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            colors: {
                canvas: {
                    DEFAULT: '#F7F6FB',
                    soft: '#FBFAFD',
                },
                surface: '#FFFFFF',
                ink: {
                    DEFAULT: '#25233A',
                    soft: '#3D3A56',
                    muted: '#76738F',
                    faint: '#ABA8C0',
                },
                brand: {
                    DEFAULT: '#7467D8',
                    deep: '#5D50C2',
                    bright: '#A79DF0',
                    muted: '#F0EDFF',
                    line: '#DCD6FB',
                },
                copper: {
                    DEFAULT: '#C9773E',
                    soft: '#E9A36F',
                    muted: '#FFF3EA',
                    line: '#F7D9C2',
                },
                line: {
                    DEFAULT: '#ECEAF4',
                    strong: '#DDDAEA',
                },
                night: {
                    DEFAULT: '#25233A',
                    soft: '#3D3A56',
                    line: '#ECEAF4',
                },
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                'display-sm': ['1.5rem', { lineHeight: '1.25', letterSpacing: '-0.025em', fontWeight: '600' }],
                'display': ['1.875rem', { lineHeight: '1.2', letterSpacing: '-0.03em', fontWeight: '600' }],
            },
            spacing: {
                18: '4.5rem',
                22: '5.5rem',
            },
            borderRadius: {
                panel: '1.125rem',
            },
            boxShadow: {
                glow: '0 6px 16px -8px rgba(116, 103, 216, 0.45)',
                panel: '0 1px 2px rgba(37, 35, 58, 0.03), 0 8px 24px -18px rgba(116, 103, 216, 0.22)',
                lift: '0 16px 32px -20px rgba(116, 103, 216, 0.35)',
            },
            transitionTimingFunction: {
                matex: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
            transitionDuration: {
                220: '220ms',
                320: '320ms',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                'flash-in': {
                    '0%': { opacity: '0', transform: 'translateY(-6px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 320ms cubic-bezier(0.22, 1, 0.36, 1) both',
                'fade-in': 'fade-in 220ms ease-out both',
                'flash-in': 'flash-in 280ms cubic-bezier(0.22, 1, 0.36, 1) both',
            },
        },
    },

    plugins: [forms],
};
