import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Support/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                soft: '0 1px 2px rgb(15 23 42 / 0.04), 0 4px 16px -6px rgb(15 23 42 / 0.08)',
                glow: '0 10px 30px -10px rgb(16 185 129 / 0.45)',
            },
            keyframes: {
                'fade-in-up': {
                    '0%': { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'scale-in': {
                    '0%': { opacity: '0', transform: 'scale(.96)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-6px)' },
                },
                'typing-bounce': {
                    '0%, 60%, 100%': { transform: 'translateY(0)', opacity: '.4' },
                    '30%': { transform: 'translateY(-4px)', opacity: '1' },
                },
            },
            animation: {
                'fade-in-up': 'fade-in-up .4s ease-out both',
                'scale-in': 'scale-in .25s cubic-bezier(.2,.8,.2,1) both',
                float: 'float 5s ease-in-out infinite',
                'typing-bounce': 'typing-bounce 1.1s ease-in-out infinite',
            },
        },
    },

    plugins: [forms],
};
