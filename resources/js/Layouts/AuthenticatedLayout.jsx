import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import FlashMessage from '@/Components/FlashMessage';
import {
    canAccessMasterData,
    isMasterNavActive,
    masterNavForRole,
    masterNavHref,
} from '@/Config/masterNav';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

function transactionItemsForRole(role) {
    const items = [
        { href: 'purchase-orders.index', label: 'Purchase Order', match: 'purchase-orders.*' },
        { href: 'delivery-notes.index', label: 'Delivery Notes', match: 'delivery-notes.*' },
    ];

    if (['supplier_rm', 'purchasing', 'admin'].includes(role)) {
        items.splice(2, 0, { href: 'forecasts.index', label: 'Forecast', match: 'forecasts.*' });
    }

    if (['ppic', 'admin'].includes(role)) {
        items.push({ href: 'receivings.index', label: 'Receiving', match: 'receivings.*' });
    }

    return items;
}

function monitoringItemsForRole(role) {
    const items = [];

    if (['purchasing', 'admin', 'supplier_ohp'].includes(role)) {
        items.push({
            href: 'monitoring.kedatangan',
            label: 'Monitoring Kedatangan di OHP',
            match: 'monitoring.kedatangan',
        });
    }

    if (['purchasing', 'admin', 'supplier_rm'].includes(role)) {
        items.push({
            href: 'monitoring.pengiriman',
            label: 'Monitoring Pengiriman',
            match: 'monitoring.pengiriman',
        });
    }

    return items;
}

/* ------------------------------------------------------------------ */
/* Visual-only helpers: icons + accent colors per menu item            */
/* ------------------------------------------------------------------ */

const ICON_PATHS = {
    home: 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1v-9.5Z',
    po: 'M9 4h6m-6 0a2 2 0 0 0-2 2v0h10v0a2 2 0 0 0-2-2M9 4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2M7 6H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1M8 12h8M8 16h5',
    truck: 'M3 6h11v10H3zM14 9h4l3 3v4h-7M7.5 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm10 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z',
    chart: 'M4 20V10m6 10V4m6 16v-7m4 7H2',
    inbox: 'M4 13h4l1.5 3h5L16 13h4M5 5h14l1 8v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-6l1-8Z',
    radar: 'M12 12m-8 0a8 8 0 1 0 16 0 8 8 0 1 0-16 0M12 12l5-5M12 8a4 4 0 1 0 4 4',
    route: 'M6 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm12-10a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM6 15V9a4 4 0 0 1 4-4h6M18 9v6a4 4 0 0 1-4 4H8',
    grid: 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
    database: 'M12 3c4.4 0 8 1.3 8 3s-3.6 3-8 3-8-1.3-8-3 3.6-3 8-3Zm8 3v12c0 1.7-3.6 3-8 3s-8-1.3-8-3V6m16 6c0 1.7-3.6 3-8 3s-8-1.3-8-3',
    users: 'M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1m17 0v-1a4 4 0 0 0-3-3.9M14 4.1a4 4 0 0 1 0 7.8M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
    factory: 'M3 21V10l6 3V10l6 3V6l6 3v12H3Zm4-4h2m4 0h2',
    tag: 'M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9Zm5-4.5h.01',
    clock: 'M12 7v5l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    user: 'M20 21a8 8 0 1 0-16 0M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
    logout: 'M15 17l5-5-5-5M20 12H9M12 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7',
    menu: 'M4 7h16M4 12h16M4 17h16',
    close: 'M6 6l12 12M18 6 6 18',
};

const ITEM_STYLE = {
    dashboard: { icon: 'home', tone: 'bg-violet-100 text-violet-600' },
    'purchase-orders.index': { icon: 'po', tone: 'bg-sky-100 text-sky-600' },
    'delivery-notes.index': { icon: 'truck', tone: 'bg-emerald-100 text-emerald-600' },
    'forecasts.index': { icon: 'chart', tone: 'bg-amber-100 text-amber-600' },
    'receivings.index': { icon: 'inbox', tone: 'bg-rose-100 text-rose-500' },
    'monitoring.kedatangan': { icon: 'radar', tone: 'bg-cyan-100 text-cyan-600' },
    'monitoring.pengiriman': { icon: 'route', tone: 'bg-lime-100 text-lime-700' },
    overview: { icon: 'grid', tone: 'bg-violet-100 text-violet-600' },
    'qad-items': { icon: 'database', tone: 'bg-indigo-100 text-indigo-500' },
    suppliers: { icon: 'factory', tone: 'bg-teal-100 text-teal-600' },
    'supplier-rm': { icon: 'factory', tone: 'bg-teal-100 text-teal-600' },
    'supplier-ohp': { icon: 'factory', tone: 'bg-sky-100 text-sky-600' },
    prices: { icon: 'tag', tone: 'bg-yellow-100 text-yellow-700' },
    discipline: { icon: 'clock', tone: 'bg-orange-100 text-orange-500' },
    users: { icon: 'users', tone: 'bg-pink-100 text-pink-500' },
};

