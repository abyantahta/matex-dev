# Shared UI Components (resources/js/Components)

Framework: React 18 + Inertia.js (Laravel backend), Tailwind CSS 3 + @tailwindcss/forms. No component library — custom primitives. Utility classes `.ui-panel`, `.ui-eyebrow`, `.ui-tab` etc. live in resources/css/app.css (see theme.md).


### `resources/js/Components/ApplicationLogo.jsx`

```jsx
export default function ApplicationLogo({ className = '', ...props }) {
    return (
        <svg
            {...props}
            viewBox="0 0 48 48"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className={className}
            aria-hidden="true"
        >
            <rect width="48" height="48" rx="12" className="fill-brand" />
            <path
                d="M12 34V14h4.6l7.4 14.2L31.4 14H36v20h-3.8V21.2L25.4 34h-2.8L15.8 21.2V34H12Z"
                className="fill-white"
            />
            <path d="M12 36.5h24" stroke="currentColor" strokeWidth="1.5" className="text-brand-bright/80" />
        </svg>
    );
}
```


### `resources/js/Components/Checkbox.jsx`

```jsx
export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-line text-brand shadow-sm transition duration-220 ease-matex focus:ring-brand/40 ' +
                className
            }
        />
    );
}
```


### `resources/js/Components/DangerButton.jsx`

```jsx
export default function DangerButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `pressable inline-flex items-center justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2.5 text-sm font-semibold tracking-wide text-white shadow-sm hover:bg-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-400 focus:ring-offset-2 active:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:translate-y-0 ${className}`
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
```


### `resources/js/Components/Dropdown.jsx`

```jsx
import { Transition } from '@headlessui/react';
import { Link } from '@inertiajs/react';
import { createContext, useContext, useState } from 'react';

const DropDownContext = createContext();

const Dropdown = ({ children }) => {
    const [open, setOpen] = useState(false);

    const toggleOpen = () => {
        setOpen((previousState) => !previousState);
    };

    return (
        <DropDownContext.Provider value={{ open, setOpen, toggleOpen }}>
            <div className="relative">{children}</div>
        </DropDownContext.Provider>
    );
};

const Trigger = ({ children }) => {
    const { open, setOpen, toggleOpen } = useContext(DropDownContext);

    return (
        <>
            <div onClick={toggleOpen}>{children}</div>

            {open && (
                <div
                    className="fixed inset-0 z-40"
                    onClick={() => setOpen(false)}
                ></div>
            )}
        </>
    );
};

const Content = ({
    align = 'right',
    width = '48',
    contentClasses = 'py-1 bg-surface',
    children,
}) => {
    const { open, setOpen } = useContext(DropDownContext);

    let alignmentClasses = 'origin-top';

    if (align === 'left') {
        alignmentClasses = 'ltr:origin-top-left rtl:origin-top-right start-0';
    } else if (align === 'right') {
        alignmentClasses = 'ltr:origin-top-right rtl:origin-top-left end-0';
    }

    let widthClasses = '';

    if (width === '48') {
        widthClasses = 'w-48';
    } else if (width === '56') {
        widthClasses = 'w-56';
    } else if (width === '64') {
        widthClasses = 'w-64';
    } else if (width === '72') {
        widthClasses = 'w-72';
    } else {
        widthClasses = width;
    }

    return (
        <>
            <Transition
                show={open}
                enter="transition ease-out duration-200"
                enterFrom="opacity-0 scale-95"
                enterTo="opacity-100 scale-100"
                leave="transition ease-in duration-75"
                leaveFrom="opacity-100 scale-100"
                leaveTo="opacity-0 scale-95"
            >
                <div
                    className={`absolute z-50 mt-2 rounded-md shadow-lg ${alignmentClasses} ${widthClasses}`}
                    onClick={() => setOpen(false)}
                >
                    <div
                        className={
                            `rounded-md ring-1 ring-black ring-opacity-5 ` +
                            contentClasses
                        }
                    >
                        {children}
                    </div>
                </div>
            </Transition>
        </>
    );
};

const DropdownLink = ({ className = '', children, ...props }) => {
    return (
        <Link
            {...props}
            className={
                'block w-full px-4 py-2.5 text-start text-sm font-medium leading-5 text-ink-soft transition duration-220 ease-matex hover:bg-brand-muted hover:text-brand-deep focus:bg-brand-muted focus:text-brand-deep focus:outline-none ' +
                className
            }
        >
            {children}
        </Link>
    );
};

Dropdown.Trigger = Trigger;
Dropdown.Content = Content;
Dropdown.Link = DropdownLink;

export default Dropdown;
```


