/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/Livewire/**/*.php",
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#f4f6ff',
          100: '#e7ecff',
          200: '#ced8ff',
          300: '#a5baff',
          400: '#7594ff',
          500: '#4063ff',
          600: '#1c39f4',
          700: '#0e23da',
          800: '#1122b1',
          900: '#14238b',
          950: '#0e1552',
        },
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
