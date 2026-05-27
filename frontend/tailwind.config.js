/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      colors: {
        ink: '#172033',
        mist: '#f6f8fb',
        line: '#d9e1ec',
        brand: '#126b5f',
        berry: '#8c2f5f',
        amber: '#b46912',
      },
    },
  },
  plugins: [],
}
