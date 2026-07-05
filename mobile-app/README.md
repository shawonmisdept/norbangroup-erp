# Norban Portal — Capacitor Android App

Single APK for **Employee Portal** + **Rental Driver Portal**.

Full guide: [../docs/MOBILE-APP-APK.md](../docs/MOBILE-APP-APK.md)

## Software needed

1. **Node.js 18+** — https://nodejs.org  
2. **Android Studio** — https://developer.android.com/studio  
3. **JDK 17** — included with Android Studio  

See the main doc for SDK setup, environment variables, and Play Store steps.

## Quick start

```powershell
cd mobile-app
npm install
npx cap add android
npx cap sync android
npx cap open android
```

In Android Studio: select device → **Run**.

## Configuration

Edit `capacitor.config.ts`:

```ts
server: {
  url: 'https://portal.norbangroup.com/app',
}
```

After deploy Laravel `/app` route, this URL must be reachable on HTTPS.

## Debug APK

```powershell
cd android
.\gradlew assembleDebug
```

Output: `android/app/build/outputs/apk/debug/app-debug.apk`

## Release

1. Android Studio → **Build → Generate Signed App Bundle**
2. Update `public/.well-known/assetlinks.json` on server with SHA-256 fingerprint
3. Upload AAB to Play Console

## Notes

- Web/UI changes on server **do not** require rebuilding this APK.
- Rebuild APK only for icon, splash, plugins, or Android SDK updates.
