# Arquitectura

## Visión general

SoportET20 es una aplicación **PHP monolítica** servida por XAMPP/Apache, con un frontend de páginas estáticas enriquecidas con PHP (para auth guard y partials) y JavaScript vanilla para las interacciones. El backend expone endpoints REST-style que devuelven JSON.

No hay framework, no hay ORM, no hay build step. La simplicidad es deliberada: el proyecto lo mantiene una persona (el dev de la escuela), corre en un servidor Windows modesto, y no necesita escalar más allá de ~80 PCs y ~30 usuarios concurrentes.

## Diagrama de capas

```
┌──────────────────────────────────────────────────────────────────┐
│  NAVEGADOR                                                       │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────────┐  │
│  │ index.php       │  │ home.php        │  │ admin.php        │  │
│  │ (login)         │  │ (dashboard,     │  │ (CRUD + audit)   │  │
│  │                 │  │  tickets, PCs)  │  │                  │  │
│  └────────┬────────┘  └────────┬────────┘  └────────┬─────────┘  │
│           │                    │                    │            │
│           │     partials/ (sidebar, head, auth_guard, bell)      │
└───────────┼────────────────────┼────────────────────┼────────────┘
            │                    │                    │
            │ fetch() + credentials: 'include'         │
            ▼                    ▼                    ▼
┌──────────────────────────────────────────────────────────────────┐
│  BACKEND (PHP 8.x)                                               │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │ auth.php  |  session_check.php  |  functions.php         │    │
│  └──────────────────────────────────────────────────────────┘    │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │ aulas.php | aulas_info.php | computers.php | tickets.php │    │
│  │ usuarios.php | log_acciones.php | notificaciones.php     │    │
│  │ estadisticas.php                                         │    │
│  └──────────────────────────────────────────────────────────┘    │
│         ▲                                                        │
│         │ PDO                                                    │
└─────────┼────────────────────────────────────────────────────────┘
          ▼
┌──────────────────────────────────────────────────────────────────┐
│  MySQL 8                                                         │
│  usuarios · aulas · aulas_info · computadoras · tickets          │
│  log_acciones · notificaciones                                   │
│  evento: ev_limpiar_notificaciones_viejas                        │
└──────────────────────────────────────────────────────────────────┘
          ▲
          │
┌─────────┴────────────────────────────────────────────────────────┐
│  JOBS (cron / Task Scheduler)                                    │
│  bin/heartbeat.php  → ping a PCs por IP, actualiza estado        │
└──────────────────────────────────────────────────────────────────┘
```

## Patrones clave

### 1. Auth guard en el include

Las páginas protegidas (`home.php`, `admin.php`) inician con:

```php
require __DIR__ . '/partials/auth_guard.php';
```

El guard:
- Abre la sesión si no está abierta.
- Redirige 302 a `index.php` si no hay `$_SESSION['user_id']`.
- Si la página setea `$requireAdmin = true;` antes del include, verifica el rol y redirige a `home.php` si el usuario no es ADMIN.
- Expone `$currentUser` (array) para que la página lo use.

Resultado: **sin flash de contenido** (no se ve el layout y después salta al login), ahorra un fetch de verificación, y simplifica el JS.

### 2. Partials por composición

`public/partials/` tiene piezas reusables que NO mantienen estado:

- `head.php` — `<head>` con Tailwind CDN + `$pageTitle`.
- `sidebar.php` — sidebar única parametrizada por `$activePage` (decide qué item está activo y qué items son botones de tab vs links a la otra página).
- `scripts_common.php` — inyecta `window.CURRENT_USER`, define `escapeHtml()`, `logout()` y el auto-init de la campanita.
- `notification_bell.php` — botón + dropdown de notificaciones; el JS vive en `scripts_common.php` y se auto-activa si detecta el DOM.
- `auth_guard.php` — ya explicado.

Esto reemplazó código HTML duplicado (~300 líneas repetidas entre `home.html` y `admin.html`).

### 3. Endpoint JSON con envelope mínimo

No hay envelope `{ success, data, error }` universal. Cada endpoint devuelve la forma más natural para su caso:

- **Listas**: array plano (`[{...}, {...}]`).
- **Detalles**: objeto plano.
- **Mutaciones**: `{ success: bool, message: string, ...extras }`.
- **Errores**: status code HTTP + `{ error, message }`.

El frontend usa un helper `apiFetch()` (en `admin.php`) que interpreta 401 → redirect a login y `!res.ok` → throw.

### 4. XSS prevention por convención

Todo contenido dinámico en el frontend pasa por `escapeHtml()`:

```js
`<td>${escapeHtml(t.descripcion)}</td>`
```

En el backend, los datos salen como JSON (seguro por defecto) salvo en mensajes de error que se hardcodean.

### 5. SQL injection prevention

100% prepared statements con PDO. No hay concatenación de SQL con input del usuario. El único uso de `sprintf`/interpolación es para nombres de columna/tabla hardcodeados en código (no user input).