function Icon({ name, className = 'h-[18px] w-[18px]' }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={className}
            aria-hidden="true"
        >
            <path d={ICON_PATHS[name] || ICON_PATHS.grid} />
        </svg>
    );
}

function SideLink({ href, label, active, styleKey, sub = false, collapsed = false, trailing = null, ...props }) {
    const style = ITEM_STYLE[styleKey] || { icon: 'grid', tone: 'bg-slate-100 text-slate-500' };

    return (
        <div className="relative flex items-center">
            <Link
                href={href}
                title={collapsed ? label : undefined}
                {...props}
                className={
                    'group relative flex w-full items-center rounded-xl py-1.5 text-[0.8125rem] font-medium leading-tight transition duration-220 ease-matex ' +
                    (collapsed ? 'justify-center px-0 ' : 'gap-3 px-2.5 ') +
                    (sub && !collapsed ? 'ps-6 ' : '') +
                    (trailing && !collapsed ? 'pe-9 ' : '') +
                    (active
                        ? 'bg-brand-muted font-semibold text-brand-deep'
                        : 'text-ink-muted hover:bg-canvas hover:text-ink')
                }
            >
                {active && (
                    <span className="absolute -left-3 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-brand-bright" />
                )}
                <span
                    className={
                        'flex shrink-0 items-center justify-center rounded-lg transition duration-220 ease-matex ' +
                        style.tone +
                        (sub ? ' h-6 w-6' : ' h-7 w-7') +
                        (active ? ' ring-2 ring-white' : '')
                    }
                >
                    <Icon name={style.icon} className={sub ? 'h-3.5 w-3.5' : 'h-[17px] w-[17px]'} />
                </span>
                {!collapsed && <span className="line-clamp-2 min-w-0 flex-1">{label}</span>}
            </Link>
            {!collapsed && trailing}
        </div>
    );
}

function Chevron({ open, className = '' }) {
    return (
        <svg
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
            className={`h-4 w-4 transition-transform duration-220 ease-matex ${open ? 'rotate-180' : ''} ${className}`}
        >
            <path
                fillRule="evenodd"
                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                clipRule="evenodd"
            />
        </svg>
    );
}

