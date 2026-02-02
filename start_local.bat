@echo off
echo ========================================
echo   CloudDix - Servidor de Desenvolvimento
echo ========================================
echo.

REM Verifica se PHP existe
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERRO] PHP nao encontrado no PATH.
    echo Por favor, instale PHP 8.2+ e adicione ao PATH do sistema.
    pause
    exit /b 1
)

REM Verifica versao do PHP
php -v

echo.
echo Iniciando servidor em http://localhost:8000
echo Pressione Ctrl+C para parar.
echo.

php -S localhost:8000

pause
