# Mera's Store Customer Android App

This Kotlin app opens the Laravel customer route:

`http://10.0.2.2:8000/user-app`

For a real phone, update `app/src/main/res/values/strings.xml` so `customer_url` points to the hosted Laravel URL, for example:

`https://your-domain.com/user-app`

Build an installable debug APK from this folder:

```powershell
gradle :app:assembleDebug
```

After building, copy:

`app/build/outputs/apk/debug/app-debug.apk`

to:

`../../public/downloads/meras-user-app.apk`