# bin/ — Scripts operativos EduMonitor-ET20

Scripts de mantenimiento y tareas programadas. No son accesibles desde la web: el directorio `bin/` vive fuera de `public/` y cada script valida `PHP_SAPI === 'cli'` al inicio.

---

## heartbeat.php

### ¿Qué hace?

Recorre todas las computadoras registradas en la tabla `computadoras` que tengan una IP asignada, intenta un ping TCP contra cada una y actualiza su `estado` según el resultado combinado con el horario escolar.

El "ping" se hace con `fsockopen()` contra dos puertos típicos de Windows:

1. **445** — SMB (File and Printer Sharing). Suele estar abierto en PCs del aula.
2. **135** — RPC Endpoint Mapper. Fallback si SMB está filtrado.

No se usa `shell_exec("ping ...")` porque:

- Es un riesgo de inyección si algún día la IP viene de input no sanitizado.
- En muchos entornos ICMP está bloqueado y `ping` da falsos negativos.
- `fsockopen` es portable entre Windows y Linux sin depender del binario `ping.exe`.

### Reglas de transición de estado

| Ping | Horario escolar | Estado actual | Nuevo estado |
|------|-----------------|---------------|--------------|
| OK   | Sí              | HIBERNANDO    | OPERATIVA    |
| OK   | Sí              | OPERATIVA     | (sin cambio) |
| OK   | No              | OPERATIVA     | ALERTA       |
| OK   | No              | HIBERNANDO    | ALERTA       |
| FAIL | Sí              | cualquiera    | (sin cambio) |
| FAIL | No              | OPERATIVA     | HIBERNANDO   |

**Estados protegidos** (nunca los pisa el heartbeat):

- `MANTENIMIENTO` — marcado por un técnico.
- `FUERA_SERVICIO` — PC dada de baja operativamente.
- `ALERTA` durante horario escolar (flag de seguridad, solo lo limpia un humano o la propia transición a HIBERNANDO fuera de hora).

Cada cambio real se registra en `log_acciones` con la acción `HEARTBEAT_CAMBIO_ESTADO` y `id_usuario = NULL` (acción del sistema).

### Salida estándar

Al finalizar imprime una línea resumen en stdout, por ejemplo:

```
[2026-04-13 22:45:00] Heartbeat: 18 PCs, 12 online, 3 state changes
```

Los errores de ping individuales o de update se escriben en stderr como `WARN` y no abortan el script. Solo un error fatal (DB caída, excepción no manejada) termina con exit code `1`.

### Variables de entorno

Usa la misma convención que `backend/db.php`:

| Variable  | Default             | Descripción                          |
|-----------|---------------------|--------------------------------------|
| `DB_HOST` | `localhost`         | Host de MySQL                        |
| `DB_NAME` | `soportet20_db`   | Nombre de la base                    |
| `DB_USER` | `root`              | Usuario MySQL                        |
| `DB_PASS` | *(vacío)*           | Password MySQL                       |

**Nunca** hardcodees credenciales en el script ni las pongas en el comando del scheduler. En Windows se configuran con `setx DB_PASS ...` o desde "Editar variables de entorno del sistema". En Linux se declaran en el crontab o en un `/etc/default/edumonitor`.

### Horario escolar (configurable)

Está hardcodeado en la cabecera de `heartbeat.php` mediante constantes:

```php
const HORARIO_DIAS_HABILES = [1, 2, 3, 4, 5]; // 1=Lun ... 7=Dom
const HORARIO_HORA_INICIO  = 7;               // 07:00
const HORARIO_HORA_FIN     = 18;              // 18:00 (exclusivo)
```

Para cambiarlo editá esas tres constantes. Si en el futuro se necesita por aula o por turno, mover a tabla `configuracion_horarios` y cargar al inicio del script.

### Uso manual

```bash
# Ejecución real
php bin/heartbeat.php

# Modo prueba: no escribe en DB, solo imprime lo que haría
php bin/heartbeat.php --dry-run
```

---

## Programación de la tarea (NO se configura automáticamente)

> El script **solo crea el archivo**. Configurar el scheduler es responsabilidad del operador de sistemas.

### Linux — cron

Editá el crontab del usuario que tenga acceso a la DB:

```cron
*/5 * * * * php /var/www/SoportET20/bin/heartbeat.php >> /var/log/edumonitor/heartbeat.log 2>&1
```

Asegurate de que `/var/log/edumonitor/` exista y sea escribible, y que las variables de entorno `DB_*` estén definidas para ese usuario (por ejemplo en `~/.profile` o inyectadas en la línea del cron).

### Windows Server — Task Scheduler

Paso a paso para correr cada 5 minutos:

