# Matex — Design System

## Product context
Matex (Material Exchange) is an internal B2B procurement/traceability portal for PT. SDI and its raw-material (RM) and OH-Part (OHP) suppliers. Flow: PO → RM confirm → DN → ship → OHP confirm → PPIC receiving → QAD → billing. Users are purchasing staff, PPIC, admins, and supplier staff — desktop-first, data-dense, used daily. Tone: calm, operational, trustworthy. Not a marketing site.

Key pages: Dashboard, Forecast, Purchase Orders, Delivery Notes, Receiving, Billing, Master Data (Overview, Item Master QAD, Suppliers, Kedisiplinan RM, Users). Language of UI copy: Bahasa Indonesia (labels like "Kelola →", "Belum ada…").

## Visual direction
Light, clean, enterprise. White panels on a cool blue-grey canvas; one strong brand green used sparingly for emphasis, active states, and eyebrow labels; copper as a rare warm accent (warnings/late). Subtle depth via soft layered shadows, not heavy borders. Micro-motion: fade-up on mount, 2px lift on hover.

## Color tokens (use ONLY these)
- Canvas (page bg): `#E7EDF4`; canvas-soft: `#F3F6FA`
- Surface (cards/nav): `#FFFFFF`
- Ink (text): `#0F2137`; ink-soft `#243B53`; ink-muted `#627D98`; ink-faint `#9FB3C8`
- Brand green: `#0B6E4F`; brand-deep `#084C37`; brand-bright `#149E72`; brand-muted (tint) `#E4F3ED`; brand-line `#B5D6C9`
- Copper accent: `#C45C26`; copper-soft `#E07A3D`; copper-muted `#FDF1EA`; copper-line `#F0C9B0`
- Borders: line `#D5DEE8`; line-strong `#B8C5D4`
- Status badge "Aktif": emerald-50 bg / emerald-700 text
No other hues (no purple, pink, blue gradients, neon). Gradients only if built from brand green shades (`#0B6E4F → #149E72`) or brand-muted tints.

## Typography
- Body: "IBM Plex Sans" 400/500/600/700
- Display (headings, numbers, eyebrows): "Sora" 500/600/700, tight tracking
- Eyebrow label: Sora 11px, 600, uppercase, letter-spacing .14em, brand green
- Section title: Sora 18–20px 600
- KPI number: Sora 30px 700, tabular-nums, ink
- Helper text: 14px ink-muted; micro text 12px ink-faint

## Layout & spacing
- Full-width pages, padding 12–20px (`px-3 sm:px-4 lg:px-5`), vertical rhythm 20–24px between blocks
- Grids: `gap-4`, stats `sm:grid-cols-2 lg:grid-cols-3`, link cards `md:grid-cols-3`
- Card padding 20px (p-5)

## Components
- Panel/card: radius 12px, 1px border line, white bg, shadow `0 1px 0 rgba(15,33,55,.04), 0 8px 24px -12px rgba(15,33,55,.18)`. Interactive hover: border brand-line, shadow `0 12px 28px -14px rgba(15,33,55,.28)`, translateY(-2px).
- Pill tabs: radius 6px, px-12 py-6, 14px medium; active solid brand green + white text; idle white + ink-muted + 1px line ring.
- Count pill: brand-muted bg, brand-deep text, 14px semibold, radius 6px.
- Buttons: primary = solid brand green, white text, radius 6px; secondary = white with line border.
- Top nav: sticky, white 90% + backdrop blur, 64px tall, bottom border line/80; active link = brand green text + 2px green underline.

## Motion
- Ease `cubic-bezier(.22,1,.36,1)`, 220ms (color/transform), 320ms (entrance)
- `fade-up`: opacity 0→1, translateY 8px→0, 320ms, stagger 40ms per card

## Rules for generated designs
- Keep the exact palette above; the user explicitly wants redesigns to "still match the colors".
- Keep IBM Plex Sans + Sora only.
- Keep the app shell (top nav, header band, tab pills) unchanged unless asked; redesign targets are the content cards.
