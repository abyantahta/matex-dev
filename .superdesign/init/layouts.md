# Layouts

- `AuthenticatedLayout`: app shell — sticky top nav (logo MATEX, nav links by role, Master Data dropdown, user dropdown), optional `header` slot rendered in white band under nav, `<main>` on #E7EDF4 canvas.
- `AdminLayout`: wraps AuthenticatedLayout; header = eyebrow 'MASTER DATA' + title + description; body = pill tab bar (Overview / Item Master QAD / Suppliers / Kedisiplinan RM / Users) + children.
- `GuestLayout`: auth pages.


### `resources/js/Layouts/AuthenticatedLayout.jsx`

```jsx
import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import FlashMessage from '@/Components/FlashMessage';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import {
    canAccessMasterData,
    isMasterNavActive,
    isMasterSectionActive,
    masterNavForRole,
    masterNavHref,
} from '@/Config/masterNav';
import { Link, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

function navItemsForRole(role) {
    const base = [
        { href: 'dashboard', label: 'Dashboard', match: 'dashboard' },
        { href: 'purchase-orders.index', label: 'Purchase Orders', match: 'purchase-orders.*' },
        { href: 'delivery-notes.index', label: 'Delivery Notes', match: 'delivery-notes.*' },
    ];

    if (['supplier_rm', 'purchasing', 'admin'].includes(role)) {
        base.splice(1, 0, { href: 'forecasts.index', label: 'Forecast', match: 'forecasts.*' });
    }

    if (['ppic', 'admin'].includes(role)) {
        base.push({ href: 'receivings.index', label: 'Receiving', match: 'receivings.*' });
    }

    if (['supplier_rm', 'purchasing', 'admin', 'ppic'].includes(role)) {
        base.push({ href: 'billing.index', label: 'Billing / Receiving', match: 'billing.*' });
    }

    return base;
}

function MasterDataNav({ role }) {
    const active = isMasterSectionActive();
    const items = masterNavForRole(role);

    return (
        <Dropdown>
            <Dropdown.Trigger>
                <button
                    type="button"
                    className={
                        'group relative inline-flex items-center gap-1 px-1 pt-1 text-sm font-medium leading-5 transition duration-220 ease-matex focus:outline-none ' +
                        (active
                            ? 'font-semibold text-brand'
                            : 'text-ink-muted hover:text-ink')
                    }
                >
                    <span>Master Data</span>
                    <svg
                        className={
                            'h-3.5 w-3.5 transition duration-220 ease-matex ' +
                            (active ? 'text-brand' : 'text-ink-faint group-hover:text-ink-muted')
                        }
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            fillRule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clipRule="evenodd"
                        />
                    </svg>
                    <span
                        className={
                            'absolute inset-x-0 -bottom-px h-0.5 origin-left rounded-full bg-brand transition duration-320 ease-matex ' +
                            (active
                                ? 'scale-x-100 opacity-100'
                                : 'scale-x-0 opacity-0 group-hover:scale-x-100 group-hover:opacity-70')
                        }
                    />
                </button>
            </Dropdown.Trigger>
            <Dropdown.Content align="left" width="72" contentClasses="py-2 bg-surface">
                <div className="border-b border-line px-4 pb-2 pt-1">
                    <p className="ui-eyebrow">Master Data</p>
                    <p className="mt-0.5 text-xs text-ink-muted">
                        Raw material, suppliers, dan data pendukung
                    </p>
                </div>
                {items.map((item) => {
                    const itemActive = isMasterNavActive(item);
                    return (
                        <Dropdown.Link
                            key={item.key}
                            href={masterNavHref(item)}
                            className={
                                (item.group ? 'ps-7 ' : '') +
                                (itemActive
                                    ? '!bg-brand-muted !font-semibold !text-brand-deep'
                                    : '')
                            }
                        >
                            <span className="block">{item.label}</span>
                            {item.description && (
                                <span className="mt-0.5 block text-xs font-normal text-ink-faint">
                                    {item.description}
                                </span>
                            )}
                        </Dropdown.Link>
                    );
                })}
            </Dropdown.Content>
        </Dropdown>
    );
}

export default function AuthenticatedLayout({ header, children }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);
    const [showingMasterMobile, setShowingMasterMobile] = useState(false);
    const items = useMemo(() => navItemsForRole(user.role), [user.role]);
    const masterItems = useMemo(() => masterNavForRole(user.role), [user.role]);
    const showMaster = canAccessMasterData(user.role);

    return (
        <div className="min-h-screen bg-canvas">
            <nav className="sticky top-0 z-40 border-b border-line/80 bg-surface/90 backdrop-blur-md">
                <div className="w-full px-3 sm:px-4 lg:px-5">
                    <div className="flex h-16 justify-between gap-4">
                        <div className="flex min-w-0 items-center">
                            <Link
                                href={route('dashboard')}
                                className="group flex shrink-0 items-center gap-2.5 transition duration-220 ease-matex"
                            >
                                <ApplicationLogo className="h-9 w-9 transition duration-320 ease-matex group-hover:scale-105 group-hover:shadow-lift" />
                                <div className="leading-none">
                                    <span className="font-display text-lg font-bold tracking-[0.12em] text-ink">
                                        MATEX
                                    </span>
                                    <span className="mt-0.5 block text-[0.625rem] font-medium uppercase tracking-[0.16em] text-ink-muted">
                                        Material Exchange
                                    </span>
                                </div>
                            </Link>

                            <div className="hidden items-stretch gap-5 sm:ms-8 sm:flex lg:ms-10">
                                {items.map((item) => (
                                    <NavLink
                                        key={item.href}
                                        href={route(item.href)}
                                        active={route().current(item.match)}
                                    >
                                        {item.label}
                                    </NavLink>
                                ))}
                                {showMaster && <MasterDataNav role={user.role} />}
                            </div>
                        </div>

                        <div className="hidden items-center gap-3 sm:flex">
                            <div className="hidden text-right md:block">
                                <div className="text-xs font-semibold text-ink-soft">
                                    {user.role_label}
                                </div>
                                <div className="text-xs text-ink-muted">
                                    {user.company?.name}
                                </div>
                            </div>
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <span className="inline-flex">
                                        <button
                                            type="button"
                                            className="pressable inline-flex items-center rounded-md border border-line bg-surface px-3 py-2 text-sm font-semibold text-ink-soft hover:border-brand-line hover:text-ink"
                                        >
                                            {user.name}
                                            <svg
                                                className="-me-0.5 ms-2 h-4 w-4 text-ink-faint"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fillRule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clipRule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </span>
                                </Dropdown.Trigger>
                                <Dropdown.Content>
                                    <Dropdown.Link href={route('profile.edit')}>
                                        Profile
                                    </Dropdown.Link>
                                    <Dropdown.Link
                                        href={route('logout')}
                                        method="post"
                                        as="button"
                                    >
                                        Log Out
                                    </Dropdown.Link>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>

                        <div className="-me-1 flex items-center sm:hidden">
                            <button
                                type="button"
                                onClick={() =>
                                    setShowingNavigationDropdown((prev) => !prev)
                                }
                                className="pressable inline-flex items-center justify-center rounded-md border border-line p-2 text-ink-muted hover:bg-canvas-soft hover:text-ink"
                            >
                                <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        className={!showingNavigationDropdown ? 'inline-flex' : 'hidden'}
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={showingNavigationDropdown ? 'inline-flex' : 'hidden'}
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' border-t border-line bg-surface sm:hidden'
                    }
                >
                    <div className="space-y-1 py-3">
                        {items.map((item) => (
                            <ResponsiveNavLink
                                key={item.href}
                                href={route(item.href)}
                                active={route().current(item.match)}
                            >
                                {item.label}
                            </ResponsiveNavLink>
                        ))}

                        {showMaster && (
                            <div className="pt-1">
                                <button
                                    type="button"
                                    className={
                                        'flex w-full items-center justify-between border-l-4 py-2.5 pe-4 ps-3 text-base font-medium transition duration-220 ease-matex ' +
                                        (isMasterSectionActive()
                                            ? 'border-brand bg-brand-muted text-brand-deep'
                                            : 'border-transparent text-ink-muted')
                                    }
                                    onClick={() => setShowingMasterMobile((v) => !v)}
                                >
                                    <span>Master Data</span>
                                    <svg
                                        className={
                                            'h-4 w-4 transition duration-220 ease-matex ' +
                                            (showingMasterMobile ? 'rotate-180' : '')
                                        }
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fillRule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clipRule="evenodd"
                                        />
                                    </svg>
                                </button>
                                {(showingMasterMobile || isMasterSectionActive()) && (
                                    <div className="space-y-1 pb-1">
                                        {masterItems.map((item) => (
                                            <ResponsiveNavLink
                                                key={item.key}
                                                href={masterNavHref(item)}
                                                active={isMasterNavActive(item)}
                                                className={item.group ? 'ps-8 text-sm' : 'ps-6 text-sm'}
                                            >
                                                {item.label}
                                            </ResponsiveNavLink>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                    <div className="border-t border-line pb-3 pt-3">
                        <div className="px-4">
                            <div className="font-display text-base font-semibold text-ink">
                                {user.name}
                            </div>
                            <div className="text-sm text-ink-muted">{user.email}</div>
                        </div>
                        <div className="mt-2 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink method="post" href={route('logout')} as="button">
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="border-b border-line bg-surface">
                    <div className="ui-page !py-5">{header}</div>
                </header>
            )}

            <FlashMessage />
            <main className="animate-fade-in">{children}</main>
        </div>
    );
}
```


