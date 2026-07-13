# Martin Sols Mobile

Application Capacitor hybride pour le CRM Martin Sols.

Le login, la barre du haut et le menu lateral sont integres dans l'app mobile.
Les pages et modules du CRM restent charges en WebView via une session web
temporaire creee par l'API mobile Laravel.

URL CRM par defaut :

```text
https://crm.jp2.fr
```

Le code metier reste dans le CRM Laravel. Les mises a jour des modules web sont
visibles dans l'app sans reconstruire l'APK, tant que le cadre mobile ne change
pas.

## Commandes

```bash
npm install
npm run build
npx cap sync android
npx cap open android
```

## Android APK debug

```bash
npm run build
npx cap sync android
./android/gradlew.bat -p android assembleDebug
```

APK genere :

```text
mobile/android/app/build/outputs/apk/debug/app-debug.apk
```

## iOS

La plateforme iOS se prepare avec Capacitor, mais l'ouverture et la compilation iOS doivent se faire sur macOS avec Xcode :

```bash
npx cap add ios
npx cap sync ios
npx cap open ios
```
