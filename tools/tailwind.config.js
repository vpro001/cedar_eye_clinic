// Builds assets/css/admin.css for the admin panel, the sign-in page and the installer.
// Rebuild after changing admin markup:  npx tailwindcss@3 -c tools/tailwind.config.js -i tools/admin.src.css -o assets/css/admin.css --minify
module.exports = {
  content: ['./admin/**/*.php', './install.php'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Plex Sans"', 'system-ui', 'sans-serif'],
        serif: ['Newsreader', 'Georgia', 'serif'],
        mono: ['"Plex Mono"', 'ui-monospace', 'monospace'],
      },
      colors: {
        ink: { DEFAULT: '#142120', soft: '#2a3735', muted: '#56605e' },
        sea: { DEFAULT: '#2c7469', dark: '#1f5a51', tint: '#dceae5', logo: '#5fab9f' },
        paper: { DEFAULT: '#f4f2ec', 2: '#eae6dc' },
        line: '#d9d4c8',
      },
    },
  },
};
