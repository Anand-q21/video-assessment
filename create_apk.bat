@echo off
echo 📱 Creating APK from your Video Platform API Demo
echo ================================================

echo.
echo 1️⃣ Installing Capacitor...
call npm install -g @capacitor/cli

echo.
echo 2️⃣ Creating Capacitor project...
call npx cap init "Video Platform API" com.videoapi.demo

echo.
echo 3️⃣ Adding Android platform...
call npx cap add android

echo.
echo 4️⃣ Copying your files...
xcopy /E /I /Y public\* www\

echo.
echo 5️⃣ Syncing with Android...
call npx cap sync android

echo.
echo 6️⃣ Opening Android Studio...
call npx cap open android

echo.
echo ✅ Done! 
echo.
echo Next steps in Android Studio:
echo 1. Wait for Gradle sync to complete
echo 2. Click "Build" → "Build Bundle(s) / APK(s)" → "Build APK(s)"
echo 3. APK will be in: app/build/outputs/apk/debug/app-debug.apk
echo.
pause