Para paginación se bindea `LIMIT ? OFFSET ?` como `PDO::PARAM_INT` (ver `log_acciones.php`).

### 6. Auditoría como cross-cutting concern

`functions.php::registrarAccion($pdo, $userId, $accion, $detalle)` se llama desde todos los endpoints que mutan estado. Todo cambio queda trazado con:
- quién (user_id),
- qué (acción codificada: `LOGIN_EXITOSO`, `TICKET_CREADO`, `CAMBIO_ESTADO_PC`, etc.),
- detalles estructurados (JSON con el contexto),
- IP y timestamp.

La tab de Auditoría del admin lo lee con filtros y paginación.

### 7. Escalado automático de tickets

`tickets.php::detectarCritico($descripcion)` busca keywords (humo, fuego, explotó, incendio, chispa, quemado, quemada) en el texto del ticket. Si encuentra una:
- Fuerza prioridad a `CRITICA`.
- Fuerza tipo a `HARDWARE`.
- Marca la PC como `FUERA_SERVICIO`.
- Notifica a todos los ADMIN + TECNICO con tipo `TICKET_CRITICO`.
- Registra la keyword detectada en la auditoría.

Es intencional y visible: el mensaje de respuesta al profesor dice _"Ticket escalado a CRÍTICO por detección de 'humo'"_.

### 8. Notificaciones: push-via-polling

No hay websockets ni SSE — el frontend consulta `GET /backend/notificaciones.php?count=1` cada 30 segundos. Es un fetch mínimo (solo un número), light para el server y simple para el dev.

Las notificaciones nacen de hooks en `tickets.php` cuando se crea/resuelve un ticket. Al resolver, solo notifica al profesor que reportó.

## Decisiones técnicas

| Decisión | Alternativa descartada | Por qué |
|---|---|---|
| Sin framework | Laravel / Symfony | Overhead para un solo dev mantenedor. Simplicidad gana. |
| Sin ORM | Eloquent / Doctrine | PDO + SQL directo es más rápido de entender y debugear para este tamaño. |
| Sin build step | Webpack / Vite + SPA | Tailwind CDN y JS vanilla cubren el 100% de los casos. Deploy = copiar archivos. |
| Sesiones PHP | JWT | No hay móvil ni API pública. Las cookies httpOnly son más seguras que localStorage. |
| Polling 30s | WebSocket / SSE | Complejidad operativa (reverse proxy, reconnect logic) sin beneficio real. |
| Chart.js CDN | D3 custom | Time-to-value. 5 tipos de charts en 4 horas. |
| Tailwind CDN | Tailwind build | Sin build step. Para producción a escala se puede migrar, hoy no. |
| PHP evento MySQL | Cron | Menos componentes que mantener. Limpia notificaciones >30d auto. |
| Heartbeat CLI | Daemon | Windows Task Scheduler es infra nativa y confiable en el server de la escuela. |

## Flujo de datos: crear un ticket

```
Profesor abre home.php#new-ticket
   │
   ▼
Completa form (aula + PC select + tipo + prioridad + descripción)
   │
   ▼
POST /backend/tickets.php (JSON)
   │
   ▼
┌──────────────────────────────────────────┐
│ tickets.php                              │
│  1. Valida campos + auth                 │
│  2. detectarCritico() sobre descripción  │
│     → si match: escala a CRITICA          │
│  3. BEGIN TRANSACTION                    │
│  4. INSERT tickets                       │
│  5. UPDATE computadoras SET estado = ... │
│  6. registrarAccion(TICKET_CREADO)       │
│  7. registrarAccion(CAMBIO_ESTADO_PC)    │
│  8. notificarUsuarios(ADMIN + TECNICO)   │
│  9. COMMIT                               │
└──────────────────────────────────────────┘
   │
   ▼
Respuesta: { success, ticket_id, prioridad, estado_pc, [escalado_automatico] }
   │
   ▼
Frontend: toast + redirect a tab Tickets
```

## Diseño de permisos

La lógica de permisos está en **3 capas**:

1. **HTTP**: `session_check.php` garantiza 401 si no hay sesión.
2. **Endpoint**: cada endpoint llama `requiereAdmin()` o `requiereRol(['X'])` si hace falta.
3. **UI**: el sidebar + las secciones del dashboard renderizan condicionalmente por rol (`$currentUser['rol']`).

No confiamos solo en ocultar el UI — los endpoints siempre validan.

## Naming de archivos

- Backend: `snake_case.php`, verbos de recurso (`aulas.php`, `tickets.php`).
- Migrations: `NNN_descripcion.sql` con prefijo numerado incremental.
- Partials: `snake_case.php`.
- Public: `kebab-case.html` NO — usamos `lowercase.php`.
- JS: camelCase para funciones y variables.
- CSS classes: las de Tailwind tal cual vienen.