/** Smoothly expands / collapses its children (CSS grid-rows trick). */
function Collapse({ open, children }) {
    return (
        <div
            className={
                'grid transition-[grid-template-rows,opacity] duration-320 ease-matex ' +
                (open ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0')
            }
            aria-hidden={!open}
        >
            <div className={'min-h-0 overflow-hidden ' + (open ? '' : 'invisible')}>{children}</div>
        </div>
    );
}

function SideSection({ label, open, onToggle, collapsed, hasActive, children }) {
    if (collapsed) {
        // Icon-only mode: no accordion, just a thin divider between groups.
        return (
            <div className="space-y-1 pt-2">
                <div className="mx-auto mb-2 h-px w-8 bg-line" />
                {children}
            </div>
        );
    }

    return (
        <div className="pt-2">
            <button
                type="button"
                data-keep-open
                onClick={onToggle}
                aria-expanded={open}
                className="group flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-[0.625rem] font-bold uppercase tracking-[0.16em] text-ink-faint transition hover:bg-canvas hover:text-ink-muted"
            >
                <span className="flex items-center gap-2">
                    {label}
                    {!open && hasActive && <span className="h-1.5 w-1.5 rounded-full bg-brand-bright" />}
                </span>
                <Chevron open={open} className="h-3.5 w-3.5" />
            </button>
            <Collapse open={open}>
                <div className="space-y-1 pt-1">{children}</div>
            </Collapse>
        </div>
    );
}

function Sidebar({
    user,
    transactionItems,
    monitoringItems,
    masterItems,
    showMaster,
    onNavigate,
    collapsed = false,
}) {
    const transactionActive = transactionItems.some((item) => route().current(item.match));
    const monitoringActive = monitoringItems.some((item) => route().current(item.match));
    const masterActive = showMaster && route().current('admin.*');

    const [openSection, setOpenSection] = useState(() =>
        masterActive ? 'master' : monitoringActive ? 'monitoring' : 'transaction',
    );
    const toggleSection = (key) => setOpenSection((current) => (current === key ? null : key));

    // Master items: parents + their children (item.group === parent key)
    const masterParents = masterItems.filter((item) => !item.group);
    const masterChildren = (key) => masterItems.filter((item) => item.group === key);
    const [openGroups, setOpenGroups] = useState(() => ({
        suppliers: route().current('admin.qad-suppliers.*'),
    }));
    const toggleGroup = (key) => setOpenGroups((current) => ({ ...current, [key]: !current[key] }));

    return (
        <div className="flex h-full flex-col border-r border-line bg-surface text-ink">
            <div className={'relative overflow-hidden pb-4 pt-5 ' + (collapsed ? 'px-0' : 'px-5')}>
                <div className="pointer-events-none absolute -left-10 -top-16 h-40 w-40 rounded-full bg-violet-200/60 blur-3xl" />
                <div className="pointer-events-none absolute -right-10 top-0 h-32 w-32 rounded-full bg-pink-200/50 blur-3xl" />
                <Link
                    href={route('dashboard')}
                    className={'relative flex items-center gap-3 ' + (collapsed ? 'justify-center' : '')}
                    title={collapsed ? 'MATEX' : undefined}
                >
                    <ApplicationLogo className="h-10 w-10 shrink-0" />
                    {!collapsed && (
                        <div className="leading-none">
                            <span className="font-display text-lg font-extrabold tracking-[0.06em] text-ink">
                                MATEX
                            </span>
                            <span className="mt-1 block whitespace-nowrap text-[0.625rem] font-semibold uppercase tracking-[0.16em] text-ink-faint">
                                Material Exchange
                            </span>
                        </div>
                    )}
                </Link>
            </div>

            <nav
                className={'sidebar-scroll flex-1 space-y-0.5 overflow-y-auto overflow-x-hidden pb-6 ' + (collapsed ? 'px-3' : 'px-3')}
                onClick={onNavigate}
            >
                <SideLink
                    href={route('dashboard')}
                    label="Dashboard"
                    styleKey="dashboard"
                    collapsed={collapsed}
                    active={route().current('dashboard')}
                />

                <SideSection
                    label="Transaction"
                    collapsed={collapsed}
                    open={openSection === 'transaction'}
                    hasActive={transactionActive}
                    onToggle={() => toggleSection('transaction')}
                >
                    {transactionItems.map((item) => (
                        <SideLink
                            key={item.href}
                            href={route(item.href)}
                            label={item.label}
                            styleKey={item.href}
                            collapsed={collapsed}
                            active={route().current(item.match)}
                        />
                    ))}
                </SideSection>

                {monitoringItems.length > 0 && (
                    <SideSection
                        label="Monitoring"
                        collapsed={collapsed}
                        open={openSection === 'monitoring'}
                        hasActive={monitoringActive}
                        onToggle={() => toggleSection('monitoring')}
                    >
                        {monitoringItems.map((item) => (
                            <SideLink
                                key={item.href}
                                href={route(item.href)}
                                label={item.label}
                                styleKey={item.href}
                                collapsed={collapsed}
                                active={route().current(item.match)}
                            />
                        ))}
                    </SideSection>
                )}

                {showMaster && (
                    <SideSection
                        label="Master Data"
                        collapsed={collapsed}
                        open={openSection === 'master'}
                        hasActive={masterActive}
                        onToggle={() => toggleSection('master')}
                    >
                        {masterParents.map((item) => {
                            const children = masterChildren(item.key);
                            const groupOpen = Boolean(openGroups[item.key]);

                            return (
                                <div key={item.key} className="space-y-1">
                                    <SideLink
                                        href={masterNavHref(item)}
                                        label={item.label}
                                        styleKey={item.key}
                                        collapsed={collapsed}
                                        active={isMasterNavActive(item)}
                                        trailing={
                                            children.length > 0 ? (
                                                <button
                                                    type="button"
                                                    data-keep-open
                                                    onClick={() => toggleGroup(item.key)}
                                                    aria-expanded={groupOpen}
                                                    aria-label={`${groupOpen ? 'Sembunyikan' : 'Tampilkan'} sub-menu ${item.label}`}
                                                    className="absolute right-1.5 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg text-ink-faint transition hover:bg-white hover:text-ink"
                                                >
                                                    <Chevron open={groupOpen} />
                                                </button>
                                            ) : null
                                        }
                                    />
                                    {children.length > 0 && !collapsed && (
                                        <Collapse open={groupOpen}>
                                            <div className="relative ms-[1.375rem] space-y-1 border-s border-line ps-1">
                                                {children.map((child) => (
                                                    <SideLink
                                                        key={child.key}
                                                        href={masterNavHref(child)}
                                                        label={child.label}
                                                        styleKey={child.key}
                                                        sub
                                                        active={isMasterNavActive(child)}
                                                    />
                                                ))}
                                            </div>
                                        </Collapse>
                                    )}
                                </div>
                            );
                        })}
                    </SideSection>
                )}
            </nav>

            <div className="border-t border-line p-3">
                {collapsed ? (
                    <div className="flex flex-col items-center gap-2">
                        <span
                            title={`${user.name} · ${user.role_label}`}
                            className="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-sm font-extrabold uppercase text-amber-700"
                        >
                            {(user.name || '?').trim().charAt(0)}
                        </span>
                        <Link
                            href={route('profile.edit')}
                            title="Profile"
                            className="flex h-9 w-9 items-center justify-center rounded-xl text-ink-soft ring-1 ring-inset ring-line transition hover:bg-canvas hover:text-ink"
                        >
                            <Icon name="user" className="h-4 w-4" />
                        </Link>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            title="Log Out"
                            className="flex h-9 w-9 items-center justify-center rounded-xl text-rose-500 ring-1 ring-inset ring-rose-100 transition hover:bg-rose-50 hover:text-rose-600"
                        >
                            <Icon name="logout" className="h-4 w-4" />
                        </Link>
                    </div>
                ) : (
                    <>
                        <div className="flex items-center gap-3 rounded-2xl bg-canvas p-2.5">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-sm font-extrabold uppercase text-amber-700">
                                {(user.name || '?').trim().charAt(0)}
                            </span>
                            <div className="min-w-0 flex-1 leading-tight">
                                <p className="truncate text-sm font-semibold text-ink">{user.name}</p>
                                <p className="truncate text-[0.6875rem] text-ink-muted">
                                    {user.role_label}
                                    {user.company?.name ? ` · ${user.company.name}` : ''}
                                </p>
                            </div>
                        </div>
                        <div className="mt-2 grid grid-cols-2 gap-2">
                            <Link
                                href={route('profile.edit')}
                                className="flex items-center justify-center gap-1.5 rounded-xl px-2 py-2 text-xs font-semibold text-ink-soft ring-1 ring-inset ring-line transition hover:bg-canvas hover:text-ink"
                            >
                                <Icon name="user" className="h-3.5 w-3.5" />
                                Profile
                            </Link>
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="flex items-center justify-center gap-1.5 rounded-xl px-2 py-2 text-xs font-semibold text-rose-500 ring-1 ring-inset ring-rose-100 transition hover:bg-rose-50 hover:text-rose-600"
                            >
                                <Icon name="logout" className="h-3.5 w-3.5" />
                                Log Out
                            </Link>
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}

const SIDEBAR_KEY = 'matex.sidebar.collapsed';

export default function AuthenticatedLayout({ header, children }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);
    const [collapsed, setCollapsed] = useState(() => {
        if (typeof window === 'undefined') return false;
        return window.localStorage.getItem(SIDEBAR_KEY) === '1';
    });
    const toggleCollapsed = () =>
        setCollapsed((current) => {
            const next = !current;
            try {
                window.localStorage.setItem(SIDEBAR_KEY, next ? '1' : '0');
            } catch {
                /* storage unavailable — keep in-memory only */
            }
            return next;
        });
    const transactionItems = useMemo(() => transactionItemsForRole(user.role), [user.role]);
    const monitoringItems = useMemo(() => monitoringItemsForRole(user.role), [user.role]);
    const masterItems = useMemo(() => masterNavForRole(user.role), [user.role]);
    const showMaster = canAccessMasterData(user.role);

    useEffect(() => {
        document.body.style.overflow = showingNavigationDropdown ? 'hidden' : '';
        return () => {
            document.body.style.overflow = '';
        };
    }, [showingNavigationDropdown]);

    const sidebarProps = { user, transactionItems, monitoringItems, masterItems, showMaster };

    return (
        <div className="min-h-screen bg-canvas">
            {/* Desktop sidebar */}
            <aside
                className={
                    'fixed inset-y-0 left-0 z-40 hidden transition-[width] duration-320 ease-matex lg:block ' +
                    (collapsed ? 'w-[76px]' : 'w-[264px]')
                }
            >
                <Sidebar {...sidebarProps} collapsed={collapsed} />
                <button
                    type="button"
                    onClick={toggleCollapsed}
                    aria-label={collapsed ? 'Perlebar sidebar' : 'Ciutkan sidebar'}
                    title={collapsed ? 'Perlebar sidebar' : 'Ciutkan sidebar'}
                    className="absolute -right-3.5 top-7 z-10 flex h-7 w-7 items-center justify-center rounded-full border border-line bg-surface text-ink-muted shadow-panel transition hover:border-brand-line hover:bg-brand-muted hover:text-brand-deep"
                >
                    <svg
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden="true"
                        className={'h-4 w-4 transition-transform duration-320 ease-matex ' + (collapsed ? 'rotate-180' : '')}
                    >
                        <path
                            fillRule="evenodd"
                            d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                            clipRule="evenodd"
                        />
                    </svg>
                </button>
            </aside>

            {/* Mobile drawer */}
            {showingNavigationDropdown && (
                <div className="fixed inset-0 z-50 lg:hidden">
                    <div
                        className="absolute inset-0 animate-fade-in bg-ink/25 backdrop-blur-sm"
                        onClick={() => setShowingNavigationDropdown(false)}
                    />
                    <aside className="absolute inset-y-0 left-0 w-[280px] max-w-[85vw] animate-fade-in shadow-2xl">
                        <Sidebar
                            {...sidebarProps}
                            onNavigate={(e) => {
                                if (e.target.closest('[data-keep-open]')) return;
                                if (e.target.closest('a,button')) setShowingNavigationDropdown(false);
                            }}
                        />
                    </aside>
                </div>
            )}

            <div
                className={
                    'transition-[padding] duration-320 ease-matex ' + (collapsed ? 'lg:ps-[76px]' : 'lg:ps-[264px]')
                }
            >
                {/* Top bar */}
                <div className="sticky top-0 z-30 border-b border-line/70 bg-canvas/80 backdrop-blur-xl">
                    <div className="mx-auto flex h-16 w-full max-w-[1600px] items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                        <div className="flex items-center gap-3">
                            <button
                                type="button"
                                onClick={() => setShowingNavigationDropdown(true)}
                                className="pressable inline-flex items-center justify-center rounded-xl border border-line bg-surface p-2 text-ink-soft hover:text-brand lg:hidden"
                                aria-label="Buka menu"
                            >
                                <Icon name="menu" className="h-5 w-5" />
                            </button>
                            <Link href={route('dashboard')} className="flex items-center gap-2 lg:hidden">
                                <ApplicationLogo className="h-8 w-8" />
                                <span className="font-display text-base font-extrabold tracking-[0.06em] text-ink">
                                    MATEX
                                </span>
                            </Link>
                            <div className="hidden items-center gap-2 lg:flex">
                                <span className="h-2 w-2 rounded-full bg-emerald-300 shadow-[0_0_0_4px_rgba(110,231,183,0.25)]" />
                                <span className="text-sm font-medium text-ink-muted">
                                    Halo, <span className="font-semibold text-ink">{user.name}</span>
                                </span>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            {user.company?.name && (
                                <span className="hidden rounded-full bg-brand-muted px-3 py-1 text-xs font-semibold text-brand-deep md:inline-flex">
                                    {user.company.name}
                                </span>
                            )}
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <button
                                        type="button"
                                        className="pressable inline-flex items-center gap-2 rounded-full border border-line bg-surface py-1 pe-3 ps-1 text-sm font-semibold text-ink-soft hover:border-brand-line"
                                    >
                                        <span className="flex h-7 w-7 items-center justify-center rounded-full bg-brand-muted text-[0.6875rem] font-bold uppercase text-brand-deep">
                                            {(user.name || '?').trim().charAt(0)}
                                        </span>
                                        <span className="hidden sm:inline">{user.role_label}</span>
                                        <svg className="h-4 w-4 text-ink-faint" viewBox="0 0 20 20" fill="currentColor">
                                            <path
                                                fillRule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clipRule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </Dropdown.Trigger>
                                <Dropdown.Content contentClasses="bg-surface">
                                    <div className="mb-1 border-b border-line px-3 pb-2.5 pt-2">
                                        <p className="truncate text-sm font-semibold text-ink">{user.name}</p>
                                        <p className="truncate text-xs text-ink-muted">{user.email}</p>
                                    </div>
                                    <Dropdown.Link href={route('profile.edit')}>Profile</Dropdown.Link>
                                    <Dropdown.Link href={route('logout')} method="post" as="button">
                                        Log Out
                                    </Dropdown.Link>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>
                    </div>
                </div>

                {header && (
                    <header className="ui-page !pb-0">
                        <div className="ui-hero animate-fade-up">{header}</div>
                    </header>
                )}

                <FlashMessage />
                <main className="animate-fade-in">{children}</main>
            </div>
        </div>
    );
}
