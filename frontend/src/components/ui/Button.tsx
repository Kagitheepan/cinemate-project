import type { ReactNode, ButtonHTMLAttributes } from 'react';

type ButtonVariant = 'primary' | 'secondary' | 'outline' | 'ghost' | 'destructive';
type ButtonSize = 'sm' | 'md' | 'lg' | 'icon';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: ButtonVariant;
    size?: ButtonSize;
    fullWidth?: boolean;
    className?: string;
    children: ReactNode;
}

const Button = ({
    variant = 'primary',
    size = 'md',
    fullWidth = false,
    className = '',
    children,
    ...props
}: ButtonProps) => {
    const baseStyles = 'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-purple-500/50';

    const variants: Record<ButtonVariant, string> = {
        primary: 'bg-purple-600 hover:bg-purple-700 text-white shadow-lg shadow-purple-500/20',
        secondary: 'bg-white/10 hover:bg-white/20 text-white backdrop-blur-md border border-white/10',
        outline: 'border-2 border-purple-500 text-purple-400 hover:bg-purple-500/10',
        ghost: 'text-gray-400 hover:text-white hover:bg-white/5',
        destructive: 'bg-red-500 hover:bg-red-600 text-white shadow-lg shadow-red-500/20'
    };

    const sizes: Record<ButtonSize, string> = {
        sm: 'px-3 py-1.5 text-xs',
        md: 'px-6 py-2.5 text-sm',
        lg: 'px-8 py-3.5 text-base',
        icon: 'p-2'
    };

    const combinedClasses = `${baseStyles} ${variants[variant]} ${sizes[size]} ${fullWidth ? 'w-full' : ''} ${className}`;

    return (
        <button className={combinedClasses.trim()} {...props}>
            {children}
        </button>
    );
};

export default Button;

