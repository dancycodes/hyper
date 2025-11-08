@echo off
setlocal enabledelayedexpansion

echo ==========================================
echo HTML Builder Test Suite - All Tests
echo ==========================================
echo.
echo Running 292 tests across 8 test files...
echo.

set TOTAL_TESTS=0
set TOTAL_PASSED=0
set TOTAL_FAILED=0

echo [1/8] ElementBasicsTest (36 tests)...
"C:\Users\pc\.config\herd\bin\php.bat" -d opcache.enable=0 vendor\bin\phpunit tests\Unit\Html\ElementBasicsTest.php --no-progress --colors=never 2>&1 | findstr /C:"OK" /C:"FAILURES"
echo.

echo [2/8] VoidElementTest (32 tests)...
"C:\Users\pc\.config\herd\bin\php.bat" -d opcache.enable=0 vendor\bin\phpunit tests\Unit\Html\VoidElementTest.php --no-progress --colors=never 2>&1 | findstr /C:"OK" /C:"FAILURES"
echo.

echo [3/8] TextElementTest (50 tests)...
"C:\Users\pc\.config\herd\bin\php.bat" -d opcache.enable=0 vendor\bin\phpunit tests\Unit\Html\TextElementTest.php --no-progress --colors=never 2>&1 | findstr /C:"OK" /C:"FAILURES"
echo.

echo [4/8] GlobalAttributesTest (63 tests)...
"C:\Users\pc\.config\herd\bin\php.bat" -d opcache.enable=0 vendor\bin\phpunit tests\Unit\Html\Attributes\GlobalAttributesTest.php --no-progress --colors=never 2>&1 | findstr /C:"OK" /C:"FAILURES"
echo.

echo [5/8] FormAttributesTest (51 tests)...
"C:\Users\pc\.config\herd\bin\php.bat" -d opcache.enable=0 vendor\bin\phpunit tests\Unit\Html\Attributes\FormAttributesTest.php --no-progress --colors=never 2>&1 | findstr /C:"OK" /C:"FAILURES"
echo.

echo [6/8] ContainerElement_BasicTest (18 tests)...
"C:\Users\pc\.config\herd\bin\php.bat" -d opcache.enable=0 vendor\bin\phpunit tests\Unit\Html\ContainerElement_BasicTest.php --no-progress --colors=never 2>&1 | findstr /C:"OK" /C:"FAILURES"
echo.

echo [7/8] ContainerElement_SecurityTest (18 tests)...
"C:\Users\pc\.config\herd\bin\php.bat" -d opcache.enable=0 vendor\bin\phpunit tests\Unit\Html\ContainerElement_SecurityTest.php --no-progress --colors=never 2>&1 | findstr /C:"OK" /C:"FAILURES"
echo.

echo [8/8] DataSignalsIntegrationTest (24 tests)...
"C:\Users\pc\.config\herd\bin\php.bat" -d opcache.enable=0 vendor\bin\phpunit tests\Unit\Html\DataSignalsIntegrationTest.php --no-progress --colors=never 2>&1 | findstr /C:"OK" /C:"FAILURES"
echo.

echo ==========================================
echo Test Suite Complete
echo ==========================================
echo.
echo Expected: 292 tests passing
echo See HTML_TESTS_STATUS.md for details
echo.
