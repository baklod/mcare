/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#5B2D8E',
        'primary-light': '#7B4ABE',
        'primary-dark': '#3d1a6e',
        accent: '#7B4ABE',
        bg: '#F9F6FF',
        'text-dark': '#1a1a2e',
        'text-muted': '#6b6b8a',
      }
    },
  },
  plugins: [],
}