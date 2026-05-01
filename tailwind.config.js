import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/js/**/*.vue",
        "./resources/js/**/*.js",
        "./app/Livewire/**/*.php",
        "./vendor/lunarphp/stripe-payments/resources/views/**/*.blade.php",
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    50: "#f3fce8",
                    100: "#e5f8cd",
                    200: "#ccf29d",
                    300: "#aae863",
                    400: "#8ed93a",
                    500: "#71C229",
                    600: "#569719",
                    700: "#427318",
                    800: "#365b18",
                    900: "#2e4d18",
                    950: "#162908",
                },
                secondary: {
                    DEFAULT: "#000000",
                },
            },
        },
    },
    plugins: [forms],
};
