@echo off
REM Sanruth ERP - Setup Script for Daily Sales Report Scheduled Task

SET TASK_NAME=SanruthDailySalesReport
SET BATCH_PATH=c:\handover\billing_sanruth-main\tools\run_daily_sales_report.bat

echo Setting up Windows Scheduled Task '%TASK_NAME%' to run daily at 9:00 PM (21:00)...

schtasks /Create /TN "%TASK_NAME%" /TR "%BATCH_PATH%" /SC DAILY /ST 21:00 /F

IF %ERRORLEVEL% EQU 0 (
    echo.
    echo SUCCESS: Scheduled task '%TASK_NAME%' registered successfully for 9:00 PM daily.
) ELSE (
    echo.
    echo ERROR: Failed to register scheduled task. Please run as Administrator.
)

pause
