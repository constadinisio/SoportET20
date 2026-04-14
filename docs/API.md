# API Reference

Todos los endpoints viven en `/backend/*.php` y devuelven JSON. La autenticación usa cookies de sesión PHP (`PHPSESSID`), por lo que el frontend debe incluir `credentials: 'include'` en los `fetch()`.

## Convenciones

- **Content-Type respuesta**: `application/json`.
- **Content-Type request** (para POST/PUT/PATCH/DELETE): `application/json`.
- **Errores 401**: se devuelven cuando la sesión no es válida. El frontend debe redirigir a `index.php`.
- **Errores 403**: rol insuficiente para la operación.
- **Errores 400**: input inválido.
- **Errores 404**: recurso no encontrado.
- **Errores 500**: error interno (capturado por `set_exception_handler` en `functions.php`).

---

## Autenticación

### `POST /backend/auth.php`

Login.

**Body**: `{ "usuario": "admin", "clave": "admin123" }`

**Respuesta OK** (200):
```json
{
  "success": true,
  "user": { "id": 1, "usuario": "admin", "nombre": "Administrador", "rol": "ADMIN" }
}
```

**Errores**:
- 400 si faltan campos.
- 401 si la clave es incorrecta.
- 403 si el usuario está desactivado.

### `DELETE /backend/auth.php`

Logout. Destruye la sesión y la audita.

**Respuesta**: `{ "success": true, "message": "Sesión cerrada." }`

### `GET /backend/auth.php`

Check de sesión activa (usado como fallback por el JS, ya que el auth_guard server-side hace 302).

**Respuesta OK**: `{ "authenticated": true, "user": {...} }` (200)
**Sin sesión**: `{ "authenticated": false }` (401)

---

## Aulas

### `GET /backend/aulas.php`

Lista aulas activas. Incluye contadores de PCs.

**Query params**:
- `incluir_inactivas=1` → incluye aulas con `activa=0`.

**Respuesta**: array plano con cada aula + `total_pcs` + `pcs_operativas`.

### `POST /backend/aulas.php` _(solo ADMIN)_

Crea un aula.

**Body**: `{ id, nombre, piso, capacidad_pcs }`

### `PUT /backend/aulas.php` _(solo ADMIN)_

Actualiza un aula.

**Body**: `{ id, nombre, piso, capacidad_pcs, activa }`

### `DELETE /backend/aulas.php` _(solo ADMIN)_

Desactiva (soft-delete). Falla si el aula tiene PCs asociadas.

**Body**: `{ id }`

---

## Info del Aula

### `GET /backend/aulas_info.php?id_aula=X`

Devuelve info contextual del aula (AP, passwords, software).

- **ADMIN / TECNICO**: ven `passwords`.
- **PROFESOR**: ven todo excepto `passwords`. El campo viene en `null` y se setea `passwords_ocultas: true`.

**Respuesta**:
```json
{
  "id_aula": "6A",
  "aula_nombre": "Aula 6° A",
  "access_point": "RC. F19",
  "passwords": { "alumno": "AlumnoET20", "cfp": "CFP", "admin": null },
  "passwords_ocultas": false,
  "software": {
    "instalados": ["Android Studio", "XAMPP", ...],
    "no_instalados": []
  },
  "actualizado_en": "2026-04-14 18:35:22"
}
```

### `PUT /backend/aulas_info.php` _(solo ADMIN)_

Upsert de info del aula. Usa `INSERT ... ON DUPLICATE KEY UPDATE` para crear o actualizar en una sola op.

**Body**:
```json
{
  "id_aula": "6A",
  "access_point": "RC. F19",
  "passwords": { "alumno": "AlumnoET20", "cfp": "CFP" },
  "software": { "instalados": ["XAMPP"], "no_instalados": [] }
}
```

---

## Computadoras

### `GET /backend/computers.php`

Lista PCs.

**Query params**:
- `id_aula=X` → filtra por aula.
- `id=Y` → devuelve 1 PC (sin detalle).
- `id=Y&detalle=1` → devuelve 1 PC con `specs`, `aula_nombre`, `ticket_activo` y `historial_tickets` (últimos 20 resueltos/cerrados).

**Respuesta `detalle=1`**:
```json
{
  "id": "4-12",
  "id_aula": "4",
  "aula_nombre": "Aula 4",
  "nombre": "PC 12",
  "estado": "FUERA_SERVICIO",
  "ip": null, "mac": null,
  "observaciones": "Rack: A4",
  "specs": { "cpu": "...", "ram": "...", "os": "Windows 10 Pro", "discos": [...] },
  "ticket_activo": {
    "id": 5, "tipo": "HARDWARE", "prioridad": "ALTA", "estado": "ABIERTO",
    "descripcion": "No bootea...", "creado_en": "...",
    "reportado_por": "admin", "reportado_por_nombre": "Administrador"
  },
  "historial_tickets": [
    { "id": 3, "tipo": "SOFTWARE", "prioridad": "BAJA", "estado": "RESUELTO",
      "descripcion": "...", "nota_resolucion": "...", "creado_en": "...",
      "cerrado_en": "...", "reportado_por": "...", "resuelto_por": "..." }
  ]
}
```

