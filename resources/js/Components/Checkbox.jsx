export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded-md border-line-strong text-brand shadow-none transition duration-220 ease-matex focus:ring-brand/40 ' +
                className
            }
        />
    );
}
