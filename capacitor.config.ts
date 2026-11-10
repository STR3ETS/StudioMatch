import type { CapacitorConfig } from '@capacitor/cli';

// De schermen zitten in de app zelf (mobile/app, gebouwd naar mobile/www); de server levert de gegevens
// (api/app/*) en de pagina's die nog niet in de app zitten, die openen in dezelfde webview. Het adres van
// de server gaat mee in de build van de schermen én in deze config, dus beide met dezelfde waarde:
//   STUDIOMATCH_API_URL=http://127.0.0.1:8000 npm run app:build && npx cap copy android
const serverUrl = process.env.STUDIOMATCH_API_URL ?? 'https://studiomatch.nl';
const insecure = serverUrl.startsWith('http://');
// Een technisch Plesk-domein (*.plesk.page) toont elke browser eerst een waarschuwingspagina (428) met een
// "Continue"-knop; de app kan die niet wegklikken. Zonder "Mozilla" in de user agent komt hij er niet. Laravel
// herkent de app nog steeds aan "StudioMatchApp" (InApp). Alleen voor testbuilds tegen zo'n domein.
const technicalDomain = new URL(serverUrl).hostname.endsWith('.plesk.page');

const config: CapacitorConfig = {
    appId: 'nl.studiomatch.app',
    appName: 'StudioMatch',
    webDir: 'mobile/www',
    backgroundColor: '#101529',
    // Laravel herkent de app hieraan.
    appendUserAgent: 'StudioMatchApp',
    ...(technicalDomain ? { overrideUserAgent: 'StudioMatchApp (testserver)' } : {}),
    server: {
        // http://localhost bij een testserver over http, anders blokkeert de webview de API (gemengde inhoud).
        androidScheme: insecure ? 'http' : 'https',
        cleartext: insecure,
        // Alleen de hostnaam: Capacitor vergelijkt zonder poort.
        allowNavigation: [new URL(serverUrl).hostname],
        errorPath: 'offline.html',
    },
    ios: {
        contentInset: 'never',
    },
    plugins: {
        SplashScreen: {
            launchShowDuration: 600,
            backgroundColor: '#101529',
            showSpinner: false,
        },
        FirebaseMessaging: {
            // iOS: geen melding als de app open staat (Android doet dat vanzelf niet); main.js ververst
            // dan de teller en het scherm.
            presentationOptions: [],
        },
    },
};

export default config;