### `resources/js/Components/EmptyState.jsx`

```jsx
export default function EmptyState({ title, description }) {
    return (
        <div className="animate-fade-up rounded-panel border border-dashed border-line bg-canvas-soft px-6 py-12 text-center">
            <h3 className="font-display text-sm font-semibold text-ink">{title}</h3>
            {description && (
                <p className="mx-auto mt-2 max-w-md text-sm text-ink-muted">{description}</p>
            )}
        </div>
    );
}
```


### `resources/js/Components/FlashMessage.jsx`

```jsx
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function FlashMessage() {
    const { flash } = usePage().props;
    const [visible, setVisible] = useState(false);
    const message = flash?.success || flash?.error;
    const isError = Boolean(flash?.error);

    useEffect(() => {
        if (message) {
            setVisible(true);
            const timer = setTimeout(() => setVisible(false), 4500);
            return () => clearTimeout(timer);
        }
    }, [message]);

    if (!visible || !message) {
        return null;
    }

    return (
        <div className="ui-page !py-3">
            <div
                className={`animate-flash-in rounded-panel border px-4 py-3 text-sm font-medium shadow-panel ${
                    isError
                        ? 'border-rose-200 bg-rose-50 text-rose-800'
                        : 'border-brand-line bg-brand-muted text-brand-deep'
                }`}
            >
                {message}
            </div>
        </div>
    );
}
```


### `resources/js/Components/InputError.jsx`

```jsx
export default function InputError({ message, className = '', ...props }) {
    return message ? (
        <p
            {...props}
            className={'text-sm text-red-600 ' + className}
        >
            {message}
        </p>
    ) : null;
}
```


### `resources/js/Components/InputLabel.jsx`

```jsx
export default function InputLabel({
    value,
    className = '',
    children,
    ...props
}) {
    return (
        <label
            {...props}
            className={`block text-sm font-semibold text-ink-soft ${className}`}
        >
            {value ? value : children}
        </label>
    );
}
```


### `resources/js/Components/Modal.jsx`

```jsx
import {
    Dialog,
    DialogPanel,
    Transition,
    TransitionChild,
} from '@headlessui/react';

export default function Modal({
    children,
    show = false,
    maxWidth = '2xl',
    closeable = true,
    onClose = () => {},
}) {
    const close = () => {
        if (closeable) {
            onClose();
        }
    };

    const maxWidthClass = {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[maxWidth];

    return (
        <Transition show={show} leave="duration-200">
            <Dialog
                as="div"
                id="modal"
                className="fixed inset-0 z-50 flex transform items-center overflow-y-auto px-4 py-6 transition-all sm:px-0"
                onClose={close}
            >
                <TransitionChild
                    enter="ease-out duration-300"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-200"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="absolute inset-0 bg-gray-500/75" />
                </TransitionChild>

                <TransitionChild
                    enter="ease-out duration-300"
                    enterFrom="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enterTo="opacity-100 translate-y-0 sm:scale-100"
                    leave="ease-in duration-200"
                    leaveFrom="opacity-100 translate-y-0 sm:scale-100"
                    leaveTo="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <DialogPanel
                        className={`mb-6 transform overflow-hidden rounded-lg bg-surface shadow-xl transition-all sm:mx-auto sm:w-full ${maxWidthClass}`}
                    >
                        {children}
                    </DialogPanel>
                </TransitionChild>
            </Dialog>
        </Transition>
    );
}
```


### `resources/js/Components/NavLink.jsx`

