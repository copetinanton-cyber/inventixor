# 📋 Ubicación de Reportes Generados - Sistema Inventixor

## 📍 **¿Dónde se Guardan los Reportes?**

Basándome en el análisis del sistema Inventixor, te explico dónde se almacenan los reportes generados el **1/10/2025, 22:46:06**:

### 🎯 **1. Reportes en la Base de Datos**
Los reportes se registran automáticamente en la tabla `Reportes` de la base de datos:

**Ubicación**: Base de datos MySQL `inventixor` → Tabla `Reportes`

**Campos almacenados**:
- `id_repor` - ID único del reporte
- `nombre` - Nombre del reporte
- `descripcion` - Descripción detallada
- `fecha_hora` - Fecha y hora de generación (ej: 2025-10-01 22:46:06)
- `num_doc` - Usuario que generó el reporte
- `id_nit` - Proveedor relacionado (opcional)
- `id_prod` - Producto relacionado (opcional)

### 📁 **2. Archivos Exportados**

#### **Descargas Automáticas**
Cuando exportas reportes, los archivos se descargan directamente al navegador:

**Ubicación por defecto**: 
- 🪟 **Windows**: `C:\Users\[TuUsuario]\Downloads\`
- 🍎 **Mac**: `/Users/[TuUsuario]/Downloads/`
- 🐧 **Linux**: `/home/[TuUsuario]/Downloads/`

#### **Nombres de Archivos Generados**
```
📄 Ejemplos de archivos descargados:
├── reportes_2025-10-01.csv
├── inventario_2025-10-01.csv
├── analisis_rotacion_2025-10-01.json
├── performance_proveedores_2025-10-01.csv
└── analisis_abc_2025-10-01.csv
```

### 🔧 **3. Sistema de Archivos del Servidor**

#### **Archivos Temporales**
El sistema NO guarda archivos permanentes en el servidor. Los reportes se generan dinámicamente usando:

```php
// Generación temporal para descarga inmediata
$output = fopen('php://output', 'w');
header('Content-Disposition: attachment; filename="reportes_' . date('Y-m-d') . '.csv"');
```

**Ubicación temporal**: Memoria del servidor (no se almacena en disco)

#### **Archivos de Sistema**
**Ubicación de archivos del sistema**:
```
📂 c:\xampp\htdocs\inventixor\
├── 📄 reportes.php (Vista principal)
├── 📄 reportes_modernos.php (Vista avanzada)
├── 📁 app\helpers\
│   ├── 📄 GeneradorReportes.php
│   └── 📄 PlantillasReportes.php
├── 📁 api\
│   └── 📄 reportes.php
└── 📁 public\js\
    └── 📄 reportes-modernos.js
```

### 🔍 **4. Cómo Acceder a los Reportes Guardados**

#### **A. Desde la Base de Datos**
```sql
-- Ver todos los reportes del 1/10/2025
SELECT * FROM Reportes 
WHERE DATE(fecha_hora) = '2025-10-01' 
ORDER BY fecha_hora DESC;

-- Ver reporte específico generado a las 22:46:06
SELECT * FROM Reportes 
WHERE DATE(fecha_hora) = '2025-10-01' 
AND TIME(fecha_hora) = '22:46:06';
```

#### **B. Desde el Sistema Web**
1. **Navegar a**: `http://localhost/inventixor/reportes.php`
2. **Ver historial**: Lista todos los reportes generados
3. **Filtrar por fecha**: Usar filtros para encontrar reportes del 1/10/2025
4. **Re-exportar**: Generar nuevamente cualquier reporte existente

#### **C. Desde phpMyAdmin**
1. **Acceder**: `http://localhost/phpmyadmin`
2. **Base de datos**: `inventixor`
3. **Tabla**: `Reportes`
4. **Filtrar**: `WHERE fecha_hora LIKE '2025-10-01 22:46%'`

### 📊 **5. Tipos de Reportes Disponibles**

#### **Reportes Estándar**
- **Inventario General** → `inventario_2025-10-01.csv`
- **Análisis ABC** → `analisis_abc_2025-10-01.csv`
- **Rotación de Productos** → `analisis_rotacion_2025-10-01.csv`
- **Performance Proveedores** → `performance_proveedores_2025-10-01.csv`

#### **Formatos de Exportación**
- 📊 **CSV**: Para Excel y análisis
- 📋 **JSON**: Para integración con APIs
- 📄 **PDF**: Para impresión (en desarrollo)
- 📈 **Excel**: Formato nativo (en desarrollo)

### ⚙️ **6. Configuración de Descarga**

#### **Cambiar Ubicación de Descargas**
En tu navegador:
1. **Chrome**: Configuración → Avanzado → Descargas
2. **Firefox**: Configuración → General → Archivos y aplicaciones
3. **Edge**: Configuración → Descargas

#### **Verificar Archivos Descargados**
```bash
# Windows (PowerShell)
Get-ChildItem $env:USERPROFILE\Downloads\*reporte*.csv

# Linux/Mac (Terminal)
ls -la ~/Downloads/*reporte*.csv
```

### 🚨 **7. Importante - Seguridad**

#### **Características de Seguridad**
- ✅ **No persistencia**: Los archivos no se guardan en el servidor
- ✅ **Acceso controlado**: Solo usuarios autenticados pueden generar reportes
- ✅ **Trazabilidad**: Todos los reportes quedan registrados en la BD
- ✅ **Permisos por rol**: Admin/Coordinador pueden ver todos, Auxiliar solo los propios

#### **Recomendaciones**
- 🔒 **Backup regular** de la base de datos
- 📋 **Exportar reportes importantes** periódicamente
- 🗄️ **Organizar descargas** en carpetas por fecha
- 🔍 **Revisar logs** para auditar actividad de reportes

---

## 📞 **Resumen para el Reporte del 1/10/2025, 22:46:06**

### **Ubicaciones donde encontrarlo**:

1. **📊 Base de Datos**: Tabla `Reportes` en MySQL
2. **💾 Archivo Exportado**: En tu carpeta de Descargas
3. **🌐 Sistema Web**: En `reportes.php` → Filtrar por fecha
4. **🔍 Búsqueda**: Usar phpMyAdmin con la fecha exacta

### **Nombre probable del archivo**:
- `reportes_2025-10-01.csv` (si es exportación general)
- `[tipo_reporte]_2025-10-01.[formato]` (si es específico)

### **Consulta SQL exacta**:
```sql
SELECT * FROM Reportes 
WHERE fecha_hora BETWEEN '2025-10-01 22:46:00' AND '2025-10-01 22:47:00'
ORDER BY fecha_hora DESC;
```

¿Te gustaría que te ayude a crear un script para localizar específicamente el reporte que mencionas?