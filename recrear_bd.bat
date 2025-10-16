@echo off
echo ========================================
echo INVENTIXOR - RECREAR BASE DE DATOS
echo ========================================
echo.
echo Este script recreará completamente la base de datos InventiXor
echo ADVERTENCIA: Se eliminará toda la información existente
echo.
set /p confirmacion="¿Estás seguro de continuar? (S/N): "

if /i "%confirmacion%" neq "S" (
    echo Operación cancelada.
    pause
    exit /b
)

echo.
echo Iniciando recreación de la base de datos...
echo.

REM Verificar si MySQL está en el PATH
mysql --version >nul 2>&1
if errorlevel 1 (
    echo Error: MySQL no está instalado o no está en el PATH
    echo Intentando usar la ruta de XAMPP...
    set MYSQL_PATH="C:\xampp\mysql\bin\mysql.exe"
) else (
    set MYSQL_PATH=mysql
)

echo Ejecutando script de base de datos...
%MYSQL_PATH% -u root -p < inventixor_completo.sql

if errorlevel 1 (
    echo.
    echo Error al ejecutar el script de base de datos.
    echo Verifica:
    echo - Que MySQL esté ejecutándose
    echo - Que las credenciales sean correctas
    echo - Que el archivo inventixor_completo.sql exista
    pause
    exit /b 1
)

echo.
echo ========================================
echo ¡BASE DE DATOS CREADA EXITOSAMENTE!
echo ========================================
echo.
echo La base de datos 'inventixor' ha sido recreada con:
echo - Todas las tablas del sistema
echo - Sistema de salidas mejorado
echo - Sistema de devoluciones con lista desplegable
echo - Sistema de notificaciones
echo - Historial y auditoría
echo - Datos de ejemplo para pruebas
echo.
echo Puedes acceder al sistema en:
echo http://localhost/inventixor/
echo.
echo Usuarios de prueba:
echo - Administrador: 1001 / password
echo - Coordinador: 1002 / password  
echo - Empleado: 1003 / password
echo.
pause