### `resources/js/Layouts/AdminLayout.jsx`

```jsx
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    isMasterNavActive,
    masterNavForRole,
    masterNavHref,
    masterTabItemsForRole,
} from '@/Config/masterNav';
import { Link, usePage } from '@inertiajs/react';

export default function AdminLayout({ title, description, actions, children }) {
    const { auth } = usePage().props;
    const role = auth.user.role;

    const primaryTabs = masterTabItemsForRole(role);
    const supplierQuickLinks = masterNavForRole(role).filter((item) => item.group === 'suppliers');

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="ui-eyebrow">Master Data</p>
                        <h2 className="ui-section-title mt-1">{title}</h2>
                        {description && (
                            <p className="mt-1 text-sm text-ink-muted">{description}</p>
                        )}
                    </div>
                    {actions}
                </div>
            }
        >
            <div className="ui-page space-y-5">
                <div className="flex flex-col gap-3">
                    <div className="flex flex-wrap items-center gap-2">
                        {primaryTabs.map((link) => {
                            const active = isMasterNavActive(link);
                            return (
                                <Link
                                    key={link.key}
                                    href={masterNavHref(link)}
                                    className={`ui-tab ${active ? 'ui-tab-active' : 'ui-tab-idle'}`}
                                >
                                    {link.label}
                                </Link>
                            );
                        })}
                    </div>

                    {route().current('admin.qad-suppliers.*') && supplierQuickLinks.length > 0 && (
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="text-[0.625rem] font-semibold uppercase tracking-[0.14em] text-ink-faint">
                                Filter supplier
                            </span>
                            {supplierQuickLinks.map((link) => {
                                const active = isMasterNavActive(link);
                                return (
                                    <Link
                                        key={link.key}
                                        href={masterNavHref(link)}
                                        className={`ui-tab text-xs ${active ? 'ui-tab-active' : 'ui-tab-idle'}`}
                                    >
                                        {link.label}
                                    </Link>
                                );
                            })}
                        </div>
                    )}
                </div>
                <div className="animate-fade-up">{children}</div>
            </div>
        </AuthenticatedLayout>
    );
}
```


