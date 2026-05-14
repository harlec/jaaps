# JAAP – Sistema de Gestión de Junta Administradora de Agua Potable

## Descripción

Sistema web desarrollado en **PHP 8**, **MySQL** y **Tailwind CSS** para administrar el padrón de abonados, registrar pagos semestrales/anuales y gestionar inscripciones de una Junta Administradora de Agua Potable (JAAP).

---

## Tecnologías

| Capa        | Tecnología                               |
|-------------|------------------------------------------|
| Backend     | PHP 8.1+, PDO (MySQL)                    |
| Frontend    | Tailwind CSS 3 (CDN Play), Alpine.js 3   |
| Gráficos    | Chart.js 4                               |
| Base datos  | MySQL 8 / MariaDB 10.6+                  |
| API externa | migo.pe (consulta DNI)                   |

---

## Estructura del Proyecto

```
jaaps/
├── config/
│   ├── config.php          # Configuración global, helpers, constantes
│   └── database.php        # Conexión PDO (singleton)
├── includes/
│   ├── auth.php            # Middleware de autenticación
│   ├── header.php          # HTML <head> y apertura de layout
│   ├── sidebar.php         # Navegación lateral
│   ├── topbar.php          # Barra superior
│   └── footer.php          # Cierre del layout
├── abonados/
│   ├── index.php           # Listado con filtros y paginación
│   ├── crear.php           # Formulario de nuevo abonado
│   ├── editar.php          # Edición de abonado existente
│   ├── ver.php             # Ficha completa + historial de pagos
│   └── eliminar.php        # Eliminación segura (solo sin pagos)
├── pagos/
│   ├── index.php           # Historial general de pagos
│   └── registrar.php       # Formulario de registro de pago
├── inscripciones/
│   ├── index.php           # Lista de inscripciones
│   └── registrar.php       # Formulario de inscripción
├── conceptos/
│   └── index.php           # CRUD de conceptos de cobro
├── importar/
│   ├── index.php           # Carga masiva CSV/TXT
│   └── plantilla.csv       # Archivo de ejemplo
├── api/
│   ├── dni.php             # Proxy seguro → migo.pe
│   └── buscar_abonado.php  # Búsqueda AJAX de abonados
├── database.sql            # Esquema completo + datos iniciales
├── login.php               # Inicio de sesión
├── logout.php              # Cierra sesión y redirige
├── dashboard.php           # Panel principal con KPIs y gráficos
└── index.php               # Redirige según estado de sesión
```

---

## Instalación

### 1. Requisitos previos

- PHP 8.1 o superior con extensiones: `pdo_mysql`, `curl`, `mbstring`, `fileinfo`
- MySQL 8.0+ o MariaDB 10.6+
- Servidor web: Apache / Nginx / Laragon / XAMPP

### 2. Crear la base de datos

Importa el archivo `database.sql` en tu servidor MySQL:

```bash
mysql -u root -p < database.sql
```

O mediante phpMyAdmin: **Importar** → seleccionar `database.sql`.

### 3. Configurar la conexión

Edita `config/config.php` y ajusta las variables de entorno o edita directamente los `define()`:

```php
// En config/database.php, puedes usar $_ENV o editar los valores directamente:
define('DB_HOST', 'localhost');
define('DB_NAME', 'jaap_db');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

### 4. Configurar la URL base

En `config/config.php`:

```php
define('APP_URL', 'http://localhost/jaaps'); // Ajusta según tu entorno
```

### 5. Configurar token de migo.pe

```php
define('MIGO_API_TOKEN', 'TU_TOKEN_AQUI'); // https://api.migo.pe/tokens
```

### 6. Acceder al sistema

Abre `http://localhost/jaaps/` en tu navegador.

**Credenciales iniciales:**

| Campo    | Valor             |
|----------|-------------------|
| Correo   | admin@jaap.pe     |
| Password | Admin2026!        |

