const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        'resources/views/**/*.php',
        'resources/css/**/*.css',
        'resources/scss/**/*.scss',
        'resources/**/*.{js,jsx,ts,tsx,vue}'
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
                serif: ['Cormorant Garamond', ...defaultTheme.fontFamily.serif]
            },
            fontSize: {
                xxxs: '.5rem',
                xxs: '.625rem'
            }
        }
    },
    plugins: [require('@tailwindcss/line-clamp')]
};
