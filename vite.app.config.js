import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

// De schermen die in de iOS/Android-app zelf zitten (mobile/app), gebouwd naar mobile/www (webDir van
// Capacitor). Het adres van de server gaat mee in de build:
//   STUDIOMATCH_API_URL=http://127.0.0.1:8000 npm run app:build
export default defineConfig({
    root: 'mobile/app',
    base: './',
    // Offline-scherm en logo (mobile/app/public) gaan ongewijzigd mee naar mobile/www.
    publicDir: 'public',
    define: {
        'import.meta.env.VITE_STUDIOMATCH_API': JSON.stringify(process.env.STUDIOMATCH_API_URL ?? 'https://studiomatch.nl'),
    },
    plugins: [tailwindcss()],
    build: {
        outDir: '../www',
        // Elke build begint leeg, zodat oude bestanden niet in de app blijven zitten.
        emptyOutDir: true,
        assetsInlineLimit: 0,
    },
});
