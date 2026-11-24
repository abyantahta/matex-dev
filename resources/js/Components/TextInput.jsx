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
                'rounded-xl border-line bg-surface text-sm text-ink shadow-none transition duration-220 ease-matex placeholder:text-ink-faint hover:border-brand-line focus:border-brand focus:ring-4 focus:ring-brand/15 ' +
                className
            }
            ref={localRef}
        />
    );
});