### `resources/js/Layouts/GuestLayout.jsx`

```jsx
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-ink px-4 py-10">
            <div
                className="pointer-events-none absolute inset-0 opacity-40"
                style={{
                    backgroundImage:
                        'radial-gradient(circle at 20% 20%, rgba(20,158,114,0.35), transparent 42%), radial-gradient(circle at 80% 10%, rgba(196,92,38,0.22), transparent 36%), linear-gradient(160deg, #0F2137 0%, #12263A 45%, #0B6E4F 140%)',
                }}
            />
            <div
                className="pointer-events-none absolute inset-0 opacity-[0.07]"
                style={{
                    backgroundImage:
                        'linear-gradient(rgba(255,255,255,0.35) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.35) 1px, transparent 1px)',
                    backgroundSize: '48px 48px',
                }}
            />

            <div className="relative z-10 w-full max-w-md animate-fade-up">
                <Link href="/" className="mb-8 flex flex-col items-center gap-3 text-center">
                    <ApplicationLogo className="h-16 w-16 shadow-lift transition duration-320 ease-matex hover:scale-105" />
                    <div>
                        <span className="font-display text-3xl font-bold tracking-[0.18em] text-white">
                            MATEX
                        </span>
                        <p className="mt-2 text-xs font-medium uppercase tracking-[0.2em] text-brand-line">
                            Material Exchange Portal
                        </p>
                    </div>
                </Link>

                <div className="rounded-panel border border-white/10 bg-surface/95 p-6 shadow-lift backdrop-blur-sm sm:p-8">
                    {children}
                </div>
            </div>
        </div>
    );
}
```


