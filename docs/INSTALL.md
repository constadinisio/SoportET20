# Guía de Instalación y Configuración

Guía paso a paso para dejar SoportET20 funcionando desde cero. Asume que sos nuevo en el proyecto y que no necesariamente tenés mucha experiencia con servidores — seguí los pasos en orden y debería andar.

**Tiempo estimado**: 20-40 minutos (la primera vez).

---

## Índice

1. [Prerrequisitos](#1-prerrequisitos)
2. [Instalar XAMPP](#2-instalar-xampp-si-no-lo-tenés)
3. [Copiar el proyecto](#3-copiar-el-proyecto)
4. [Crear la base de datos](#4-crear-la-base-de-datos)
5. [Cargar el schema y las migraciones](#5-cargar-el-schema-y-las-migraciones)
6. [Cargar datos de ejemplo](#6-cargar-datos-de-ejemplo-opcional)
7. [Configurar las credenciales de la DB](#7-configurar-las-credenciales-de-la-db)
8. [Primer login y cambio de passwords](#8-primer-login-y-cambio-de-passwords)
9. [Configurar el heartbeat](#9-configurar-el-heartbeat-opcional)
10. [Checklist final](#10-checklist-final)
11. [Troubleshooting](#11-troubleshooting)

---

## 1. Prerrequisitos

Necesitás en la máquina:

- **XAMPP 8.x** (trae Apache + MySQL 8 + PHP 8). [Descargar](https://www.apachefriends.org/download.html)
- **Editor de texto decente**: VS Code, Notepad++, Sublime Text. (No Word ni Bloc de notas de Windows.)
- **Cliente MySQL**: alcanza con **phpMyAdmin** (viene con XAMPP, en `http://localhost/phpmyadmin`).
- El código de **SoportET20** (clon del repo, ZIP, o lo que te hayan pasado).

---

## 2. Instalar XAMPP (si no lo tenés)

1. Descargá el instalador de la web oficial.
2. Ejecutalo como administrador.
3. Durante la instalación, **aceptá los defaults** salvo el directorio: te recomiendo `C:\xampp` (el default). Si elegiste otro, anotalo — lo vas a necesitar más adelante.
4. Abrí el **XAMPP Control Panel** (el acceso directo del escritorio).
5. Arrancá **Apache** y **MySQL** (clicks en "Start"). Los dos deberían ponerse en verde.

**Verificación:**
- En el navegador, abrí `http://localhost/` → debería cargarte la dashboard de XAMPP.
- Abrí `http://localhost/phpmyadmin` → debería dejarte entrar sin contraseña.

> ⚠ Si Apache no arranca, probablemente **otro programa está usando el puerto 80** (Skype, IIS, Docker). Solución rápida: en XAMPP → "Config" → "httpd.conf" → cambiar `Listen 80` a `Listen 8080` y después acceder por `http://localhost:8080/`.

---

## 3. Copiar el proyecto

### Opción A — Con Git

```bash
cd C:\xampp\htdocs
git clone <URL_DEL_REPO> SoportET20
```

### Opción B — Con ZIP

1. Descomprimí el ZIP.
2. Copiá la carpeta `SoportET20/` dentro de `C:\xampp\htdocs\`.

**Debería quedar así:**

```
C:\xampp\htdocs\SoportET20\
├── backend\
├── bin\
├── docs\
├── migrations\
├── public\
├── database_v2.sql
└── README.md
```

**Verificación:**
- Abrí `http://localhost/SoportET20/public/index.php` en el navegador.
- Deberías ver la página de login (o un error de base de datos — eso está bien todavía, lo resolvemos ya).

---

## 4. Crear la base de datos

### Opción A — Con phpMyAdmin (fácil)

1. Abrí `http://localhost/phpmyadmin`.
2. Click en **"Bases de datos"** en la barra superior.
3. En "Crear base de datos":
   - Nombre: `soportet20_db`
   - Cotejamiento: `utf8mb4_unicode_ci`
4. Click en **"Crear"**.

### Opción B — Con consola

```bash
# Abrir consola de MySQL (ajustar ruta a XAMPP si es distinta)
C:\xampp\mysql\bin\mysql.exe -u root

# Dentro de MySQL:
CREATE DATABASE soportet20_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

**Verificación**: en phpMyAdmin tenés que ver `soportet20_db` en el sidebar izquierdo.

---

## 5. Cargar el schema y las migraciones

El proyecto se construye en capas: primero el schema base, después las migraciones en orden numérico.

### 5.1 — Schema base

**Con phpMyAdmin:**
1. Seleccioná la DB `soportet20_db` en el sidebar.
2. Tab **"Importar"**.
3. Click **"Seleccionar archivo"** → elegí `database_v2.sql` del proyecto.
4. Click **"Importar"** (abajo).

**Con consola:**
```bash
cd C:\xampp\htdocs\SoportET20
C:\xampp\mysql\bin\mysql.exe -u root soportet20_db < database_v2.sql
```

**Verificación**: en phpMyAdmin, dentro de `soportet20_db`, deberías ver 5 tablas: `aulas`, `computadoras`, `log_acciones`, `tickets`, `usuarios`.

### 5.2 — Migraciones incrementales

Correr **en este orden** (cada archivo agrega algo al schema):

```bash
cd C:\xampp\htdocs\SoportET20
set MYSQL=C:\xampp\mysql\bin\mysql.exe -u root soportet20_db

%MYSQL% < migrations\001_add_specs_pc.sql
%MYSQL% < migrations\002_add_indexes.sql
%MYSQL% < migrations\003_notificaciones.sql
%MYSQL% < migrations\007_aulas_info.sql
```

> Las migraciones 004, 005, 006 y 008 son **seeds** (datos de ejemplo). Van en el paso 6.

**Verificación** (en phpMyAdmin → SQL):
```sql
SHOW TABLES FROM soportet20_db;
```
Deberías ver 7 tablas: las 5 base + `notificaciones` + `aulas_info`.

**Verificación del evento MySQL** (limpia notificaciones viejas):
```sql
SHOW EVENTS FROM soportet20_db;
```
Debería aparecer `ev_limpiar_notificaciones_viejas` con STATUS = `ENABLED`.

> ⚠ Si da error "Access denied" en la migración 003: el user `root` necesita privilegio `SUPER`. Si usás XAMPP con root default, ya lo tiene. Si creaste un user restringido, corré esta migración **como root**.

---

## 6. Cargar datos de ejemplo (opcional)

Recomendado para la primera vez — te deja la app con 4 aulas, 77 PCs y 11 tickets abiertos de ejemplo.

```bash
%MYSQL% < migrations\004_seed_aula_6A.sql
%MYSQL% < migrations\005_seed_aulas_4_5_6B.sql
%MYSQL% < migrations\006_seed_tickets_iniciales.sql
%MYSQL% < migrations\008_seed_aulas_info.sql
```

**Verificación:**
```sql
SELECT id, nombre FROM aulas;
-- Debería devolver 4 filas: 4, 5, 6A, 6B

SELECT COUNT(*) FROM computadoras;
-- Debería dar 77

SELECT COUNT(*) FROM tickets WHERE estado = 'ABIERTO';
-- Debería dar 11
```

Si después querés **borrar los datos** y recargar desde cero:

```bash
%MYSQL% < migrations\reset_aulas_pcs.sql
# luego volver a correr las migraciones 004, 005, 006, 008
```

---

## 7. Configurar las credenciales de la DB

Por default, `backend/db.php` usa `root` sin password (el default de XAMPP). **Para dev local alcanza con eso** — podés saltear este paso.

**Para producción** tenés que crear un user dedicado:

### 7.1 — Crear user MySQL

En phpMyAdmin → SQL:

```sql
CREATE USER 'soportet20_app'@'localhost' IDENTIFIED BY 'ElegíUnaClaveFuerte123!';
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON soportet20_db.* TO 'soportet20_app'@'localhost';
FLUSH PRIVILEGES;
```

### 7.2 — Exportar env vars

**Windows** (System Environment Variables):
1. Panel de Control → Sistema → Configuración avanzada del sistema → Variables de entorno.
2. En "Variables del sistema" → Nuevo:
   - `DB_HOST` = `localhost`
   - `DB_NAME` = `soportet20_db`
   - `DB_USER` = `soportet20_app`
   - `DB_PASS` = `ElegíUnaClaveFuerte123!`
3. **Reiniciar Apache** desde el XAMPP Control Panel.

**Linux** (`/etc/apache2/envvars`):
```bash
export DB_HOST=localhost
export DB_NAME=soportet20_db
export DB_USER=soportet20_app
export DB_PASS=ElegíUnaClaveFuerte123!
```
Después: `sudo systemctl restart apache2`.

**Verificación**: abrí `http://localhost/SoportET20/public/index.php`. Si cargaste bien, la conexión anda.

---

## 8. Primer login y cambio de passwords

1. Abrí `http://localhost/SoportET20/public/index.php`.
2. Logueate con una de estas 3 cuentas de prueba:

| Usuario   | Clave         | Rol      |
|-----------|---------------|----------|
| admin     | admin123      | ADMIN    |
| tecnico   | tecnico123    | TECNICO  |
| profe     | profe123      | PROFESOR |

3. **⚠ CAMBIAR ESTAS CLAVES YA.** Desde el panel admin:
   - Login como `admin`.
   - Ir a **Administración → Usuarios** (sidebar izquierda).
   - Editar cada user → poner clave nueva → guardar.

### Crear tu propio usuario ADMIN

Si vas a operar el sistema diariamente, creá tu propia cuenta en vez de usar `admin`:

1. **Admin panel → Usuarios → "Nuevo Usuario"**.
2. Completá:
   - Usuario: `tunombre`
   - Nombre completo: `Tu Nombre Real`
   - Rol: `ADMIN`
   - Clave: una fuerte (min 6 caracteres).
3. Guardar.
4. Logout → login con tu nueva cuenta.
5. Desactivar el user `admin` default (Admin panel → Usuarios → Desactivar).

---

## 9. Configurar el heartbeat (opcional)

El heartbeat es el script que pingea todas las PCs cada 5 minutos para detectar cuáles están prendidas/apagadas y marcarlas en ALERTA/HIBERNANDO automáticamente.

**Si todavía no tenés IPs cargadas en las PCs, saltealo** — no va a hacer nada útil.

### 9.1 — Probar en seco

```bash
cd C:\xampp\htdocs\SoportET20
C:\xampp\php\php.exe bin\heartbeat.php --dry-run
```

Debería imprimir qué pasaría sin modificar nada. Si tira error, leer el mensaje y corregir (probablemente IPs mal cargadas o firewall bloqueando el puerto).

### 9.2 — Setear Task Scheduler (Windows)

1. Abrí **Task Scheduler** (buscar en inicio "Programador de tareas").
2. Panel derecho → **"Crear tarea básica"**.
3. Nombre: `SoportET20 Heartbeat`.
4. Descripción: `Pingeas PCs cada 5 min`.
5. Desencadenador: **Diariamente**, una vez. Click "Siguiente".
6. En el panel de propiedades de la tarea (después de crearla):
   - Tab **"Desencadenadores"** → editar el que creaste → marcar **"Repetir cada: 5 minutos durante un día"**.
7. Tab **"Acciones"** → nueva acción:
   - Programa: `C:\xampp\php\php.exe`
   - Argumentos: `C:\xampp\htdocs\SoportET20\bin\heartbeat.php`
8. Tab **"Condiciones"** → **desmarcar** "Iniciar solo si el equipo está conectado a CA".
9. Tab **"Configuración"** → marcar "Ejecutar tan pronto como sea posible una vez iniciada, si se perdió un inicio programado".

**Verificación**: después de unos minutos, abrí phpMyAdmin:

```sql
SELECT * FROM log_acciones WHERE accion = 'HEARTBEAT_CAMBIO_ESTADO' ORDER BY creado_en DESC LIMIT 5;
```

Si empezaron a aparecer entradas, el heartbeat está corriendo.

### 9.3 — Setear cron (Linux)

```bash
sudo nano /etc/cron.d/soportet20

# Agregar:
*/5 * * * * www-data /usr/bin/php /var/www/html/SoportET20/bin/heartbeat.php

# Guardar y salir (Ctrl+X, Y, Enter).
```

Ver más detalles en `bin/README.md`.

---

## 10. Checklist final

Antes de dar la instalación por terminada, chequeá lo siguiente:

- [ ] `http://localhost/SoportET20/public/index.php` carga el login.
- [ ] Podés loguearte con `admin` y ver el dashboard.
- [ ] En `admin.php` ves las 4 tabs: Aulas, PCs, Usuarios, Auditoría.
- [ ] En `home.php` ves las 4 tabs: Dashboard, Tickets, Estado PC, Nuevo Ticket.
- [ ] Si corriste los seeds: ves 4 aulas con sus PCs en el tab "Estado de PC".
- [ ] Clickeaste una PC y se abrió el modal de ficha técnica.
- [ ] Creaste un ticket de prueba desde `home.php#new-ticket`.
- [ ] Cambiaste las passwords default.
- [ ] (Prod) Configuraste env vars de DB y no usás `root`.
- [ ] (Prod) Programaste backups diarios (ver `OPERATIONS.md § Backups`).
- [ ] (Prod) Activaste el heartbeat via Task Scheduler.
- [ ] (Prod) Forzaste HTTPS en Apache.

---

## 11. Troubleshooting

### Error "No autorizado" o redirects infinitos a `index.php`

Las cookies de sesión no están persistiendo. Causas:

- **Estás usando `127.0.0.1` en un tab y `localhost` en otro** → son dominios distintos para las cookies. Usá siempre el mismo.
- **`session.save_path` no escribible**: en `php.ini` verificar que la ruta exista y tenga permisos. En XAMPP default es `C:\xampp\tmp` — chequear que la carpeta exista.

### "Unexpected token '<'" en el navegador al hacer acciones

El backend está devolviendo HTML (error PHP) en vez de JSON. Causas comunes:

1. **Falta correr una migración**. Abrí DevTools → Network → buscá la request fallida → ver Response. Si dice algo como "Unknown column 'specs'", corré la migración 001.
2. **PHP no procesa el archivo**: verificá que Apache esté corriendo y que la URL termine en `.php`, no `.html`.
3. **Error de sintaxis PHP**: abrí el error log en `C:\xampp\apache\logs\error.log`.

### "Access denied for user 'root'@'localhost'"

La password de root cambió o el user está mal. Probá:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -p
# (ingresar password)
```

Si no te acordás la password, en XAMPP podés resetearla desde el manager o reinstalar.

### phpMyAdmin dice "El servidor está apagado"

MySQL no está corriendo. Volvé al XAMPP Control Panel y apretá "Start" en MySQL.

Si aparece en rojo y no arranca: probablemente otro MySQL está corriendo (servicio Windows, otro XAMPP). Detener ese servicio antes.

### Las PCs aparecen todas grises

Los valores de `estado` no son los del ENUM. Verificar:

```sql
SELECT DISTINCT estado FROM computadoras;
-- Solo deben salir: OPERATIVA, MANTENIMIENTO, FUERA_SERVICIO, HIBERNANDO, ALERTA
```

Si hay vacíos o valores raros, es porque se insertó con un valor inválido (MySQL los convierte a `''`). Corregir con:

```sql
UPDATE computadoras SET estado = 'OPERATIVA' WHERE estado NOT IN ('OPERATIVA','MANTENIMIENTO','FUERA_SERVICIO','HIBERNANDO','ALERTA');
```

### No aparecen notificaciones

1. Verificá que la migración 003 corrió: `SHOW TABLES FROM soportet20_db LIKE 'notificaciones';` — debe existir.
2. Verificá que el evento está activo: `SHOW EVENTS FROM soportet20_db;` → STATUS ENABLED.
3. El polling corre cada 30s — abrí DevTools → Network → filtrar por `notificaciones.php?count=1`. Tenés que ver una request cada 30s.

### El dashboard de estadísticas está vacío o con "—"

Normal si no hay datos aún. Con solo los seeds (sin tickets resueltos todavía), vas a ver:
- KPI "Tickets abiertos": 11
- KPI "Resueltos última semana": 0
- KPI "Tiempo medio resolución": —

Resolvé algunos tickets desde la UI y refrescá el dashboard — ahí se empiezan a poblar los números.

---

## ¿Y si algo no está en esta guía?

- Problema con la DB → leé [DATABASE.md](DATABASE.md).
- Problema con endpoints → [API.md](API.md).
- Problema operacional → [OPERATIONS.md](OPERATIONS.md).
- Problema de seguridad → [SECURITY.md](SECURITY.md).
- Quiero entender la arquitectura → [ARCHITECTURE.md](ARCHITECTURE.md).
