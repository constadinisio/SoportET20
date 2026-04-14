# Frontend

## Filosofía

Frontend sin build step. Todo lo que ves en `public/` se sirve tal cual por XAMPP/Apache:

- **HTML enriquecido con PHP** — usamos PHP solo para auth guard, partials y datos server-rendered (nombre del usuario, rol).
- **Tailwind vía CDN** — sin postcss, sin npm. Las clases utility van directo en el HTML.
- **JavaScript vanilla** — sin React, Vue ni jQuery. Solo `fetch`, `addEventListener`, DOM API.
- **Chart.js vía CDN** — solo en el dashboard para ADMIN/TECNICO.

Este enfoque mantiene la barrera de entrada baja para el próximo dev que toque el proyecto (alumno, técnico con poca exposición a herramientas modernas).

## Estructura

```
public/
├── index.php           # Login
├── home.php            # App principal (dashboard + tickets + estado + new ticket)
├── admin.php           # Panel admin (aulas + PCs + usuarios + auditoría)
├── partials/
│   ├── auth_guard.php  # Session check + redirect
│   ├── head.php        # <head> común con Tailwind CDN
│   ├── sidebar.php     # Sidebar parametrizada por $activePage
│   ├── scripts_common.php   # JS compartido: escapeHtml, logout, campanita
│   └── notification_bell.php # Markup del bell (JS en scripts_common)
└── (archivos .html como redirects legacy)
```

## Páginas

### `index.php` — Login

- No tiene auth guard (es el destino al fallar auth).
- Si ya hay sesión activa, redirige a `home.php`.
- Form simple: usuario + clave → POST a `/backend/auth.php`.
- En caso de éxito: guarda user en `sessionStorage` (respaldo, no source of truth) + redirige a `home.php`.

### `home.php` — App principal

Auth guard abierto (cualquier rol logueado). Tabs:

| Tab         | Hash URL              | Contenido                                                      |
|-------------|-----------------------|----------------------------------------------------------------|
| Dashboard   | `home.php#dashboard`  | Welcome + 3 action cards + **Estadísticas** (solo ADMIN/TECNICO)|
| Tickets     | `home.php#tickets`    | Cola de tickets con filtros + crear nuevo                      |
| Estado PC   | `home.php#status`     | Grilla de PCs por aula + modal ficha técnica                   |
| New Ticket  | `home.php#new-ticket` | Form de creación con aula/PC dropdowns                         |

**Hash routing**: al cargar la página, se lee `window.location.hash` y se muestra la tab correspondiente. Si el hash es inválido o está vacío, muestra `dashboard`.

**Modal de PC** (Ficha Técnica):
- Abre al clickear una PC en la grilla de Estado.
- Muestra: nombre + aula + estado actual (badge), ficha técnica (specs del JSON), ticket activo (con botón Resolver si rol lo permite), historial de últimos 20 tickets resueltos.
- Cierre: click fuera, botón X, o Escape.

**Modal de Info del Aula**:
- Abre desde el botón "ℹ Info aula" en el tab Estado.
- Muestra AP, contraseñas (u oculto si rol no permite), software instalado + no instalado con chips.

### `admin.php` — Panel admin

Auth guard con `$requireAdmin = true;` (solo ADMIN pasa). Tabs:

| Tab         | Hash URL                | Contenido                            |
|-------------|-------------------------|--------------------------------------|
| Aulas       | `admin.php#aulas`       | CRUD + botón "Info" por aula         |
| PCs         | `admin.php#pcs`         | CRUD de computadoras (con specs)     |
| Usuarios    | `admin.php#usuarios`    | CRUD de usuarios + gestión de roles  |
| Auditoría   | `admin.php#auditoria`   | Lista de `log_acciones` con filtros  |

**Modal universal** (`#modal`): reusable para los 4 CRUD + el de "Info del Aula". Se ramifica por `modalContext.entity`:

```js
if (entity === 'aulas')         → form básico
else if (entity === 'pcs')      → form extendido con specs + discos dinámicos
else if (entity === 'usuarios') → form con rol + clave (opcional en edit)
else if (entity === 'aula_info')→ form con AP + passwords + software
```

## Partials

### `auth_guard.php`

```php
require __DIR__ . '/partials/auth_guard.php';
```

Efectos:
- Abre sesión.
- Si no hay `$_SESSION['user_id']` → `header('Location: index.php'); exit;`.
- Si la página seteó `$requireAdmin = true;` y el rol != ADMIN → `header('Location: home.php'); exit;`.
- Expone `$currentUser` (array con id, usuario, nombre, rol).

Debe ser el PRIMER require de una página protegida, antes que cualquier output.

### `head.php`

```php
$pageTitle = 'Mi título';
require __DIR__ . '/partials/head.php';
```

Genera `<!DOCTYPE html>...<head>...</head>` con:
- Meta tags + viewport.
- Tailwind CDN.
- Fuente Inter de Google Fonts.
- `<title>` parametrizado.

### `sidebar.php`