### `POST /backend/computers.php` _(solo ADMIN)_

Crea una PC.

**Body**: `{ id, id_aula, nombre, ip, mac, observaciones, specs }` — `specs` como objeto JSON con `cpu, ram, os, placa, discos[]`.

### `PUT /backend/computers.php` _(solo ADMIN)_

Actualiza una PC (incluido `specs`). Maneja fallback si la columna `specs` no existe (migración 001 no corrida).

### `PATCH /backend/computers.php` _(ADMIN o TECNICO)_

Cambia solo el estado de la PC sin crear ticket.

**Body**: `{ id, estado, motivo }`

### `DELETE /backend/computers.php` _(solo ADMIN)_

Elimina una PC. Falla si tiene tickets activos.

---

## Tickets

### `GET /backend/tickets.php`

Lista tickets con filtros.

**Query params** (todos opcionales):
- `estado` — `ABIERTO | EN_PROGRESO | RESUELTO | CERRADO`
- `prioridad` — `BAJA | MEDIA | ALTA | CRITICA`
- `id_aula` — filtra por aula de la PC
- `id_pc` — filtra por PC específica
- `tipo` — `HARDWARE | SOFTWARE | RED | PERIFERICO | OTRO`
- `desde` / `hasta` — `YYYY-MM-DD`
- `busqueda` — substring en descripción

**Respuesta**: array plano con tickets + `id_aula`, `reportado_por`, `reportado_por_nombre`, `resuelto_por_usuario`.

### `POST /backend/tickets.php`

Crea un ticket. **Cualquier rol autenticado**.

**Body**: `{ id_pc, tipo, prioridad, descripcion }`

**Side effects**:
1. Si la descripción contiene keywords críticas (humo, fuego, explotó, incendio, chispa, quemado) → escala a `CRITICA`, tipo `HARDWARE`, estado PC `FUERA_SERVICIO`.
2. Según prioridad: `ALTA`/`CRITICA` → PC a `FUERA_SERVICIO`; `MEDIA`/`BAJA` → `MANTENIMIENTO`.
3. Audita `TICKET_CREADO` + `CAMBIO_ESTADO_PC`.
4. Notifica a todos los ADMIN + TECNICO (excluyendo al reporter).

**Respuesta**:
```json
{
  "success": true,
  "message": "Ticket #5 creado correctamente.",
  "ticket_id": 5,
  "prioridad": "ALTA",
  "estado_pc": "FUERA_SERVICIO",
  "escalado_automatico": false
}
```

Si hubo escalado: `escalado_automatico: true`, `keyword_detectada: "humo"`, y `message` usa "⚠ Ticket #5 ESCALADO A CRÍTICO..."

### `PATCH /backend/tickets.php` _(ADMIN o TECNICO)_

Actualiza estado del ticket.

**Body**: `{ id, estado, nota_resolucion? }`

**Side effects** si se pasa a RESUELTO/CERRADO:
1. Setea `resuelto_por` y `cerrado_en`.
2. Restaura PC a `OPERATIVA`.
3. Audita el cambio.
4. Notifica al profesor que reportó (si no es él mismo quien resolvió).

---

## Usuarios

### `GET /backend/usuarios.php` _(solo ADMIN)_

Lista usuarios activos.

**Query params**:
- `incluir_inactivos=1`

### `POST /backend/usuarios.php` _(solo ADMIN)_

Crea un usuario.

**Body**: `{ usuario, clave, nombre_completo, rol }` — la clave se hashea con `password_hash()`.

### `PUT /backend/usuarios.php` _(solo ADMIN)_

Actualiza un usuario. Si `clave` está vacía, no la modifica. Valida que el admin no se cambie su propio rol.

### `DELETE /backend/usuarios.php` _(solo ADMIN)_

Desactiva un usuario. Valida que el admin no se desactive a sí mismo.

---

## Log de Auditoría

### `GET /backend/log_acciones.php` _(solo ADMIN)_

Lista paginada de auditoría.

**Query params**:
- `id_usuario` — filtra por usuario
- `accion` — búsqueda parcial (LIKE)
- `desde` / `hasta` — `YYYY-MM-DD`
- `page` — default 1
- `limit` — default 50, max 200

**Respuesta**:
```json
{
  "total": 342,
  "page": 1,
  "limit": 50,
  "total_pages": 7,
  "logs": [
    { "id": 342, "id_usuario": 1, "accion": "TICKET_CREADO",
      "detalle": "{\"ticket_id\":5,...}", "detalle_json": {...},
      "ip": "192.168.1.10", "creado_en": "2026-04-14 18:35:22",
      "usuario": "admin", "nombre_completo": "Administrador", "rol": "ADMIN" }
  ]
}
```

`detalle_json` es el parseo del campo `detalle` si era JSON válido.

---

## Notificaciones

### `GET /backend/notificaciones.php`

