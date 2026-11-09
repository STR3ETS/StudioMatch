# StudioMatch-app (iOS en Android)

Capacitor 8. De schermen zitten in de app zelf ([mobile/app](app)), de gegevens komen van de server (`api/app/*`). Zie de sectie "Mobiele app" in [CLAUDE.md](../CLAUDE.md) voor de opzet.

## Bouwen

Het serveradres gaat mee in de build, zowel in de schermen als in de Capacitor-config. Gebruik dus in één shell dezelfde waarde voor beide stappen.

```powershell
# Live (standaard https://studiomatch.nl)
npm run app:build
npx cap sync

# Tegen een lokale server, telefoon via USB (127.0.0.1 is voor de webview een veilig adres, dus ook de microfoon/camera werken)
php artisan serve --port=8001
$env:STUDIOMATCH_API_URL='http://127.0.0.1:8001'; npm run app:build; npx cap copy android
adb reverse tcp:8001 tcp:8001
```

`npm run app:build` draait eerst `php artisan app:texts`: de teksten uit `lang/{nl,en}/app.php` gaan als `mobile/app/texts.json` mee in de build (voor het inlogscherm zonder verbinding). Beide (`texts.json`, `mobile/www`) staan in .gitignore.

## Android

Android Studio of de command line (Windows):

```powershell
$env:JAVA_HOME='D:\Android\jdk-21'
cd android
.\gradlew.bat assembleDebug      # APK in android/app/build/outputs/apk/debug/
.\gradlew.bat installDebug       # op een aangesloten telefoon
```

`android/local.properties` (pad naar de Android SDK) staat niet in Git; maak hem aan met `sdk.dir=D\:\\Android\\Sdk` of open het project één keer in Android Studio.

## iOS

Alleen op een Mac met Xcode: `npx cap sync ios`, dan `npm run mobile:ios` en bouwen vanuit Xcode.

## Icoon en splash

`npm run mobile:assets` maakt ze opnieuw uit `public/logos/` (wit beeldmerk en logo op Prussian Blue), op de maten die al in de native projecten staan. Na `npx cap add` of een nieuw logo opnieuw draaien.