1. Abrir **Task Scheduler** (Programador de tareas).
2. Panel derecho → **Create Task...** (no "Create Basic Task", necesitamos control de usuario).
3. Pestaña **General**:
   - Name: `EduMonitor Heartbeat`.
   - Description: `Ping TCP a PCs del aula cada 5 min y actualiza estado.`
   - Run whether user is logged on or not.
   - Marcar **Run with highest privileges**.
4. Pestaña **Triggers** → **New...**:
   - Begin the task: `On a schedule`.
   - `Daily`, recur every `1 day`.
   - Marcar **Repeat task every** `5 minutes` for a duration of `1 day`.
5. Pestaña **Actions** → **New...**:
   - Action: `Start a program`.
   - Program/script: `C:\xampp\php\php.exe` (o la ruta a `php.exe` de tu instalación).
   - Add arguments: `C:\Users\User\Workspace\Escuela Técnica 20 D.E. 20\SoportET20\bin\heartbeat.php`.
   - Start in: `C:\Users\User\Workspace\Escuela Técnica 20 D.E. 20\SoportET20`.
6. Pestaña **Conditions**: desmarcar `Start the task only if the computer is on AC power` si es un servidor fijo.
7. Pestaña **Settings**:
   - Marcar `If the task fails, restart every 1 minute` (intentar 3 veces).
   - `Stop the task if it runs longer than 5 minutes`.
8. Guardar. Pedirá credenciales del usuario bajo el que va a correr — usar una cuenta de servicio con acceso a MySQL, no una cuenta personal.

Para loguear salida a archivo, envolver en un `.bat`:

```bat
@echo off
set DB_HOST=localhost
set DB_NAME=soportet20_db
set DB_USER=edumonitor_service
set DB_PASS=__el_password__
"C:\xampp\php\php.exe" "C:\Users\User\Workspace\Escuela Técnica 20 D.E. 20\SoportET20\bin\heartbeat.php" >> "C:\Logs\edumonitor\heartbeat.log" 2>&1
```

Y apuntar la Action a ese `.bat` en lugar de `php.exe` directamente. **Ese `.bat` NO debe commitearse al repo** (contiene credenciales); guardarlo fuera del working copy.

---

## Troubleshooting

### "Todos los pings fallan" (0 online)

1. **Firewall de Windows**: por default bloquea 445/135 desde otras subredes. Desde el servidor, probar:
   ```
   Test-NetConnection 192.168.1.50 -Port 445
   ```
   Si da `TcpTestSucceeded : False`, el firewall del cliente bloquea. En las PCs del aula habilitar la regla "File and Printer Sharing (SMB-In)" para el perfil de red que corresponda.

2. **ICMP habilitado pero TCP bloqueado**: el script **no usa ICMP** a propósito. Si querés fallback por ICMP hay que agregarlo explícitamente, pero implica `exec("ping")` y vuelve al problema de inyección.

3. **IPs incorrectas**: verificar en la UI de admin que la columna `ip` de `computadoras` esté actualizada. IPs de DHCP cambian; conviene reservar DHCP por MAC o usar IPs fijas.

4. **Subred distinta / VLAN**: si el servidor y las PCs están en VLANs separadas, confirmar que hay ruteo y que el firewall de borde permite 445/135.

5. **Timeout muy corto**: por default 1 segundo. En redes lentas aumentar `PING_TIMEOUT_SEGUNDOS` a 2 o 3.

### "El script nunca cambia estados"

- Verificar que `log_acciones` tenga entradas `HEARTBEAT_CAMBIO_ESTADO`.
- Correr con `--dry-run` y revisar qué decisiones toma.
- Confirmar que la hora del servidor es correcta (`Get-Date` en PowerShell, `date` en Linux). Si el servidor está en UTC y la escuela en ART, la ventana de "horario escolar" se desfasa 3 horas.

### "El cron corre pero no veo logs"

- En Windows, Task Scheduler no captura stdout/stderr del proceso; por eso se recomienda el `.bat` con redirección.
- En Linux verificar permisos del archivo de log y que el usuario del cron pueda escribir.

### "PHP no encuentra PDO MySQL"

Habilitar en `php.ini`:
```
extension=pdo_mysql
```

---

## Próximos pasos sugeridos

- Agregar métrica `ultimo_heartbeat_ts` a `computadoras` para UI en tiempo real.
- Mover horario escolar a tabla configurable con override por aula.
- Agregar alerta por email/notificación cuando >N% de PCs están offline en horario escolar.
- Tests unitarios de `decidirNuevoEstado()` y `enHorarioEscolar()` (son funciones puras, fáciles de cubrir con PHPUnit).