```jsx
import { Link } from '@inertiajs/react';

export default function NavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={
                'group relative inline-flex items-center px-1 pt-1 text-sm font-medium leading-5 transition duration-220 ease-matex focus:outline-none ' +
                (active
                    ? 'font-semibold text-brand'
                    : 'text-ink-muted hover:text-ink') +
                (className ? ` ${className}` : '')
            }
        >
            <span>{children}</span>
            <span
                className={
                    'absolute inset-x-0 -bottom-px h-0.5 origin-left rounded-full bg-brand transition duration-320 ease-matex ' +
                    (active
                        ? 'scale-x-100 opacity-100'
                        : 'scale-x-0 opacity-0 group-hover:scale-x-100 group-hover:opacity-70')
                }
            />
        </Link>
    );
}
```


### `resources/js/Components/Pagination.jsx`

```jsx
import { Link } from '@inertiajs/react';

export default function Pagination({ paginator }) {
    const links = Array.isArray(paginator?.links)
        ? paginator.links
        : paginator?.meta?.links || [];

    if (!links.length || links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-end gap-1 border-t border-line px-4 py-3">
            {links.map((link, index) => {
                const label = String(link.label)
                    .replace('&laquo;', '«')
                    .replace('&raquo;', '»');

                if (!link.url) {
                    return (
                        <span
                            key={`${label}-${index}`}
                            className="rounded-md px-2.5 py-1 text-sm text-ink-faint"
                            dangerouslySetInnerHTML={{ __html: label }}
                        />
                    );
                }

                return (
                    <Link
                        key={`${label}-${index}`}
                        href={link.url}
                        preserveState
                        preserveScroll
                        className={`rounded-md px-2.5 py-1 text-sm transition duration-220 ease-matex ${
                            link.active
                                ? 'bg-brand font-semibold text-white'
                                : 'text-ink-soft hover:bg-canvas-soft'
                        }`}
                        dangerouslySetInnerHTML={{ __html: label }}
                    />
                );
            })}
        </div>
    );
}
```


### `resources/js/Components/PrimaryButton.jsx`

```jsx
export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `pressable inline-flex items-center justify-center rounded-md border border-transparent bg-brand px-4 py-2.5 text-sm font-semibold tracking-wide text-white shadow-sm hover:bg-brand-deep focus:outline-none focus:ring-2 focus:ring-brand/40 focus:ring-offset-2 active:bg-brand-deep disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:translate-y-0 ${className}`
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
```


### `resources/js/Components/ResponsiveNavLink.jsx`

```jsx
import { Link } from '@inertiajs/react';

export default function ResponsiveNavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={`flex w-full items-start border-l-4 py-2.5 pe-4 ps-3 text-base font-medium transition duration-220 ease-matex focus:outline-none ${
                active
                    ? 'border-brand bg-brand-muted text-brand-deep'
                    : 'border-transparent text-ink-muted hover:border-brand-line hover:bg-canvas-soft hover:text-ink'
            } ${className}`}
        >
            {children}
        </Link>
    );
}
```


### `resources/js/Components/SearchableSelect.jsx`

