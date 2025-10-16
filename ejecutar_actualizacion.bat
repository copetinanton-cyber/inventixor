@echo off
echo ========================================
echo EJECUTANDO ACTUALIZACION DE INVENTIXOR
echo ========================================
echo.
echo Conectando a MySQL...
echo (Si no tienes contraseña, solo presiona Enter)
echo.

c:\xampp\mysql\bin\mysql.exe -u root -p inventixor < actualizar_mejoras.sql

if %errorlevel% == 0 (
    echo.
    echo ========================================
    echo ✅ ACTUALIZACION COMPLETADA EXITOSAMENTE
    echo ========================================
    echo.
    echo El campo motivo ahora funciona como lista desplegable
    echo Puedes probar en: http://localhost/inventixor/solucion_definitiva.html
    echo.
    pause
) else (
    echo.
    echo ========================================
    echo ❌ ERROR EN LA ACTUALIZACION
    echo ========================================
    echo.
    echo Verifica:
    echo 1. Que XAMPP esté ejecutándose
    echo 2. Que MySQL esté activo
    echo 3. Que la contraseña sea correcta
    echo.
    pause
)