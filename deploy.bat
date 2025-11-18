@echo off
echo 🚀 Deploying Video Platform API
echo ===============================

echo.
echo Choose deployment method:
echo 1. Railway (Fastest)
echo 2. Heroku (Popular)
echo 3. Manual setup
echo.
set /p choice="Enter choice (1-3): "

if "%choice%"=="1" goto railway
if "%choice%"=="2" goto heroku
if "%choice%"=="3" goto manual
goto end

:railway
echo.
echo 🚂 Deploying to Railway...
echo.
echo 1. Install Railway CLI:
echo    npm install -g @railway/cli
echo.
echo 2. Login and deploy:
echo    railway login
echo    railway init
echo    railway up
echo.
echo 3. Your app will be available at: https://your-app.railway.app
goto end

:heroku
echo.
echo 🟣 Deploying to Heroku...
echo.
echo 1. Install Heroku CLI from: https://devcenter.heroku.com/articles/heroku-cli
echo.
echo 2. Login and create app:
echo    heroku login
echo    heroku create your-video-api
echo.
echo 3. Add database:
echo    heroku addons:create cleardb:ignite
echo.
echo 4. Deploy:
echo    git add .
echo    git commit -m "Deploy video platform API"
echo    git push heroku main
echo.
echo 5. Run migrations:
echo    heroku run php bin/console doctrine:migrations:migrate
goto end

:manual
echo.
echo 📋 Manual Deployment Steps:
echo.
echo 1. Choose a hosting provider:
echo    - DigitalOcean App Platform
echo    - AWS Elastic Beanstalk
echo    - Google Cloud Run
echo    - Vercel
echo.
echo 2. Upload your project files
echo 3. Set environment variables
echo 4. Run: composer install --no-dev --optimize-autoloader
echo 5. Run: php bin/console doctrine:migrations:migrate
echo 6. Set document root to 'public' folder
goto end

:end
echo.
echo ✅ Deployment instructions complete!
echo.
echo 📱 After deployment, use your live URL with PWA Builder:
echo    https://www.pwabuilder.com
echo.
pause