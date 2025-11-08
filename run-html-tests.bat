@echo off
setlocal enabledelayedexpansion

echo ======================================
echo HTML Builder Test Suite Runner
echo ======================================
echo.

set TOTAL_TESTS=0
set TOTAL_PASSED=0
set TOTAL_FAILED=0

for %%F in (tests\Unit\Html\*.php tests\Unit\Html\Attributes\*.php) do (
    echo Testing: %%~nF
    "C:\Users\pc\.config\herd\bin\php.bat" -d opcache.enable=0 vendor\bin\phpunit "%%F" --no-progress > temp_test_output.txt 2>&1

    findstr /C:"OK" /C:"FAILURES" temp_test_output.txt
    echo.
)

del temp_test_output.txt

echo ======================================
echo Test Suite Complete
echo ======================================