```php
$activePage = 'dashboard';  // o 'tickets', 'aulas', etc.
require __DIR__ . '/partials/sidebar.php';
```

Renderiza la sidebar lateral con:
- Logo + nombre de la app.
- 3 items del grupo "Home" (Dashboard, Tickets, Estado PC).
- 4 items del grupo "Administración" (Aulas, PCs, Usuarios, Auditoría) — **solo si el rol es ADMIN**.
- User card al pie con nombre, rol, botón de logout.

Los items del mismo grupo que la página actual son **botones de tab** (con `onclick="showTab('X')"`). Los items del otro grupo son **links** (`<a href="admin.php#X">`).

### `scripts_common.php`

Inyecta:

```js
window.CURRENT_USER = { id, usuario, nombre, rol };

function escapeHtml(str) { ... }
async function logout() { ... }
// + auto-init de la campanita si existe #notifBellBtn en el DOM
```

Debe incluirse **después** del include del bell (si se usa) y **antes** del script específico de la página.

### `notification_bell.php`

Markup de la campanita (botón + badge + dropdown panel). El JS vive en `scripts_common.php` y se auto-activa al detectar `#notifBellBtn`.

El dropdown muestra:
- Hasta 30 notificaciones (no leídas primero, luego leídas por fecha DESC).
- Cada row tiene un puntito de color por tipo: ámbar (CREADO), rojo (CRITICO), verde (RESUELTO).
- Al clickear: marca leída + navega a `home.php#tickets` si `entidad_tipo === 'ticket'`.
- Botón "Marcar todas leídas".
- Polling de `?count=1` cada 30s para actualizar el badge.

## JavaScript: patrones

### Auth-aware fetch

```js
const res = await fetch('../backend/algo.php', { credentials: 'include' });
if (res.status === 401) { window.location.href = 'index.php'; return; }
```

En `admin.php` hay un helper `apiFetch()` que encapsula esto + interpreta errores no-OK.

### DOM injection segura

```js
element.innerHTML = items.map(t => `
    <div>${escapeHtml(t.descripcion)}</div>
`).join('');
```

**Nunca** se hace `innerHTML = userContent` sin pasar por `escapeHtml()` primero.

### Event delegation mínima

Se prefieren event listeners directos en elementos renderizados (vía `addEventListener` después de `innerHTML`). No hay un event bus global.

### State local

Cada página mantiene su estado en variables globales del script (`aulas`, `tickets`, `selectedAula`, etc.). No hay store compartido entre páginas (y no hace falta — la sesión cruza).

### Routing

Hash-based (`#dashboard`, `#tickets`). Al cargar la página se lee el hash y se llama `showTab()`. No hay history API.

## Estilo

### Tailwind

Todas las clases utility inline. Sin archivos CSS propios (salvo una línea de Inter en `head.php`).

### Paleta de colores

- **Fondo**: `bg-[#0b111a]` (body), `bg-[#0f172a]` (cards), `bg-[#1e293b]` (inputs/rows).
- **Texto**: `text-white` (titles), `text-slate-300/400/500` (body/muted/labels).
- **Borders**: `border-slate-800`, `border-slate-700`.
- **Accent primario**: `blue-500`/`blue-600` (CTA, activos).
- **Estados semánticos**:
  - Verde (`emerald-500`) — OPERATIVA, RESUELTO, éxito.
  - Ámbar (`amber-500`) — MANTENIMIENTO, ABIERTO, warning.
  - Rojo (`rose-500`) — FUERA_SERVICIO, CRITICA, error.
  - Cyan (`cyan-400`) — ALERTA.
  - Gris (`slate-400/500`) — HIBERNANDO, CERRADO, inactivo.
  - Púrpura (`purple-400/500`) — acciones secundarias (botón "Info" del aula).

### Consistencia

- Cards: `rounded-2xl` + `border border-slate-800` + `bg-[#0f172a]`.
- Inputs: `rounded-xl` + `bg-[#1e293b]` + `border-slate-700` + `focus:ring-2 focus:ring-blue-500`.
- Botones primarios: `bg-blue-600 hover:bg-blue-500 rounded-xl font-bold`.
- Badges de estado: `px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase`.

## Mobile

La UI es **responsive por grid/flex de Tailwind**, pero la experiencia está optimizada para desktop (técnicos en PC). Los modales tienen `max-h-[90vh]` y scroll interno para no romper en mobile.

Las tabs del admin + sidebar no tienen menu hamburguesa — en pantallas <768px la sidebar ocupa todo el ancho. No es ideal pero es usable.

## Accesibilidad

- Labels en todos los inputs.
- Botones con `title=` para tooltips contextuales.
- Colores pasan el ratio mínimo de contraste para texto body (aunque no se testeó formalmente con WCAG).

**No** están implementados todavía:
- `aria-live` en el toast de mensajes.
- `aria-expanded` en el dropdown de notificaciones.
- Skip-to-content links.

Es un pendiente razonable para un siguiente hardening pass.