> ⚠ **Cambia la contraseña** después del primer ingreso.

Para generar un hash nuevo:
```php
echo password_hash('NuevaContraseña', PASSWORD_BCRYPT, ['cost' => 12]);
```

Luego actualiza en la BD:
```sql
UPDATE usuarios_sistema SET password = 'NUEVO_HASH' WHERE email = 'admin@jaap.pe';
```

---

## Módulos

### Dashboard
Muestra KPIs en tiempo real:
- **Total Abonados Activos**
- **Sin pago en período activo**
- **Recaudación total del año**
- **Abonados inactivos / suspendidos**

Incluye gráfico de recaudación mensual y distribución por zona.

---

### Abonados (Módulo principal)

**Campos del registro de abonado:**

| Campo             | Descripción                                                        |
|-------------------|--------------------------------------------------------------------|
| Código            | Auto-generado (AB-0001, AB-0002…)                                 |
| DNI               | 8 dígitos, consultable via migo.pe                                |
| Nombres / Apellidos | Auto-completados desde migo.pe al consultar DNI                  |
| Fecha de nacimiento | Opcional                                                         |
| Departamento / Provincia / Distrito | Ubigeo manual                                    |
| Dirección         | Dirección del predio                                               |
| Zona              | **Porvenir**, **Tunas**, **Cerro de Pasco**                        |
| Profesión         | Libre                                                              |
| Actividad         | Actividad económica                                                |
| Grado de instrucción | Sin instrucción / Primaria / Secundaria / Técnico / Universitario / Posgrado |
| Estado civil      | Soltero / Casado / Conviviente / Viudo / Divorciado               |
| Teléfono / Correo | Contacto                                                          |
| Número de hijos   | Al ingresar la cantidad, aparecen campos por cada hijo             |
| Hijos (nombres + fecha nac.) | Tabla relacionada `hijos`                              |
| Fecha de inscripción | Fecha de ingreso al padrón                                     |
| Estado            | Activo / Inactivo / Suspendido                                    |
| Observaciones     | Notas adicionales                                                  |

**Operaciones:**
- Crear (con integración DNI on-the-fly)
- Editar
- Ver ficha completa con historial de pagos
- Eliminar (solo si no tiene pagos)
- Buscar por DNI, nombres, apellidos o código
- Filtrar por zona y estado

---

### Pagos

La tarifa mensual es de **S/. 12.00**. Los cobros se realizan **1 o 2 veces al año** por período semestral:

| Período        | Meses    | Monto Base |
|----------------|----------|------------|
| Semestre 1     | Ene–Jun  | S/. 72.00  |
| Semestre 2     | Jul–Dic  | S/. 72.00  |
| Anual          | Ene–Dic  | S/. 144.00 |

**Campos del pago:**
- Abonado (búsqueda AJAX)
- Concepto (tarifa mensual, inscripción, multa, reconexión, otro)
- Período de cobro (opcional)
- Monto base, descuento, interés/mora → **Total auto-calculado**
- Fecha de pago
- Método (Efectivo / Transferencia / Depósito / Otro)
- N° operación / voucher
- Número de recibo auto-generado (REC-YYYYNNNNN)

---

### Inscripciones

Registro formal del ingreso de un abonado al servicio de agua potable.

- Número de solicitud auto-generado (INS-YYYYNNNN)
- Fecha, monto, estado (Pendiente / Aprobada / Rechazada / Cancelada)
- Observaciones

---

### Conceptos de Pago

Administración de los tipos de cobro disponibles:

| Tipo            | Descripción                         | Monto por defecto |
|-----------------|-------------------------------------|-------------------|
| Tarifa Mensual  | Cuota mensual del servicio          | S/. 12.00         |
| Inscripción     | Pago único de conexión              | Configurable      |
| Multa           | Por mora u otros incumplimientos    | Configurable      |
| Reconexión      | Tras suspensión del servicio        | Configurable      |
| Otro            | Conceptos adicionales               | Configurable      |

