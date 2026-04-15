# Seguridad

Documento de referencia de las medidas de seguridad implementadas en SoportET20, pensado tanto para auditores externos como para el próximo dev que toque el proyecto.

## Resumen ejecutivo

| Superficie | Estado | Notas |
|---|---|---|
| Autenticación | ✅ bcrypt + session regeneration | No hay JWT ni tokens propios. |
| Autorización (RBAC) | ✅ 3 roles, validación server-side | ADMIN / TECNICO / PROFESOR. |
| SQL Injection | ✅ 100% prepared statements | PDO con `ATTR_EMULATE_PREPARES = false`. |
| XSS | ✅ `escapeHtml()` por convención | Todo contenido dinámico pasa por el helper. |
| CSRF | ⚠ No implementado | Aceptable para uso intranet; ver abajo. |
| Secrets management | ✅ Env vars | `db.php` NO tiene credenciales hardcoded. |
| Password hashing | ✅ bcrypt (`PASSWORD_DEFAULT`) | Cost factor default de PHP (10). |
| Rate limiting | ❌ Sin implementar | Riesgo bajo (escuela intranet). |
| Audit log | ✅ `log_acciones` | Toda mutación queda trazada. |
| Session hijacking | ✅ `session_regenerate_id(true)` al login | Cookies httpOnly por default de PHP. |
| HTTPS | ⚠ Depende de deploy | No se fuerza en app; configurar en Apache. |

---

## Autenticación

### Flujo de login

1. Usuario envía `POST /backend/auth.php` con `{ usuario, clave }`.
2. El backend busca el usuario por `usuario` (case-sensitive).
3. Si existe y está activo, compara con `password_verify($clave, $user['clave'])`.
4. Si OK: `session_regenerate_id(true)` para prevenir session fixation → setea `$_SESSION['user_id']`, `usuario`, `nombre`, `rol` → registra `LOGIN_EXITOSO` en auditoría.
5. Si falla: registra `LOGIN_FALLIDO` con el `usuario_intentado` (para detectar ataques de fuerza bruta vía el log).
6. Usuario inactivo: registra `LOGIN_USUARIO_INACTIVO` y devuelve 403 genérico.

### Hash de contraseñas

```php
// Al crear usuario / cambiar clave:
$hash = password_hash($clave, PASSWORD_DEFAULT);

// Al verificar:
password_verify($claveIngresada, $user['clave']);
```

`PASSWORD_DEFAULT` = bcrypt con cost 10 en PHP 8. No hay salt manual (bcrypt lo incluye). **No** se implementa cost factor más alto porque la diferencia en un server de escuela no se justifica.

### Sesiones

- **Transport**: cookie `PHPSESSID`, httpOnly por defecto de PHP.
- **Storage**: `/tmp` (Linux) o `C:\xampp\tmp` (Windows) — acceso restringido al user del web server.
- **Regeneration**: `session_regenerate_id(true)` en login para evitar session fixation. Destrucción en logout (`session_destroy()`).
- **Timeout**: lo que setee `php.ini` (`session.gc_maxlifetime`). Recomendado: 8 hs para uso en escuela.

### Lo que NO hay

- **Sin JWT**: no lo necesitamos. No hay app móvil ni API externa.
- **Sin refresh tokens**: las sesiones son simples.
- **Sin "recordarme"**: hay que loguearse cada sesión.
- **Sin 2FA**: se podría agregar en el futuro (TOTP con librería `spomky-labs/otphp`), por ahora no justifica la complejidad.

---

## Autorización (RBAC)

3 roles: `ADMIN`, `TECNICO`, `PROFESOR`. La matriz completa está en [API.md § Tabla de permisos](API.md).

### Validación en 3 capas

1. **HTTP layer** — `session_check.php` rechaza con 401 si no hay `$_SESSION['user_id']`.
2. **Endpoint layer** — cada endpoint que lo necesita llama `requiereAdmin()` o `requiereRol(['ADMIN','TECNICO'])`.
3. **UI layer** — el sidebar y las secciones condicionales se renderizan o no según `$currentUser['rol']`.

**Nunca** se confía solo en la UI. La UI oculta lo inadecuado para UX, pero el endpoint valida.

### Casos especiales

- **`aulas_info.php` GET**: todos los roles pueden consumirlo, pero el endpoint **remueve el campo `passwords`** si el rol es PROFESOR y expone `passwords_ocultas: true`. El frontend usa esa flag para mostrar el mensaje "Solo ADMIN/TECNICO pueden ver las contraseñas".
- **`usuarios.php` DELETE/PUT**: el endpoint impide que un ADMIN se desactive a sí mismo o se cambie el rol. Evita lockout accidental.
- **`tickets.php` PATCH**: solo ADMIN y TECNICO pueden resolver. Los PROFESOR pueden crear pero no cerrar.

