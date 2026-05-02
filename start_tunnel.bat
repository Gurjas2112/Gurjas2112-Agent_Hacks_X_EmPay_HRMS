@echo off
title EmPay HRMS - Ngrok Tunnel
echo ---------------------------------------------------
echo  EmPay HRMS - Smart Human Resource Management System
echo  Starting Ngrok Tunnel for Mobile Access...
echo ---------------------------------------------------
echo.
echo  [Step 1] Detecting server on port 80...
netstat -ano | findstr :80 > nul
if %errorlevel% neq 0 (
    echo  [WARNING] Apache (Port 80) does not seem to be running!
    echo  Please start Apache from XAMPP Control Panel first.
    echo.
)

echo  [Step 2] Starting ngrok...
echo  (Your public URL will be displayed below)
echo.
npx ngrok http 80
pause
