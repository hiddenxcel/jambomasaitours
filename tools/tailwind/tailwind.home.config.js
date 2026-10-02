/* Tailwind config for index.php only (different palette: safari = forest green, emerald remapped to brand). */
module.exports = {
  content: ['./index.php', './includes/**/*.php', './ajax/**/*.php', './assets/js/**/*.js', './tools/tailwind/dom-classes.txt'],
  theme: { extend: {
    colors: {
      brand: '#a05e22', brandd: '#7d4817', safari: '#3b5c51', forest: '#3b5c51', forestd: '#2c463d',
      cream: '#f4e1c3', creaml: '#faf3e6', dark: '#23362f', card: '#2c463d', glass: 'rgba(255,255,255,0.05)',
      emerald: { 100: '#f1ddc4', 200: '#e6c39b', 300: '#d9a36f', 400: '#c17a3a', 500: '#a05e22', 600: '#7d4817', 700: '#5e3611' },
    },
    fontFamily: {
      heading: ['Nanum Myeongjo', 'Georgia', 'serif'],
      sans: ['Inter', 'Poppins', 'sans-serif'],
      nav: ['Montserrat', 'sans-serif'],
    },
  } },
};