```jsx
import { useEffect, useMemo, useRef, useState } from 'react';

export default function SearchableSelect({
    options = [],
    value,
    onChange,
    placeholder = 'Cari & pilih...',
    getOptionLabel = (o) => o.label,
    getOptionValue = (o) => String(o.value),
    className = '',
    autoFocus = false,
    focusToken = 0,
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const rootRef = useRef(null);
    const inputRef = useRef(null);

    const selected = useMemo(
        () => options.find((o) => getOptionValue(o) === String(value ?? '')),
        [options, value, getOptionValue],
    );

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();
        if (!q) {
            return options.slice(0, 50);
        }
        return options
            .filter((o) => getOptionLabel(o).toLowerCase().includes(q))
            .slice(0, 50);
    }, [options, query, getOptionLabel]);

    useEffect(() => {
        if (!open) {
            setQuery('');
        }
    }, [open]);

    useEffect(() => {
        if (!autoFocus) {
            return undefined;
        }
        setOpen(true);
        const timer = setTimeout(() => inputRef.current?.focus(), 0);
        return () => clearTimeout(timer);
    }, [autoFocus, focusToken]);

    useEffect(() => {
        const onDocClick = (e) => {
            if (rootRef.current && !rootRef.current.contains(e.target)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', onDocClick);
        return () => document.removeEventListener('mousedown', onDocClick);
    }, []);

    return (
        <div ref={rootRef} className={`relative ${className}`}>
            <button
                type="button"
                className="mt-1 flex w-full items-center justify-between rounded-md border border-line bg-surface px-3 py-2 text-left text-sm shadow-sm transition duration-220 ease-matex hover:border-brand-line focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                onClick={() => {
                    setOpen((v) => !v);
                    setTimeout(() => inputRef.current?.focus(), 0);
                }}
            >
                <span className={selected ? 'text-ink' : 'text-ink-faint'}>
                    {selected ? getOptionLabel(selected) : placeholder}
                </span>
                <svg className="h-4 w-4 text-ink-faint" viewBox="0 0 20 20" fill="currentColor">
                    <path
                        fillRule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clipRule="evenodd"
                    />
                </svg>
            </button>

            {open && (
                <div className="absolute z-30 mt-1 w-full overflow-hidden rounded-md border border-line bg-surface shadow-lg">
                    <div className="border-b border-line p-2">
                        <input
                            ref={inputRef}
                            type="text"
                            className="w-full rounded-md border-line text-sm"
                            placeholder="Ketik untuk mencari..."
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Escape') {
                                    setOpen(false);
                                }
                                if (e.key === 'Enter' && filtered[0]) {
                                    e.preventDefault();
                                    onChange(getOptionValue(filtered[0]));
                                    setOpen(false);
                                }
                            }}
                        />
                    </div>
                    <ul className="max-h-56 overflow-auto py-1">
                        {filtered.length === 0 ? (
                            <li className="px-3 py-2 text-sm text-ink-muted">Tidak ada hasil</li>
                        ) : (
                            filtered.map((option) => {
                                const optionValue = getOptionValue(option);
                                const active = optionValue === String(value ?? '');
                                return (
                                    <li key={optionValue}>
                                        <button
                                            type="button"
                                            className={`block w-full px-3 py-2 text-left text-sm transition duration-220 ease-matex hover:bg-brand-muted ${
                                                active
                                                    ? 'bg-brand-muted font-medium text-brand-deep'
                                                    : 'text-ink-soft'
                                            }`}
                                            onClick={() => {
                                                onChange(optionValue);
                                                setOpen(false);
                                            }}
                                        >
                                            {getOptionLabel(option)}
                                        </button>
                                    </li>
                                );
                            })
                        )}
                    </ul>
                    {value && (
                        <button
                            type="button"
                            className="w-full border-t border-line px-3 py-2 text-left text-xs text-rose-600 hover:bg-rose-50"
                            onClick={() => {
                                onChange('');
                                setOpen(false);
                            }}
                        >
                            Hapus pilihan
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}
```


### `resources/js/Components/SecondaryButton.jsx`

```jsx
export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            type={type}
            className={
                `pressable inline-flex items-center justify-center rounded-md border border-line bg-surface px-4 py-2.5 text-sm font-semibold tracking-wide text-ink-soft shadow-sm hover:border-brand-line hover:bg-canvas-soft focus:outline-none focus:ring-2 focus:ring-brand/30 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:translate-y-0 ${className}`
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
```


### `resources/js/Components/StatusBadge.jsx`

