// tailwind.config.js
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./app/Views/**/*.php",         // Scan PHP files in the Views directory
    "./app/Views/*.php",            // Scan PHP files directly in Views
    // Add any other paths where you might use Tailwind classes, e.g., JS files
    // "./public/js/**/*.js"
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}