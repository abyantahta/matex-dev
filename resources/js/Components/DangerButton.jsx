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
                `pressable inline-flex items-center justify-center gap-2 rounded-xl border border-transparent bg-rose-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 focus:ring-offset-2 active:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:translate-y-0 ${className}`
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
