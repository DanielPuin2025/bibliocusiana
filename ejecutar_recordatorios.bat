@echo off
REM =====================================================
REM Script para ejecutar recordatorios automáticos
REM Sistema de Biblioteca - Bibliocusiana
REM =====================================================

echo ========================================
echo Ejecutando verificacion de vencimientos
echo ========================================
echo.

"C:\xampp\php\php.exe" "C:\xampp\htdocs\biblioteca\backend\cron\verificar_vencimientos.php"

echo.
echo ========================================
echo Proceso completado
echo ========================================
echo.

REM Descomentar la siguiente línea si quieres ver el resultado
REM pause
