# GUÍA PASO A PASO - RECREAR BASE DE DATOS INVENTIXOR

## 📋 Preparación

1. **Asegúrate de que XAMPP esté ejecutándose:**
   - Apache: ✅ Activo
   - MySQL: ✅ Activo

2. **Haz backup de datos importantes** (si los necesitas)

---

## 🚀 Método 1: Script Automático (Recomendado)

### Windows:
```cmd
cd c:\xampp\htdocs\inventixor
recrear_bd.bat
```

### Manual con MySQL:
```cmd
cd c:\xampp\htdocs\inventixor
c:\xampp\mysql\bin\mysql.exe -u root -p < inventixor_completo.sql
```

---

## 🔧 Método 2: phpMyAdmin (Interfaz Gráfica)

1. **Abrir phpMyAdmin:**
   - Ir a: `http://localhost/phpmyadmin`
   - Usuario: `root` (sin contraseña por defecto)

2. **Eliminar base de datos existente:**
   - Seleccionar base de datos `inventixor` (si existe)
   - Clic en "Eliminar" o "Drop"
   - Confirmar eliminación

3. **Importar nueva base de datos:**
   - Clic en "Importar" en el menú superior
   - Clic en "Seleccionar archivo"
   - Buscar: `c:\xampp\htdocs\inventixor\inventixor_completo.sql`
   - Clic en "Continuar"

---

## 🖥️ Método 3: Línea de Comandos Manual

1. **Abrir Command Prompt como Administrador**

2. **Navegar al directorio:**
   ```cmd
   cd c:\xampp\htdocs\inventixor
   ```

3. **Conectar a MySQL:**
   ```cmd
   c:\xampp\mysql\bin\mysql.exe -u root -p
   ```

4. **Eliminar base de datos existente:**
   ```sql
   DROP DATABASE IF EXISTS inventixor;
   EXIT;
   ```

5. **Ejecutar script completo:**
   ```cmd
   c:\xampp\mysql\bin\mysql.exe -u root -p < inventixor_completo.sql
   ```

---

## ✅ Verificación del Resultado

Después de ejecutar cualquier método, verifica que todo esté correcto:

### 1. Verificar en phpMyAdmin:
- Ir a: `http://localhost/phpmyadmin`
- Verificar que existe la base `inventixor`
- Debe tener estas tablas:
  - ✅ Categoria (5 registros)
  - ✅ Subcategoria (11 registros)  
  - ✅ Users (3 usuarios)
  - ✅ Productos (5 productos de ejemplo)
  - ✅ Proveedores (4 proveedores)
  - ✅ Salidas (tabla vacía)
  - ✅ Devoluciones (tabla vacía)
  - ✅ ProductosSeguimiento (tabla vacía)
  - ✅ Garantias (tabla vacía)
  - ✅ NotificacionesSistema (1 notificación)
  - ✅ TiposSalida (10 tipos)

### 2. Probar el sistema:
- Ir a: `http://localhost/inventixor/`
- Login con: 1001 / password
- Verificar que el campo motivo aparece como lista desplegable

---

## 🎯 Beneficios de la Nueva Base de Datos

### ✅ **Mejoras Implementadas:**
- **Sistema de salidas avanzado** con seguimiento post-salida
- **Devoluciones con lista desplegable categorizada** 🎯
- **Sistema de garantías** automático
- **Notificaciones del sistema** en tiempo real
- **Historial completo** de movimientos
- **Auditoría CRUD** de todas las acciones
- **Triggers automáticos** para consistencia de datos
- **Vistas optimizadas** para consultas rápidas

### 📊 **Datos Incluidos:**
- 3 usuarios de prueba (admin, coordinador, empleado)
- 5 categorías de calzado
- 11 subcategorías especializadas
- 4 proveedores activos
- 5 productos de ejemplo con stock
- 10 tipos de salida configurados
- Sistema de notificaciones activo

---

## 🆘 Solución de Problemas

### Error: "mysql no es reconocido"
```cmd
# Usar ruta completa:
c:\xampp\mysql\bin\mysql.exe -u root -p < inventixor_completo.sql
```

### Error: "Access denied"
- Verificar que MySQL esté ejecutándose en XAMPP
- Usar usuario `root` sin contraseña (configuración por defecto de XAMPP)

### Error: "Can't connect to MySQL server"
- Iniciar MySQL desde el panel de control de XAMPP
- Verificar que el puerto 3306 no esté bloqueado

### El sistema no muestra la lista desplegable:
1. Limpiar caché del navegador (Ctrl+F5)
2. Verificar que la base de datos se creó correctamente
3. Probar con: `http://localhost/inventixor/solucion_definitiva.html`

---

## 📞 Contacto de Soporte

Si tienes problemas, revisa:
1. Los logs de MySQL en XAMPP
2. La consola del navegador (F12)
3. Que todos los archivos estén en su lugar

¡La base de datos está lista para producción! 🚀