---

## SQL Injection

### Política

**100%** de las queries dinámicas usan prepared statements con PDO. Cero concatenación de input de usuario.

```php
// ✅ Correcto
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = ?');
$stmt->execute([$usuario]);

// ❌ Nunca
$stmt = $pdo->query("SELECT * FROM usuarios WHERE usuario = '$usuario'");
```

### Caso especial: LIMIT/OFFSET

MySQL no acepta placeholders para LIMIT/OFFSET por default. Para evitar que se interpolen como strings (que generaría error de sintaxis), se bindean explícitamente como enteros:

```php
$stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
$stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
```

Esto requiere `PDO::ATTR_EMULATE_PREPARES = false`, que ya está configurado en `db.php`.

### Nombres de tabla/columna

No hay casos donde el nombre de tabla o columna venga del input del usuario. Las filter lists que permiten elegir columnas para ordenar/filtrar usan **whitelisting** explícito:

```php
$whitelist = ['creado_en', 'prioridad', 'estado'];
$sortBy = in_array($_GET['sort'] ?? '', $whitelist, true) ? $_GET['sort'] : 'creado_en';
```

(Aunque hoy no tenemos ese caso — todos los orders son hardcoded.)

---

## Cross-Site Scripting (XSS)

### Frontend

Todo contenido que viene del backend y se inserta con `.innerHTML` pasa por `escapeHtml()` (definido en `scripts_common.php`):

```js
function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    })[c]);
}
```

Se usa sistemáticamente en:
- Renderizado de tablas (tickets, auditoría, PCs, usuarios).
- Option values de los `<select>` dinámicos.
- IDs y datos en atributos (como `data-notif-id`, `onclick` handlers).
- Tooltips (`title="..."`).

### Backend

Las respuestas del backend son JSON (`Content-Type: application/json`) que por definición no ejecuta scripts. Los únicos casos donde el backend emite HTML son:
- Errores antes del `header('Content-Type: application/json')`: cubierto por `set_exception_handler()` que fuerza JSON.
- Output directo de PHP en páginas: siempre pasa por `htmlspecialchars($x, ENT_QUOTES, 'UTF-8')` antes de echo.

### Content Security Policy

**No implementado**. Sería un hardening adicional razonable para producción:

```php
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:;");
```

Se puede agregar en `head.php` o en la config de Apache.

---

## CSRF

### Estado actual

**No hay protección CSRF explícita.** Las mutaciones (POST/PUT/PATCH/DELETE) usan cookies de sesión y un atacante podría, teóricamente, hacer que un admin logueado dispare una acción desde un sitio malicioso.

### Mitigaciones vigentes

1. **Uso intranet**: el sistema corre dentro de la red de la escuela, no está expuesto a internet.
2. **Mismo origen**: todas las requests del frontend son `credentials: 'include'` al mismo origen (`/backend/*.php`).
3. **JSON content-type**: los endpoints de mutación esperan `application/json`. Un form HTML malicioso no puede enviar esto sin un preflight CORS (y no tenemos CORS permisivo).
4. **SameSite cookies**: PHP 8+ por default tiene `session.cookie_samesite = Lax`, que bloquea envíos CSRF desde otros sitios.

### Cómo agregarlo si se expone a internet

Flujo CSRF double-submit cookie:

```php
// En auth.php (login):
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// En cada endpoint de mutación:
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    jsonResponse(['error' => 'CSRF token inválido.'], 403);
}

// En el frontend (scripts_common.php):
const CSRF_TOKEN = window.CSRF_TOKEN; // inyectado server-side
fetch('...', { headers: { 'X-CSRF-Token': CSRF_TOKEN } });
```

---

## Rate Limiting

**No implementado**. Mitigaciones informales:

- El auditor (`log_acciones`) registra todo intento fallido de login con el `usuario_intentado`. Un DBA puede detectar patrones de fuerza bruta.
- Fail2ban en Apache (nivel infra) podría bloquear IPs con demasiados 401/403.

Para producción pública, agregar:

```php
// backend/rate_limit.php (ejemplo)
$key = 'rl:' . $_SERVER['REMOTE_ADDR'] . ':' . $_SERVER['REQUEST_URI'];
// Contar en APCu o Redis con TTL corto
```

---

## Secret management

### Credenciales de DB

`backend/db.php` lee de env vars:

```php
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'soportet20_db';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
```

