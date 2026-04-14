# Operaciones

Guía para instalar, operar y mantener SoportET20 en producción (servidor Windows de la escuela) o dev local.

## Requisitos

- **PHP 8.0+** con extensiones: `pdo`, `pdo_mysql`, `json`, `session`, `mbstring`.
- **MySQL 8.0+** (InnoDB, JSON columns, events).
- **Apache** (vía XAMPP) o Nginx+PHP-FPM.
- **Windows Server 2016+** o cualquier Linux moderno.
- **Task Scheduler** (Windows) o **cron** (Linux) para el heartbeat.

## Instalación inicial

### 1. Copiar el proyecto

```bash
# Windows (XAMPP)
cp -r SoportET20 C:\xampp\htdocs\SoportET20

# Linux
cp -r SoportET20 /var/www/html/SoportET20
```

### 2. Configurar variables de entorno

`backend/db.php` lee credenciales desde variables de entorno:

```php
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'soportet20_db';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
```

**En XAMPP/Windows**: setear en `php.ini` o via variables de sistema (ControlPanel → System → Environment Variables):

```
DB_HOST=localhost
DB_NAME=soportet20_db
DB_USER=soportet20_app
DB_PASS=<clave fuerte>
```

**En Linux**: agregar a `/etc/apache2/envvars` o al service unit:

```bash
export DB_HOST=localhost
export DB_NAME=soportet20_db
export DB_USER=soportet20_app
export DB_PASS=...
```

Reiniciar Apache/PHP-FPM después de cambiar env vars.

### 3. Crear usuario MySQL dedicado (recomendado)

Para producción, NO usar `root`:

```sql
CREATE USER 'soportet20_app'@'localhost' IDENTIFIED BY '<clave fuerte>';
CREATE DATABASE soportet20_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON soportet20_db.* TO 'soportet20_app'@'localhost';
FLUSH PRIVILEGES;
```

_(No le damos CREATE/DROP/ALTER — esos los corre el DBA manualmente con root cuando hay migración.)_

### 4. Cargar schema + migraciones

