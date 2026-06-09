@echo off
setlocal

cd /d "%~dp0"

:env
if not defined BASE_URL set "BASE_URL=http://127.0.0.1:8000"

if not defined K6_EMAIL (
  echo Masukkan email untuk login test.
  set /p K6_EMAIL=K6_EMAIL: 
)

if not defined K6_PASSWORD (
  echo.
  echo Masukkan password untuk login test.
  set /p K6_PASSWORD=K6_PASSWORD: 
)

:menu
set "PASSWORD_STATUS=Belum diset"
if defined K6_PASSWORD set "PASSWORD_STATUS=Set"

cls
echo ========================================
echo          K6Testing Runner
echo ========================================
echo.
echo BASE_URL    : %BASE_URL%
echo K6_EMAIL    : %K6_EMAIL%
echo K6_PASSWORD : %PASSWORD_STATUS%
echo.
echo Pilih test yang ingin dijalankan:
echo.
echo 1. Smoke test
echo 2. Baseline test
echo 3. Load test
echo 4. Login-only load test
echo 5. Stress test
echo 6. Spike test
echo 7. Machines load test
echo 8. Custom endpoint test
echo 9. Ubah config login
echo 0. Keluar
echo.

set /p choice=Masukkan pilihan: 

if "%choice%"=="1" set "SCRIPT=scenarios\smoke.js" & set "TEST_NAME=Smoke test" & goto run
if "%choice%"=="2" set "SCRIPT=scenarios\baseline.js" & set "TEST_NAME=Baseline test" & goto run
if "%choice%"=="3" set "SCRIPT=scenarios\load.js" & set "TEST_NAME=Load test" & goto run
if "%choice%"=="4" set "SCRIPT=scenarios\login.js" & set "TEST_NAME=Login-only load test" & goto run
if "%choice%"=="5" set "SCRIPT=scenarios\stress.js" & set "TEST_NAME=Stress test" & goto run
if "%choice%"=="6" set "SCRIPT=scenarios\spike.js" & set "TEST_NAME=Spike test" & goto run
if "%choice%"=="7" set "SCRIPT=scenarios\machines.js" & set "TEST_NAME=Machines load test" & goto run
if "%choice%"=="8" goto endpoint_menu
if "%choice%"=="9" goto config
if "%choice%"=="0" goto end

echo.
echo Pilihan tidak valid.
pause
goto menu

:endpoint_menu
cls
echo ========================================
echo        Custom Endpoint Test
echo ========================================
echo.
echo Pilih endpoint yang ingin dites satu per satu:
echo.
echo 1.  /api/me
echo 2.  /api/profile
echo 3.  /api/dashboard
echo 4.  /api/monitor
echo 5.  /api/downtimes
echo 6.  /api/reportings
echo 7.  /api/reportings/types
echo 8.  /api/reporting-reports
echo 9.  /api/reporting-reports/statuses
echo 10. /api/jobs
echo 11. /api/jobs/new
echo 12. /api/jobs/on-progress
echo 13. /api/jobs/extend
echo 14. /api/jobs/waiting-for-approval
echo 15. /api/jobs/finish
echo 16. /api/mtbf
echo 17. /api/mttr
echo 18. /api/fbdts
echo 19. /api/targets
echo 20. /api/areas
echo 21. /api/divisions
echo 22. /api/groups
echo 23. /api/informants
echo 24. /api/machines
echo 25. /api/operations
echo 26. /api/parts
echo 27. /api/positions
echo 28. /api/reasons
echo 29. /api/serial-numbers
echo 30. /api/shifts
echo 31. /api/technicians
echo 0.  Kembali
echo.

set /p endpoint_choice=Masukkan pilihan endpoint: 