---

### Importar Abonados

Carga masiva desde archivo **CSV** o **TXT**.

**Formato esperado (con separador coma):**
```
DNI,Apellidos,Nombres,Dirección,Teléfono
12345678,GARCIA LOPEZ,JUAN CARLOS,Av. Principal 123,987654321
```

**Opciones:**
- Seleccionar separador (`,` `;` `TAB` `|`)
- Asignar zona predeterminada a todos los importados
- Omitir primera fila (encabezado)
- Consultar migo.pe si nombres/apellidos están vacíos (requiere token)

**Reglas:**
- DNIs con formato incorrecto son omitidos
- DNIs ya registrados son omitidos (sin duplicados)
- Máximo 5 MB

---

### API – Consulta DNI (migo.pe)

**Endpoint interno:** `GET /api/dni.php?dni=12345678`

- Solo accesible con sesión activa (no expuesto públicamente)
- Proxy seguro: el token nunca se expone al frontend
- Respuesta:
  ```json
  { "success": true, "dni": "12345678", "nombre": "GARCIA LOPEZ JUAN CARLOS" }
  ```
- El nombre retornado se divide automáticamente:
  - Primeras 2 palabras → **Apellidos**
  - Resto → **Nombres**

---

## Seguridad

- Contraseñas con **bcrypt** (`password_hash` / `password_verify`)
- Sesiones con `session_regenerate_id(true)` en cada login
- Cookies con `httponly` y `strict_mode`
- Todas las salidas HTML escapadas con `htmlspecialchars()` via helper `e()`
- Consultas con **PDO + prepare/execute** (sin SQL injection)
- Token migo.pe almacenado solo en servidor (jamás enviado al cliente)
- Eliminación de abonados protegida: requiere rol `admin` y no tener pagos

---

## Roles de Usuario

| Rol     | Permisos                                      |
|---------|-----------------------------------------------|
| admin   | Acceso total, eliminar abonados               |
| cajero  | Registrar pagos, ver abonados, importar       |
| viewer  | Solo lectura                                  |

---

## Esquema de Base de Datos

```
usuarios_sistema    → Usuarios del sistema (admin, cajero, viewer)
abonados            → Padrón de suscriptores del servicio de agua
hijos               → Hijos de cada abonado (relación 1:N)
conceptos           → Tipos de cobro (tarifa, inscripción, multa, etc.)
periodos_cobro      → Semestres/períodos de facturación
pagos               → Historial de pagos (FK: abonados, conceptos, periodos)
inscripciones       → Registro de ingreso al servicio (FK: abonados)
```

---

## Personalización

### Cambiar la tarifa mensual
En `config/config.php`:
```php
define('TARIFA_MENSUAL', 12.00); // S/.
```

### Agregar zonas
En `config/config.php`, agregar a `ZONAS`:
```php
define('ZONAS', [
    'porvenir'       => 'Porvenir',
    'tunas'          => 'Tunas',
    'cerro_de_pasco' => 'Cerro de Pasco',
    'nueva_zona'     => 'Nueva Zona',  // ← añadir aquí
]);
```
Y también actualizar el ENUM en la tabla `abonados` de la BD.

### Crear nuevos períodos de cobro
```sql
INSERT INTO periodos_cobro (nombre, anio, semestre, meses, fecha_inicio, fecha_fin, fecha_vencimiento, monto_total, estado)
VALUES ('Semestre 1 – 2027', 2027, '1', 6, '2027-01-01', '2027-06-30', '2027-03-31', 72.00, 'pendiente');
```

---

## Changelog

| Versión | Fecha      | Cambios                        |
|---------|------------|--------------------------------|
| 1.0.0   | 2026-05-14 | Versión inicial del sistema    |

---

## Licencia

Sistema desarrollado a medida para uso interno de la JAAP. Todos los derechos reservados.
