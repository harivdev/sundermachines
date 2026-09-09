@echo off
REM Sanruth ERP - Daily Sales Report Automated Batch Runner

cd /d "c:\handover\billing_sanruth-main"

SET ERP_DIR=c:\handover\billing_sanruth-main
SET LOG_DIR=%ERP_DIR%\logs
IF NOT EXIST "%LOG_DIR%" mkdir "%LOG_DIR%"

echo -------------------------------------------------- >> "%LOG_DIR%\daily_sales_report.log"
echo [%DATE% %TIME%] Starting Daily Sales Report Cron... >> "%LOG_DIR%\daily_sales_report.log"

IF EXIST "C:\servers\php\php-7.4.33-Win32-vc15-x64 (1)\php.exe" (
    "C:\servers\php\php-7.4.33-Win32-vc15-x64 (1)\php.exe" "%ERP_DIR%\tools\daily_sales_report_cron.php" >> "%LOG_DIR%\daily_sales_report.log" 2>&1
) ELSE (
    php "%ERP_DIR%\tools\daily_sales_report_cron.php" >> "%LOG_DIR%\daily_sales_report.log" 2>&1
)

echo [%DATE% %TIME%] Daily Sales Report Cron Completed. >> "%LOG_DIR%\daily_sales_report.log"