**Nunca** se commitean credenciales al repo. Las defaults (`root` sin password) son para dev local XAMPP sin customización. Para producción se exportan via `/etc/apache2/envvars` (Linux) o System Environment Variables (Windows).

### Otros secrets

- **Session encryption**: PHP default (no explicitamente configurado).
- **API keys externas**: ninguna (sin PHPMailer, sin APIs de terceros).

### Logs limpios

`log_acciones` registra contextos de las acciones pero nunca:
- Contraseñas en plain text.
- Hashes de passwords.
- Session IDs.
- Tokens de ningún tipo.

Único dato "sensible" que el log guarda: el `usuario_intentado` en los LOGIN_FALLIDO (para detección de brute force). Esto es intencional.

---

## Auditoría

Ver [DATABASE.md § log_acciones](DATABASE.md#log_acciones) para el schema.

### Qué se audita

- Todo login (éxito, fallo, usuario inactivo).
- Todo logout.
- Todo CREATE/UPDATE/DELETE de aulas, PCs, usuarios, tickets, info de aula.
- Todo cambio de estado de PC (con `estado_anterior` y `estado_nuevo`).
- Escalado automático de tickets con la `keyword_detectada`.
- Cambios de estado disparados por el heartbeat.

### Qué NO se audita

- Lecturas (GET). No queremos ahogar el log con cada página cargada. Si se quisiera auditar lecturas sensibles (ej: quién vio las contraseñas de un aula), se puede agregar granularmente al endpoint de `aulas_info.php`.
- Cambios propios del sistema (timestamps, counters) — no aportan valor forense.

### Retención

Ver [OPERATIONS.md § Limpiar log de auditoría viejo](OPERATIONS.md#limpiar-log-de-auditor%C3%ADa-viejo).

Por default no hay purga automática del log de auditoría (las notificaciones sí se limpian vía evento). Esto es deliberado: el log es fuente de verdad forense.

---

## Ataques considerados y mitigaciones

| Ataque | Vector | Mitigación |
|---|---|---|
| **SQL injection** | Input en formularios/params | PDO prepared statements 100% |
| **XSS reflejado** | URLs con params | No ejecutamos HTML desde query params |
| **XSS almacenado** | Descripción de ticket, nombres, etc. | `escapeHtml()` en render + CSP (recomendado) |
| **CSRF** | Link/form en sitio malicioso | SameSite=Lax cookies + JSON content-type (uso intranet) |
| **Session fixation** | Attacker setea cookie antes del login | `session_regenerate_id(true)` al login |
| **Brute force login** | Diccionario contra /auth.php | Logging forense (falta rate limiting) |
| **Privilege escalation** | PROFESOR intenta endpoint admin | `requiereAdmin()` server-side |
| **Info disclosure** | PROFESOR intenta ver passwords de aula | `aulas_info.php` filtra por rol |
| **Self-lockout admin** | Admin se desactiva o degrada rol | Validación en `usuarios.php` |
| **Insecure deserialization** | JSON.parse de input | PHP json_decode no ejecuta código |
| **File upload abuse** | N/A | No hay endpoints de upload |
| **Directory traversal** | N/A | No hay endpoints que lean archivos por path |
| **XXE, SSRF, etc.** | N/A | No procesamos XML ni hacemos fetch desde el server |

---

## Checklist pre-producción

Antes de exponer SoportET20 fuera de la intranet:

- [ ] Cambiar las claves default (`admin123`, `tecnico123`, `profe123`).
- [ ] Crear user MySQL dedicado con permisos mínimos (no `root`).
- [ ] Setear variables de entorno de DB (no defaults hardcoded).
- [ ] Forzar HTTPS en Apache/Nginx (HSTS).
- [ ] Agregar header CSP en `head.php`.
- [ ] Agregar protección CSRF (double-submit cookie).
- [ ] Implementar rate limiting en `/auth.php` (ej: fail2ban o app-level).
- [ ] Revisar `php.ini`: `expose_php = Off`, `display_errors = Off`, `log_errors = On`.
- [ ] Activar backups automáticos diarios.
- [ ] Setear timeout de sesión razonable (ej: 4-8 hs).
- [ ] Documentar el plan de respuesta a incidentes (quién rota qué si se compromete una cuenta).

---

## Reporte de vulnerabilidades

Si encontrás un bug de seguridad:

1. **No abrir un issue público**.
2. Contactar directamente al dev mantenedor.
3. Incluir: descripción, pasos para reproducir, impacto estimado, sugerencia de fix (si tenés).

Los parches se priorizan por severidad (CVSS informal):
- **Crítico** (>9): parche en horas.
- **Alto** (7-9): parche en días.
- **Medio** (<7): siguiente ciclo de update.