if "%endpoint_choice%"=="1" set "K6_ENDPOINT=me" & set "TEST_NAME=Endpoint test - /api/me" & goto run_endpoint
if "%endpoint_choice%"=="2" set "K6_ENDPOINT=profile" & set "TEST_NAME=Endpoint test - /api/profile" & goto run_endpoint
if "%endpoint_choice%"=="3" set "K6_ENDPOINT=dashboard" & set "TEST_NAME=Endpoint test - /api/dashboard" & goto run_endpoint
if "%endpoint_choice%"=="4" set "K6_ENDPOINT=monitor" & set "TEST_NAME=Endpoint test - /api/monitor" & goto run_endpoint
if "%endpoint_choice%"=="5" set "K6_ENDPOINT=downtimes" & set "TEST_NAME=Endpoint test - /api/downtimes" & goto run_endpoint
if "%endpoint_choice%"=="6" set "K6_ENDPOINT=reportings" & set "TEST_NAME=Endpoint test - /api/reportings" & goto run_endpoint
if "%endpoint_choice%"=="7" set "K6_ENDPOINT=reporting_types" & set "TEST_NAME=Endpoint test - /api/reportings/types" & goto run_endpoint
if "%endpoint_choice%"=="8" set "K6_ENDPOINT=reporting_reports" & set "TEST_NAME=Endpoint test - /api/reporting-reports" & goto run_endpoint
if "%endpoint_choice%"=="9" set "K6_ENDPOINT=reporting_report_statuses" & set "TEST_NAME=Endpoint test - /api/reporting-reports/statuses" & goto run_endpoint
if "%endpoint_choice%"=="10" set "K6_ENDPOINT=jobs" & set "TEST_NAME=Endpoint test - /api/jobs" & goto run_endpoint
if "%endpoint_choice%"=="11" set "K6_ENDPOINT=jobs_new" & set "TEST_NAME=Endpoint test - /api/jobs/new" & goto run_endpoint
if "%endpoint_choice%"=="12" set "K6_ENDPOINT=jobs_on_progress" & set "TEST_NAME=Endpoint test - /api/jobs/on-progress" & goto run_endpoint
if "%endpoint_choice%"=="13" set "K6_ENDPOINT=jobs_extend" & set "TEST_NAME=Endpoint test - /api/jobs/extend" & goto run_endpoint
if "%endpoint_choice%"=="14" set "K6_ENDPOINT=jobs_waiting_approval" & set "TEST_NAME=Endpoint test - /api/jobs/waiting-for-approval" & goto run_endpoint
if "%endpoint_choice%"=="15" set "K6_ENDPOINT=jobs_finish" & set "TEST_NAME=Endpoint test - /api/jobs/finish" & goto run_endpoint
if "%endpoint_choice%"=="16" set "K6_ENDPOINT=mtbf" & set "TEST_NAME=Endpoint test - /api/mtbf" & goto run_endpoint
if "%endpoint_choice%"=="17" set "K6_ENDPOINT=mttr" & set "TEST_NAME=Endpoint test - /api/mttr" & goto run_endpoint
if "%endpoint_choice%"=="18" set "K6_ENDPOINT=fbdts" & set "TEST_NAME=Endpoint test - /api/fbdts" & goto run_endpoint
if "%endpoint_choice%"=="19" set "K6_ENDPOINT=targets" & set "TEST_NAME=Endpoint test - /api/targets" & goto run_endpoint
if "%endpoint_choice%"=="20" set "K6_ENDPOINT=areas" & set "TEST_NAME=Endpoint test - /api/areas" & goto run_endpoint
if "%endpoint_choice%"=="21" set "K6_ENDPOINT=divisions" & set "TEST_NAME=Endpoint test - /api/divisions" & goto run_endpoint
if "%endpoint_choice%"=="22" set "K6_ENDPOINT=groups" & set "TEST_NAME=Endpoint test - /api/groups" & goto run_endpoint
if "%endpoint_choice%"=="23" set "K6_ENDPOINT=informants" & set "TEST_NAME=Endpoint test - /api/informants" & goto run_endpoint
if "%endpoint_choice%"=="24" set "K6_ENDPOINT=machines" & set "TEST_NAME=Endpoint test - /api/machines" & goto run_endpoint
if "%endpoint_choice%"=="25" set "K6_ENDPOINT=operations" & set "TEST_NAME=Endpoint test - /api/operations" & goto run_endpoint
if "%endpoint_choice%"=="26" set "K6_ENDPOINT=parts" & set "TEST_NAME=Endpoint test - /api/parts" & goto run_endpoint
if "%endpoint_choice%"=="27" set "K6_ENDPOINT=positions" & set "TEST_NAME=Endpoint test - /api/positions" & goto run_endpoint
if "%endpoint_choice%"=="28" set "K6_ENDPOINT=reasons" & set "TEST_NAME=Endpoint test - /api/reasons" & goto run_endpoint
if "%endpoint_choice%"=="29" set "K6_ENDPOINT=serial_numbers" & set "TEST_NAME=Endpoint test - /api/serial-numbers" & goto run_endpoint
if "%endpoint_choice%"=="30" set "K6_ENDPOINT=shifts" & set "TEST_NAME=Endpoint test - /api/shifts" & goto run_endpoint
if "%endpoint_choice%"=="31" set "K6_ENDPOINT=technicians" & set "TEST_NAME=Endpoint test - /api/technicians" & goto run_endpoint
if "%endpoint_choice%"=="0" goto menu

echo.
echo Pilihan endpoint tidak valid.
pause
goto endpoint_menu

:config
echo.
echo Config saat ini:
echo BASE_URL : %BASE_URL%
echo K6_EMAIL : %K6_EMAIL%
echo.
set /p BASE_URL=BASE_URL: 
set /p K6_EMAIL=K6_EMAIL: 
set /p K6_PASSWORD=K6_PASSWORD: 
goto menu

:run_endpoint
set "SCRIPT=scenarios\endpoint.js"
goto run

:run
echo.
echo Menjalankan %TEST_NAME%...
echo Script: %SCRIPT%
if defined K6_ENDPOINT echo Endpoint: %K6_ENDPOINT%
echo.
k6 run "%SCRIPT%"
echo.
echo Test selesai.
pause
goto menu

:end
endlocal
