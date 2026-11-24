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
                'group relative inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium leading-5 transition duration-220 ease-matex focus:outline-none ' +
                (active
                    ? 'bg-canvas text-ink'
                    : 'text-ink-muted hover:bg-canvas-soft hover:text-ink') +
                (className ? ` ${className}` : '')
            }
        >
            <span>{children}</span>
            <span
                className={
                    'hidden'
                }
            />
        </Link>
    );
}