Devuelve últimas 30 notificaciones del usuario autenticado.

**Respuesta**:
```json
{
  "no_leidas": 3,
  "notificaciones": [
    { "id": 42, "tipo": "TICKET_CRITICO", "titulo": "🚨 Ticket CRÍTICO #5",
      "mensaje": "PC 4-12 — No bootea...", "entidad_tipo": "ticket",
      "entidad_id": "5", "leida": 0, "creada_en": "...", "leida_en": null }
  ]
}
```

### `GET /backend/notificaciones.php?count=1`

Endpoint liviano para polling. Devuelve solo el contador de no leídas.

**Respuesta**: `{ "count": 3 }`

### `PATCH /backend/notificaciones.php`

Marca una notificación como leída (debe pertenecer al usuario).

**Body**: `{ id }`

**Respuesta**: `{ "success": true, "updated": 1 }`

### `POST /backend/notificaciones.php`

Marca todas las notificaciones del usuario como leídas.

**Body**: `{ "accion": "todas" }`

**Respuesta**: `{ "success": true, "updated": 5 }`

---

## Estadísticas

### `GET /backend/estadisticas.php` _(ADMIN o TECNICO)_

Devuelve datos agregados para el dashboard. 1 sola llamada, 6 secciones.

**Respuesta**:
```json
{
  "kpis": {
    "tickets_abiertos": 11,
    "resueltos_ultima_semana": 4,
    "pcs_operativas": 66,
    "pcs_no_operativas": 11,
    "pcs_total": 77,
    "pcs_operativas_pct": 85.7,
    "tiempo_medio_resolucion_horas": 18.5
  },
  "tickets_por_aula": [
    { "aula": "4", "estado": "ABIERTO", "cnt": 10 },
    { "aula": "6B", "estado": "ABIERTO", "cnt": 1 }
  ],
  "estado_pcs": [
    { "estado": "OPERATIVA", "cnt": 66 },
    { "estado": "MANTENIMIENTO", "cnt": 10 },
    { "estado": "FUERA_SERVICIO", "cnt": 1 }
  ],
  "tickets_por_tipo": [
    { "tipo": "HARDWARE", "cnt": 9 },
    { "tipo": "SOFTWARE", "cnt": 2 }
  ],
  "resolucion_semanal": [
    { "inicio_semana": "2026-04-07", "resueltos": 3, "tiempo_medio_h": 14.2 },
    { "inicio_semana": "2026-04-14", "resueltos": 1, "tiempo_medio_h": 22.0 }
  ],
  "top_pcs": [
    { "id_pc": "4-12", "id_aula": "4", "nombre": "PC 12", "incidencias": 3 }
  ]
}
```

---

## Errores comunes

| Situación | Código | Respuesta |
|---|---|---|
| Sin sesión | 401 | `{ "error": "No autorizado", "message": "Sesión no válida..." }` |
| Rol insuficiente | 403 | `{ "error": "Acceso denegado", "message": "Tu rol no tiene permisos..." }` |
| Recurso no encontrado | 404 | `{ "error": "...", "message": "..." }` |
| Input inválido | 400 | `{ "success": false, "message": "..." }` |
| Error interno | 500 | `{ "error": "Error interno del servidor", "message": "..." }` |

## Tabla de permisos por endpoint

| Endpoint                            | PROFESOR | TECNICO | ADMIN |
|-------------------------------------|:--------:|:-------:|:-----:|
| `GET /auth.php`                     |    ✅    |   ✅    |  ✅   |
| `POST /auth.php`                    |    ✅    |   ✅    |  ✅   |
| `DELETE /auth.php`                  |    ✅    |   ✅    |  ✅   |
| `GET /aulas.php`                    |    ✅    |   ✅    |  ✅   |
| `POST/PUT/DELETE /aulas.php`        |    ❌    |   ❌    |  ✅   |
| `GET /aulas_info.php`               | ✅ *     |   ✅    |  ✅   |
| `PUT /aulas_info.php`               |    ❌    |   ❌    |  ✅   |
| `GET /computers.php`                |    ✅    |   ✅    |  ✅   |
| `POST/PUT/DELETE /computers.php`    |    ❌    |   ❌    |  ✅   |
| `PATCH /computers.php`              |    ❌    |   ✅    |  ✅   |
| `GET /tickets.php`                  |    ✅    |   ✅    |  ✅   |
| `POST /tickets.php`                 |    ✅    |   ✅    |  ✅   |
| `PATCH /tickets.php`                |    ❌    |   ✅    |  ✅   |
| `GET/POST/PUT/DELETE /usuarios.php` |    ❌    |   ❌    |  ✅   |
| `GET /log_acciones.php`             |    ❌    |   ❌    |  ✅   |
| `* /notificaciones.php`             |    ✅    |   ✅    |  ✅   |
| `GET /estadisticas.php`             |    ❌    |   ✅    |  ✅   |

_\* PROFESOR ve `aulas_info` excepto el campo `passwords` (retornado como `null`)._
