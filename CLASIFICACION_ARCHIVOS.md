# 📋 CLASIFICACIÓN DE ARCHIVOS - SISTEMA INVENTIXOR

## 🗂️ **ESTRUCTURA ACTUAL DESPUÉS DE LA LIMPIEZA**

### **📁 RAÍZ DEL PROYECTO** (`/`)
```
├── 🔐 index.php                    # Página de inicio/redirección
├── 🔐 login.php                    # Página de login principal
├── 🔐 login_bd.php                 # Procesamiento de login (backend)
├── 🔐 logout.php                   # Cerrar sesión
├── 📊 dashboard.php                # MOVIDO a app/views/ - Dashboard principal
├── 📦 categorias.php               # Gestión de categorías (VISTA principal)
├── 📦 subcategorias.php            # MOVIDO a app/views/ - Gestión de subcategorías
├── 👥 proveedores.php              # MOVIDO a app/views/ - Gestión de proveedores  
├── 🛍️ productos.php               # MOVIDO a app/views/ - Gestión de productos
├── 📤 salidas.php                  # Gestión de salidas de inventario
├── 📊 reportes.php                 # Gestión de reportes
├── 🚨 alertas.php                  # Sistema de alertas
├── 👤 usuarios.php                 # Gestión de usuarios

├── 🔒 autorizaciones.php           # Control de permisos
├── 📄 salidas_reportes.php         # Reportes específicos de salidas
├── 🗄️ db.sql                       # Script de base de datos
└── 📚 DOCUMENTACION_COMPLETA_INVENTIXOR.md # Documentación integral
```

### **📁 CARPETA APP** (`/app/`)
#### **🎯 Controllers** (`/app/controllers/`)
```
├── 🔐 AuthController.php           # Controlador de autenticación
├── 📦 CategoriaController.php      # Controlador de categorías
├── 📦 SubcategoriaController.php   # Controlador de subcategorías
├── 👥 ProveedorController.php      # Controlador de proveedores
├── 🛍️ ProductosController.php     # Controlador de productos
├── 📤 SalidaController.php         # Controlador de salidas
├── 📊 ReporteController.php        # Controlador de reportes
└── 🚨 AlertaController.php         # Controlador de alertas
```

#### **🗃️ Models** (`/app/models/`)
```
├── 👤 User.php                     # Modelo de usuarios
├── 📦 Categoria.php                # Modelo de categorías
├── 📦 Subcategoria.php             # Modelo de subcategorías
├── 👥 Proveedor.php                # Modelo de proveedores
├── 🛍️ Producto.php                # Modelo de productos
├── 📤 Salida.php                   # Modelo de salidas
├── 📊 Reporte.php                  # Modelo de reportes
├── 🚨 Alerta.php                   # Modelo de alertas
└── ⚙️ EdicionPendiente.php         # Modelo de ediciones pendientes
```

#### **👁️ Views** (`/app/views/`)
```
├── 🔐 login.php                    # Vista de login (duplicada - REVISAR)
├── 📊 dashboard.php                # Dashboard principal (MOVIDO AQUÍ)
├── 📦 subcategorias.php            # Vista de subcategorías (MOVIDO AQUÍ)
├── 👥 proveedores.php              # Vista de proveedores (MOVIDO AQUÍ)
└── 🛍️ productos.php               # Vista de productos (MOVIDO AQUÍ)
```

#### **🛠️ Helpers** (`/app/helpers/`)
```
└── 🗄️ Database.php                 # Clase helper de base de datos
```

### **📁 CONFIG** (`/config/`)
```
└── ⚙️ db.php                       # Configuración de base de datos
```

### **📁 PUBLIC** (`/public/`)
```
└── 🎨 css/
    └── style.css                   # Estilos CSS principales
```

### **📁 VIEWS** (`/views/`)
```
├── 📤 salidas.php                  # Vista adicional de salidas
└── 📤 salidas/
    ├── form.php                    # Formulario de salidas
    └── list.php                    # Lista de salidas
```

---

## ✅ **ESTRUCTURA FINAL CORREGIDA**

### **� ARCHIVOS CORRECTAMENTE UBICADOS**
- ✅ **Vistas principales**: Todas en raíz del proyecto
- ✅ **Controllers**: Todos en `/app/controllers/`
- ✅ **Models**: Todos en `/app/models/`
- ✅ **Config**: En `/config/`
- ✅ **Helpers**: En `/app/helpers/`
- ✅ **CSS**: En `/public/css/`

### **🔧 ACCIONES CORRECTIVAS APLICADAS**

#### **1. ✅ Archivos devueltos a la raíz**
- `dashboard.php` ← `/app/views/dashboard.php`
- `subcategorias.php` ← `/app/views/subcategorias.php`
- `proveedores.php` ← `/app/views/proveedores.php`
- `productos.php` ← `/app/views/productos.php`

#### **2. ✅ Duplicados resueltos**
- Eliminado `/app/views/login.php` (mantenido `/login.php`)

#### **3. ✅ Estructura /views/ limpia**
- `/app/views/` está ahora vacía (correctamente)
- Mantenido `/views/salidas/` para componentes específicos

---

## 📊 **RESUMEN DE ARCHIVOS**

### **✅ ARCHIVOS NECESARIOS (31 archivos)**
- **Vistas principales (11)**: index, login, dashboard, categorias, subcategorias, proveedores, productos, salidas, reportes, alertas, usuarios
- **Controllers (8)**: AuthController, CategoriaController, SubcategoriaController, etc.
- **Models (9)**: User, Categoria, Subcategoria, Proveedor, Producto, etc.
- **Helpers (1)**: Database
- **Config (2)**: db.php, style.css

### **❌ ARCHIVOS ELIMINADOS (8 archivos)**
- test_file_access.php ✅
- insert_demo_data.php ✅
- insert_users.php ✅
- generar_hashes.php ✅
- listar_usuarios.php ✅
- *_backup.php (5 archivos) ✅
- PROYECTO_COMPLETADO.md ✅

- CORRECCION_REPORTES.md ✅

### **⚠️ ARCHIVOS A REVISAR (3 archivos)**
- login.php (duplicado en /app/views/)
- salidas.php (duplicado en /views/)


---

## 🎯 **ESTRUCTURA MVC IDEAL**

```
inventixor/
├── 🔐 Archivos de acceso público (raíz)
│   ├── index.php, login.php, logout.php
│   ├── dashboard.php, categorias.php, etc.
│   └── autorizaciones.php
├── 📁 app/
│   ├── 🎯 controllers/ (Lógica de negocio)
│   ├── 🗃️ models/ (Acceso a datos)
│   ├── 👁️ views/ (Solo componentes reutilizables)
│   └── 🛠️ helpers/ (Utilidades)
├── 📁 config/ (Configuración)
├── 📁 public/ (Assets estáticos)
└── 📁 views/ (Vistas específicas/componentes)
```

---

**Estado Actual**: ✅ Estructura MVC correctamente organizada
**Resultado**: Proyecto limpio y funcional con arquitectura MVC apropiada