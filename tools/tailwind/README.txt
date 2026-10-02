Rebuild the Tailwind CSS (replaces the old cdn.tailwindcss.com script). Run from the project root after
adding/changing Tailwind classes in any .php page:

  npm i --no-save tailwindcss@3.4.17
  npx tailwindcss -c tools/tailwind/tailwind.main.config.js -i tools/tailwind/input.css -o assets/css/tailwind.css --minify
  npx tailwindcss -c tools/tailwind/tailwind.home.config.js -i tools/tailwind/input.css -o assets/css/tailwind-home.css --minify

index.php loads tailwind-home.css; every other page loads tailwind.css.
dom-classes.txt = extra class names found in the rendered DOM (JS-added classes); append to it if a JS-only class is missing.
