# Theme — Matex design tokens

## Compact token summary
Colors (Tailwind `theme.extend.colors`):
- canvas `#E7EDF4` (page bg), canvas-soft `#F3F6FA` (table heads, hover rows)
- surface `#FFFFFF` (cards, nav)
- ink `#0F2137` (text), ink-soft `#243B53`, ink-muted `#627D98`, ink-faint `#9FB3C8`
- brand `#0B6E4F` (primary green), brand-deep `#084C37`, brand-bright `#149E72`, brand-muted `#E4F3ED` (tint bg), brand-line `#B5D6C9`
- copper `#C45C26` (accent), copper-soft `#E07A3D`, copper-muted `#FDF1EA`, copper-line `#F0C9B0`
- line `#D5DEE8` (borders), line-strong `#B8C5D4`
- Status greens for badges use Tailwind emerald-50/700 (e.g. "Aktif").

Typography:
- Body: "IBM Plex Sans" 400/500/600/700 (fonts.bunny.net)
- Display/headings (h1-h3, `.font-display`): Sora 500/600/700, tracking-tight
- `.ui-eyebrow`: Sora 0.6875rem (11px) 600 uppercase tracking .14em, color brand
- display-sm 1.5rem/1.25 -0.02em 600; display 1.875rem/1.2 -0.025em 700
- Stat numbers: font-display text-3xl font-bold tabular-nums text-ink

Spacing / radius / shadow:
- Page padding `.ui-page`: px-3 py-4 sm:px-4 lg:px-5 (full width, no max-w)
- Card `.ui-panel`: rounded-panel 0.75rem, border 1px line, bg surface, shadow-panel `0 1px 0 rgba(15,33,55,.04), 0 8px 24px -12px rgba(15,33,55,.18)`
- Hover `.ui-panel-interactive`: border brand-line, shadow-lift `0 12px 28px -14px rgba(15,33,55,.28)`, translateY(-2px)
- Tabs `.ui-tab`: rounded-md px-3 py-1.5 text-sm medium; active = bg brand text white shadow-sm; idle = bg surface text ink-muted ring-1 ring-line
- Motion: ease cubic-bezier(.22,1,.36,1), 220ms/320ms; animate-fade-up (8px rise, 320ms) staggered 40ms per card
- Breakpoints: Tailwind defaults (sm 640, md 768, lg 1024)

## Raw sources

### `tailwind.config.js`

```js
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
                    DEFAULT: '#E7EDF4',
                    soft: '#F3F6FA',
                },
                surface: '#FFFFFF',
                ink: {
                    DEFAULT: '#0F2137',
                    soft: '#243B53',
                    muted: '#627D98',
                    faint: '#9FB3C8',
                },
                brand: {
                    DEFAULT: '#0B6E4F',
                    deep: '#084C37',
                    bright: '#149E72',
                    muted: '#E4F3ED',
                    line: '#B5D6C9',
                },
                copper: {
                    DEFAULT: '#C45C26',
                    soft: '#E07A3D',
                    muted: '#FDF1EA',
                    line: '#F0C9B0',
                },
                line: {
                    DEFAULT: '#D5DEE8',
                    strong: '#B8C5D4',
                },
            },
            fontFamily: {
                sans: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
                display: ['Sora', '"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                'display-sm': ['1.5rem', { lineHeight: '1.25', letterSpacing: '-0.02em', fontWeight: '600' }],
                'display': ['1.875rem', { lineHeight: '1.2', letterSpacing: '-0.025em', fontWeight: '700' }],
            },
            spacing: {
                18: '4.5rem',
                22: '5.5rem',
            },
            borderRadius: {
                panel: '0.75rem',
            },
            boxShadow: {
                panel: '0 1px 0 rgba(15, 33, 55, 0.04), 0 8px 24px -12px rgba(15, 33, 55, 0.18)',
                lift: '0 12px 28px -14px rgba(15, 33, 55, 0.28)',
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
```


### `resources/css/app.css`

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
    :root {
        --matex-ease: cubic-bezier(0.22, 1, 0.36, 1);
        --matex-duration: 220ms;
    }

    html {
        scroll-behavior: smooth;
    }

    body {
        @apply bg-canvas font-sans text-ink antialiased;
        font-feature-settings: 'ss01' on, 'kern' on;
    }

    ::selection {
        @apply bg-brand-muted text-brand-deep;
    }

    h1,
    h2,
    h3 {
        font-family: Sora, 'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif;
        @apply tracking-tight text-ink;
    }

    .font-display {
        font-family: Sora, 'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif;
    }
}

@layer components {
    .ui-panel {
        @apply rounded-panel border border-line bg-surface shadow-panel;
        transition:
            transform var(--matex-duration) var(--matex-ease),
            box-shadow var(--matex-duration) var(--matex-ease),
            border-color var(--matex-duration) var(--matex-ease);
    }

    .ui-panel-interactive:hover {
        @apply border-brand-line shadow-lift;
        transform: translateY(-2px);
    }

    .ui-page {
        @apply w-full px-3 py-4 sm:px-4 lg:px-5;
    }

    .ui-section-title {
        @apply font-display text-lg font-semibold text-ink sm:text-xl;
    }

    .ui-eyebrow {
        @apply font-display text-[0.6875rem] font-semibold uppercase tracking-[0.14em] text-brand;
    }

    .ui-link {
        @apply font-medium text-brand transition duration-220 ease-matex hover:text-brand-deep;
    }

    .ui-table-head {
        @apply bg-canvas-soft text-left text-[0.6875rem] font-semibold uppercase tracking-[0.08em] text-ink-muted;
    }

    .ui-row {
        @apply transition-colors duration-220 ease-matex hover:bg-canvas-soft/80;
    }

    .ui-tab {
        @apply inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium transition duration-220 ease-matex;
    }

    .ui-tab-active {
        @apply bg-brand text-white shadow-sm;
    }

    .ui-tab-idle {
        @apply bg-surface text-ink-muted ring-1 ring-line hover:-translate-y-0.5 hover:text-ink hover:shadow-sm;
    }
}

@layer utilities {
    .no-spin::-webkit-outer-spin-button,
    .no-spin::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .no-spin[type='number'] {
        -moz-appearance: textfield;
        appearance: textfield;
    }

    .pressable {
        transition:
            transform var(--matex-duration) var(--matex-ease),
            box-shadow var(--matex-duration) var(--matex-ease),
            background-color var(--matex-duration) var(--matex-ease),
            border-color var(--matex-duration) var(--matex-ease),
            color var(--matex-duration) var(--matex-ease);
    }

    .pressable:hover {
        transform: translateY(-1px);
    }

    .pressable:active {
        transform: translateY(0) scale(0.98);
    }
}
```

