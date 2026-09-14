import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },
            keyframes: {
                'fade-in-up': {
                    '0%': { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'typing-bounce': {
                    '0%, 60%, 100%': { transform: 'translateY(0)', opacity: '.4' },
                    '30%': { transform: 'translateY(-4px)', opacity: '1' },
                },
            },
            animation: {
                'fade-in-up': 'fade-in-up .4s ease-out both',
                'typing-bounce': 'typing-bounce 1.1s ease-in-out infinite',
            },
        },
    },

    plugins: [forms],
};
