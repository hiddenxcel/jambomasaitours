/* Tailwind config for every public page EXCEPT index.php (same values the CDN <script> used). */
module.exports = {
  content: [
    './*.php', '!./index.php', '!./sitemap.php', './includes/**/*.php', './ajax/**/*.php', './assets/js/**/*.js',
    './tools/tailwind/dom-classes.txt',
  ],
  theme: { extend: {
    colors: { brand: '#a05e22', brandd: '#7d4817', safari: '#a05e22', dark: '#23362f' },
    fontFamily: {
      heading: ['Nanum Myeongjo', 'Georgia', 'serif'],
      sans: ['Inter', 'Poppins', 'sans-serif'],
      nav: ['Montserrat', 'sans-serif'],
    },
  } },
};