Ver [DATABASE.md](DATABASE.md#migraciones) para el orden completo.

```bash
mysql -u root soportet20_db < database_v2.sql
mysql -u root soportet20_db < migrations/001_add_specs_pc.sql
mysql -u root soportet20_db < migrations/002_add_indexes.sql
mysql -u root soportet20_db < migrations/003_notificaciones.sql
mysql -u root soportet20_db < migrations/007_aulas_info.sql
# Seeds (opcional, solo para demo/dev)
mysql -u root soportet20_db < migrations/004_seed_aula_6A.sql
mysql -u root soportet20_db < migrations/005_seed_aulas_4_5_6B.sql
mysql -u root soportet20_db < migrations/006_seed_tickets_iniciales.sql
mysql -u root soportet20_db < migrations/008_seed_aulas_info.sql
```

### 5. Verificar que el event_scheduler esté ON

La migración 003 ya setea esto, pero verificar:

```sql
SHOW VARIABLES LIKE 'event_scheduler';
-- debe dar ON
```

Si está OFF: en `my.ini` / `my.cnf` agregar bajo `[mysqld]`:
```
event_scheduler = ON
```

Y reiniciar MySQL.

### 6. Cambiar las claves de prueba

`database_v2.sql` crea 3 usuarios con claves conocidas. **Cambiar antes de producción**:

```sql
UPDATE usuarios SET clave = '$2y$12$...' WHERE usuario = 'admin';
-- hash generado con PHP: password_hash('nueva_clave', PASSWORD_DEFAULT)
```

O mejor, hacerlo desde el panel admin web una vez logueado.

### 7. Setear el heartbeat

Ver [Heartbeat](#heartbeat) más abajo.

### 8. Primer login

Abrir `http://localhost/SoportET20/public/index.php` y loguearse.

## Heartbeat

El script `bin/heartbeat.php` pingeas todas las PCs del sistema y actualiza su estado según:

- **Ping OK + horario escolar** → si estaba HIBERNANDO, pasa a OPERATIVA.
- **Ping OK + fuera de horario** → pasa a ALERTA (alguien la dejó prendida de noche).
- **Ping fail + fuera de horario** → pasa a HIBERNANDO.
- **Nunca** sobreescribe MANTENIMIENTO ni FUERA_SERVICIO.

### Setup en Windows Task Scheduler

1. Abrir **Task Scheduler**.
2. "Create Basic Task":
   - Name: `SoportET20 Heartbeat`
   - Trigger: **Daily**, every 5 minutes, indefinite.
3. Action: **Start a program**:
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\SoportET20\bin\heartbeat.php`
4. Conditions: uncheck "Start the task only if the computer is on AC power".
5. Settings: check "Run task as soon as possible after a scheduled start is missed".

Ver detalles y troubleshooting en `bin/README.md`.

### Setup en Linux (cron)

```bash
# /etc/cron.d/soportet20
*/5 * * * * www-data /usr/bin/php /var/www/html/SoportET20/bin/heartbeat.php
```

### Probar en seco

```bash
php bin/heartbeat.php --dry-run
```

No hace UPDATE, solo imprime qué pasaría.

## Backups

### Full dump diario

**Windows Task Scheduler** → script `.bat`:

```batch
@echo off
set FECHA=%DATE:~-4%-%DATE:~3,2%-%DATE:~0,2%
C:\xampp\mysql\bin\mysqldump.exe -u root --single-transaction --routines --events soportet20_db > C:\Backups\soportet20_%FECHA%.sql
forfiles /p C:\Backups /s /m soportet20_*.sql /d -30 /c "cmd /c del @path"
```

Corre diario 2 AM. Mantiene últimos 30 días.

### Linux (cron)

```bash
# /etc/cron.d/soportet20-backup
0 2 * * * root /usr/bin/mysqldump --single-transaction --routines --events soportet20_db | gzip > /var/backups/soportet20_$(date +\%F).sql.gz && find /var/backups -name "soportet20_*.sql.gz" -mtime +30 -delete
```

### Restaurar

```bash
mysql -u root soportet20_db < backup_2026-04-14.sql
```

## Logs

### Apache

- **Windows XAMPP**: `C:\xampp\apache\logs\error.log`
- **Linux**: `/var/log/apache2/error.log`

### PHP

`set_exception_handler` en `functions.php` captura errores no manejados y los devuelve como JSON al cliente. Pero **también** hay que habilitar log a disco:

`php.ini`:
```ini
log_errors = On
error_log = C:\xampp\php\logs\php_error.log
```

### App-level (auditoría)

No hay log de texto propio — todo lo relevante se guarda en la tabla `log_acciones`. Consultable desde el panel Admin → tab Auditoría.

## Mantenimiento

### Limpieza automática de notificaciones

El evento MySQL `ev_limpiar_notificaciones_viejas` corre todos los días a las 3 AM y borra notificaciones leídas >30 días. Verificar que esté activo:

```sql
SHOW EVENTS FROM soportet20_db;
```

Si aparece `STATUS: ENABLED`, está corriendo.

### Reindex / optimize

Para tablas con mucha escritura (log_acciones, notificaciones):

```sql
OPTIMIZE TABLE log_acciones, notificaciones, tickets;
```

Hacerlo en ventana de mantenimiento (lockea la tabla brevemente).

### Limpiar log de auditoría viejo

Si el volumen se descontrola (>500K rows):

```sql
DELETE FROM log_acciones WHERE creado_en < NOW() - INTERVAL 1 YEAR;
OPTIMIZE TABLE log_acciones;
```

## Troubleshooting

### "Unexpected token '<'..." en el frontend

El backend está devolviendo HTML (probablemente un error PHP) en vez de JSON. Posibles causas:

1. **Falta una migración** — el código intentó leer una columna que no existe. Correr las migraciones pendientes.
2. **PHP no está procesando el archivo** — verificar que Apache tenga `AddHandler application/x-httpd-php .php` y que la URL termine en `.php`.
3. **Error no capturado** — `set_exception_handler` en functions.php debería capturar todo, pero si el error ocurre antes del include (ej: syntax error), no. Ver log de Apache.

### PCs aparecen todas grises

`estado` de las PCs no está en el ENUM válido. Verificar:

```sql
SELECT DISTINCT estado FROM computadoras;
-- Solo deben salir: OPERATIVA, MANTENIMIENTO, FUERA_SERVICIO, HIBERNANDO, ALERTA
```

Si hay valores raros: fue un INSERT manual con un valor inválido (el ENUM lo convierte a `''`). Corregir:

```sql
UPDATE computadoras SET estado = 'OPERATIVA' WHERE estado = '';
```

### Heartbeat no actualiza ningún estado

1. Verificar que las PCs tengan IP: `SELECT id, ip FROM computadoras WHERE ip IS NOT NULL;`
2. Correr en dry-run para ver output: `php bin/heartbeat.php --dry-run`.
3. Verificar permisos del user PHP para hacer sockets (firewall Windows puede bloquear `fsockopen`).
4. Las PCs deben tener el puerto 445 (SMB) o 135 (RPC) abierto. Si no, cambiar el puerto en `heartbeat.php`.

### Notificaciones no se borran viejas

`event_scheduler` está OFF. Ver paso 5 de instalación.

### Sesión expira muy rápido

En `php.ini`:

```ini
session.gc_maxlifetime = 28800  ; 8 horas
session.cookie_lifetime = 0     ; cookie de sesión (hasta cerrar navegador)
```

### Error "event_scheduler" al correr 003_notificaciones.sql

El user de MySQL necesita el privilegio `SUPER` o `SYSTEM_VARIABLES_ADMIN` para `SET GLOBAL`. Correr la migración como `root`:

```bash
mysql -u root soportet20_db < migrations/003_notificaciones.sql
```

## Deployment (actualizaciones)

### Update de código (sin cambios de DB)

1. Backup de seguridad: `mysqldump ... > pre_update.sql`.
2. Copiar archivos nuevos sobre el `htdocs` actual.
3. Nada más — PHP se recarga en la siguiente request.

### Update con migración de DB

1. Backup.
2. Correr migraciones nuevas **antes** de subir el código (para que el código nuevo no encuentre schema viejo).
3. Copiar archivos.
4. Verificar con un smoke test (login + ver dashboard).

### Rollback

1. Restaurar backup de DB.
2. Revertir código (`git revert` o restaurar copia previa de `htdocs`).

## Monitoreo

No hay stack de observability (Prometheus/Grafana) — la escuela no lo necesita. Las métricas claves están expuestas vía:

- **Panel Admin → Auditoría**: todo lo que pasó con contexto.
- **Panel Home → Dashboard**: KPIs operativos.
- **Logs de Apache**: errores técnicos.

Un chequeo manual diario del dashboard por parte del técnico o director es suficiente para el volumen de operación.

## Performance

### Números actuales

- ~77 PCs, ~11 tickets activos, ~5 usuarios — queries sub-10ms.
- Dashboard full (6 queries agregadas): ~20ms en local.
- Página home.php (sin cache): ~80ms en servidor típico.

### Si crece

- Agregar índice compuesto si queries puntuales se hacen lentas (primero EXPLAIN).
- Cache de `GET /estadisticas.php` en sesión por 60s (cambio trivial, si el volumen lo pide).
- Migrar Tailwind CDN a build local si se quiere purgar clases no usadas.

Hoy no hace falta nada de esto.
