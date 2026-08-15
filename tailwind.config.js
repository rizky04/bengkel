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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Tema Bengkel: biru (profesional/otomotif) + amber (aksen).
            // Semua warna terpusat di sini — ganti nilai HEX untuk ganti tema seluruh app.
            colors: {
                brand: {
                    DEFAULT: '#2563eb', // blue-600
                    hover: '#1d4ed8',   // blue-700
                    dark: '#172554',    // blue-950 — sidebar/nav aktif
                    darker: '#0f1e42',
                    light: '#dbeafe',   // blue-100
                },
                accent: {
                    DEFAULT: '#f59e0b', // amber-500
                    light: '#fef3c7',   // amber-100
                },
                surface: '#f8fafc', // slate-50 — latar konten (airy)
            },
        },
    },

    plugins: [forms],
};