### `resources/js/Config/masterNav.js`

```js
/**
 * Konfigurasi menu Master Data — tambah/ubah item di sini saja
 * agar nav utama & tab admin tetap sinkron.
 *
 * Diakses Purchasing (Mbak Dita) & Admin. Users kini juga bisa dikelola
 * Purchasing (kecuali akun Admin — lihat UserController).
 */
export const MASTER_DATA_ROLES = ['admin', 'purchasing'];

export const MASTER_NAV_ITEMS = [
    {
        key: 'overview',
        route: 'admin.dashboard',
        label: 'Overview',
        description: 'Ringkasan master data',
        match: 'admin.dashboard',
        roles: ['admin', 'purchasing'],
    },
    {
        key: 'qad-items',
        route: 'admin.qad-items.index',
        label: 'Item Master QAD',
        description: 'Cache item master hasil sync dari QAD',
        match: 'admin.qad-items.*',
        roles: ['admin', 'purchasing'],
    },
    {
        key: 'suppliers',
        route: 'admin.qad-suppliers.index',
        label: 'Suppliers',
        description: 'Master supplier RM & OHP, tersinkron dari QAD',
        match: 'admin.qad-suppliers.*',
        params: {},
        roles: ['admin', 'purchasing'],
    },
    {
        key: 'supplier-rm',
        route: 'admin.qad-suppliers.index',
        label: 'Supplier RM',
        description: 'Supplier raw material',
        match: 'admin.qad-suppliers.*',
        params: { category: 'raw_mat' },
        group: 'suppliers',
        roles: ['admin', 'purchasing'],
    },
    {
        key: 'supplier-ohp',
        route: 'admin.qad-suppliers.index',
        label: 'Supplier OHP',
        description: 'Supplier OH Part',
        match: 'admin.qad-suppliers.*',
        params: { category: 'ohp' },
        group: 'suppliers',
        roles: ['admin', 'purchasing'],
    },
    {
        key: 'discipline',
        route: 'admin.discipline.index',
        label: 'Kedisiplinan RM',
        description: 'Raport keterlambatan vs plan',
        match: 'admin.discipline.*',
        roles: ['admin', 'purchasing'],
    },
    {
        key: 'users',
        route: 'admin.users.index',
        label: 'Users',
        description: 'Akun & role portal',
        match: 'admin.users.*',
        roles: ['admin', 'purchasing'],
    },
];

export function masterNavForRole(role) {
    return MASTER_NAV_ITEMS.filter((item) => !item.roles || item.roles.includes(role));
}

/** Item utama di tab admin (tanpa filter turunan) */
export function masterTabItemsForRole(role) {
    return masterNavForRole(role).filter((item) => !item.group);
}

/** @deprecated gunakan masterTabItemsForRole(role) */
export const MASTER_TAB_ITEMS = MASTER_NAV_ITEMS.filter((item) => !item.group);

export function canAccessMasterData(role) {
    return MASTER_DATA_ROLES.includes(role);
}

export function masterNavHref(item) {
    const params = item.params || {};
    return route(item.route, params);
}

export function isMasterNavActive(item) {
    if (!route().current(item.match)) {
        return false;
    }

    if (typeof window === 'undefined') {
        return !item.params?.category;
    }

    const category = new URL(window.location.href).searchParams.get('category');

    if (item.params?.category) {
        return category === item.params.category;
    }

    if (item.key === 'suppliers') {
        return !category;
    }

    return true;
}

export function isMasterSectionActive() {
    return route().current('admin.*');
}
```


### `resources/views/app.blade.php`

```php
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
```