```jsx
const COLORS = {
    slate: 'bg-canvas-soft text-ink-soft ring-1 ring-line',
    amber: 'bg-amber-50 text-amber-900 ring-1 ring-amber-200/80',
    orange: 'bg-copper-muted text-copper ring-1 ring-copper-line',
    blue: 'bg-sky-50 text-sky-900 ring-1 ring-sky-200/80',
    indigo: 'bg-indigo-50 text-indigo-900 ring-1 ring-indigo-200/70',
    emerald: 'bg-brand-muted text-brand-deep ring-1 ring-brand-line',
    rose: 'bg-rose-50 text-rose-800 ring-1 ring-rose-200/80',
};

const PO_STATUS = {
    draft: { label: 'Draft', color: 'slate' },
    awaiting_rm_confirm: { label: 'Menunggu Konfirmasi RM', color: 'amber' },
    awaiting_purchasing_ok: { label: 'Menunggu OK Purchasing', color: 'orange' },
    confirmed: { label: 'Confirmed', color: 'blue' },
    in_progress: { label: 'Dalam Proses', color: 'indigo' },
    completed: { label: 'Selesai', color: 'emerald' },
    cancelled: { label: 'Dibatalkan', color: 'rose' },
};

const SCHEDULE_STATUS = {
    planned: { label: 'Terjadwal', color: 'slate' },
    ship_confirmed: { label: 'Dikirim ke OHP', color: 'amber' },
    ohp_ok: { label: 'OK OHP', color: 'blue' },
    received: { label: 'Received', color: 'emerald' },
};

const QAD_STATUS = {
    pending: { label: 'Pending', color: 'amber' },
    success: { label: 'QAD Sukses', color: 'emerald' },
    failed: { label: 'QAD Gagal', color: 'rose' },
};

const FULFILLMENT_STATUS = {
    open: { label: 'Open', color: 'amber' },
    closed: { label: 'Closed', color: 'emerald' },
};

export function resolveStatus(type, value) {
    const map =
        type === 'po'
            ? PO_STATUS
            : type === 'schedule'
              ? SCHEDULE_STATUS
              : type === 'fulfillment'
                ? FULFILLMENT_STATUS
                : QAD_STATUS;
    return map[value] || { label: value, color: 'slate' };
}

export default function StatusBadge({ type = 'po', value, className = '', title }) {
    const meta = resolveStatus(type, value);

    return (
        <span
            title={title}
            className={`inline-flex items-center rounded-md px-2 py-0.5 text-[0.6875rem] font-semibold tracking-wide ${COLORS[meta.color]} ${className}`}
        >
            {meta.label}
        </span>
    );
}
```


### `resources/js/Components/TextInput.jsx`

```jsx
import { forwardRef, useEffect, useImperativeHandle, useRef } from 'react';

export default forwardRef(function TextInput(
    { type = 'text', className = '', isFocused = false, ...props },
    ref,
) {
    const localRef = useRef(null);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <input
            {...props}
            type={type}
            className={
                'rounded-md border-line bg-surface text-ink shadow-sm transition duration-220 ease-matex placeholder:text-ink-faint hover:border-line-strong focus:border-brand focus:ring-brand/30 ' +
                className
            }
            ref={localRef}
        />
    );
});
```


### `resources/js/Components/Timeline.jsx`

```jsx
export default function Timeline({ logs = [] }) {
    if (!logs.length) {
        return <p className="text-sm text-ink-muted">Belum ada aktivitas tercatat.</p>;
    }

    return (
        <ol className="relative ms-3 space-y-4 border-s border-line">
            {logs.map((log, index) => (
                <li
                    key={log.id}
                    className="animate-fade-up ms-6"
                    style={{ animationDelay: `${Math.min(index, 6) * 40}ms` }}
                >
                    <span className="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border-2 border-surface bg-brand shadow-sm" />
                    <div className="rounded-panel border border-line bg-surface p-3 transition duration-220 ease-matex hover:border-brand-line hover:shadow-panel">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <p className="font-display text-sm font-semibold text-ink">
                                {log.action}
                            </p>
                            <time className="text-xs text-ink-muted">
                                {new Date(log.created_at).toLocaleString('id-ID')}
                            </time>
                        </div>
                        <p className="mt-1 text-xs text-ink-muted">
                            {log.from_status || '—'} → {log.to_status}
                            {log.user ? ` · ${log.user.name}` : ''}
                        </p>
                        {log.notes && (
                            <p className="mt-2 text-sm text-ink-soft">{log.notes}</p>
                        )}
                    </div>
                </li>
            ))}
        </ol>
    );
}
```

