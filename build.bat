#build checks to check for errors in file/code
@echo off
REM OutCast build script - nightly/weekly build checks
setlocal
echo === PHP Lint ===
php -l index.php || goto :error
php -l includes\functions.php || goto :error
php -l includes\config.php || goto :error
php -l login.php || goto :error
php -l register.php || goto :error
php -l logout.php || goto :error
php -l test_weather.php || goto :error

echo === Quick Weather Test ===
php test_weather.php || echo Note: test may fail without a valid API key.

echo Build checks completed.
goto :eof

:error
echo Build failed.
exit /b 1


