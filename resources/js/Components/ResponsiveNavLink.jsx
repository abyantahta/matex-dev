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
            className={`flex w-full items-start border-l-2 py-2.5 pe-4 ps-4 text-[0.9375rem] font-medium transition duration-220 ease-matex focus:outline-none ${
                active
                    ? 'border-ink bg-canvas text-ink'
                    : 'border-transparent text-ink-muted hover:bg-canvas-soft hover:text-ink'
            } ${className}`}
        >
            {children}
        </Link>
    );
}
