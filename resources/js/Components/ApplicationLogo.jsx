import { useId } from 'react';

export default function ApplicationLogo({ className = '', ...props }) {
    const gid = useId().replace(/:/g, '');

    return (
        <svg
            {...props}
            viewBox="0 0 48 48"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className={className}
            aria-hidden="true"
        >
            <defs>
                <linearGradient id={`mx-${gid}`} x1="0" y1="0" x2="48" y2="48" gradientUnits="userSpaceOnUse">
                    <stop offset="0" stopColor="#8F84EC" />
                    <stop offset="0.55" stopColor="#B79CF0" />
                    <stop offset="1" stopColor="#F3A9C4" />
                </linearGradient>
            </defs>
            <rect width="48" height="48" rx="14" fill={`url(#mx-${gid})`} />
            <path
                d="M13 33V15h4.2l6.8 12.6L30.8 15H35v18h-3.6V21.6L25.3 33h-2.6l-6.1-11.4V33H13Z"
                fill="#fff"
            />
            <circle cx="37.5" cy="10.5" r="2.5" fill="#FDE68A" />
        </svg>
    );
}
