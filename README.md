# SoportET20

Sistema de gestión técnica y tickets de soporte para la **Escuela Técnica N°20 "Carlos Pellegrini"**. Monitorea el estado de las computadoras por aula, centraliza los reportes de fallas del parque informático y provee un panel de control para que el equipo técnico y la dirección puedan operar y auditar todo.

---

## Stack

- **Backend**: PHP 8.x con PDO (MySQL). Sin frameworks.
- **Base de datos**: MySQL 8 (InnoDB, utf8mb4, JSON columns, eventos).
- **Frontend**: HTML5 + vanilla JS + Tailwind CSS (CDN).
- **Charts**: Chart.js 4 (CDN, solo en panel de estadísticas).
- **Infra de desarrollo**: XAMPP (Windows) o cualquier stack LAMP.
- **Auth**: sesiones PHP con `password_hash()` + `session_regenerate_id()`.

---

## Estructura del repo

```
SoportET20/
├── backend/              # API PHP (endpoints JSON)
├── bin/                  # Scripts CLI (heartbeat)
├── docs/                 # Documentación extendida (estás acá)
├── migrations/           # Schema v2 + migraciones incrementales + seeds
├── public/               # Frontend (páginas servidas por XAMPP)
│   └── partials/         # Includes PHP reutilizables
├── database.sql          # ⚠ Schema inicial (obsoleto; usar database_v2.sql)
├── database_v2.sql       # Schema actual
└── README.md
```

---

## Quick start

```bash
# 1. Clonar en htdocs de XAMPP
# (o wherever sirva PHP tu máquina)

# 2. Crear la base de datos
mysql -u root -e "CREATE DATABASE soportet20_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Cargar schema base
mysql -u root soportet20_db < database_v2.sql

# 4. Correr migraciones en orden
mysql -u root soportet20_db < migrations/001_add_specs_pc.sql
mysql -u root soportet20_db < migrations/002_add_indexes.sql
mysql -u root soportet20_db < migrations/003_notificaciones.sql
mysql -u root soportet20_db < migrations/007_aulas_info.sql

# 5. Cargar seed (opcional pero recomendado para ver datos reales)
mysql -u root soportet20_db < migrations/004_seed_aula_6A.sql
mysql -u root soportet20_db < migrations/005_seed_aulas_4_5_6B.sql
mysql -u root soportet20_db < migrations/006_seed_tickets_iniciales.sql
mysql -u root soportet20_db < migrations/008_seed_aulas_info.sql

# 6. Abrir
# http://localhost/SoportET20/public/index.php
```

**Credenciales de prueba** (del seed de database_v2.sql):

| Usuario   | Clave         | Rol      |
|-----------|---------------|----------|
| admin     | admin123      | ADMIN    |
| tecnico   | tecnico123    | TECNICO  |
| profe     | profe123      | PROFESOR |

> ⚠ Cambiar estas claves antes de cualquier uso productivo.

---

## Features principales

- ✅ **Auth con bcrypt + sesiones** (login/logout auditado, session regeneration).
- ✅ **ABM completo** de aulas, computadoras y usuarios.
- ✅ **Estados de PC** con 5 niveles: OPERATIVA, MANTENIMIENTO, FUERA_SERVICIO, HIBERNANDO, ALERTA.
- ✅ **Sistema de tickets** con escalation automática por keywords críticas (humo, fuego, explotó, incendio, chispa, quemado).
- ✅ **Ficha técnica por PC** con specs (CPU/RAM/OS/discos) + historial de tickets.
- ✅ **Info del aula** (Access Point, contraseñas, software instalado) con permisos por rol.
- ✅ **Log de auditoría** (`log_acciones`) que registra cada cambio de estado, login, edición.
- ✅ **Notificaciones in-app** con campanita, polling y auto-limpieza vía evento MySQL.
- ✅ **Heartbeat/ping** vía script CLI + Windows Task Scheduler.
- ✅ **Dashboard con gráficas** (tickets por aula, estado PCs, resolución semanal, top PCs problemáticas).
- ✅ **Filtros avanzados** en tickets + auditoría con paginación.

---

## Documentación

- [Arquitectura](docs/ARCHITECTURE.md) — diseño, capas, diagrama de módulos, decisiones técnicas.
- [Base de datos](docs/DATABASE.md) — schema, tablas, FKs, migraciones, seeds.
- [API](docs/API.md) — referencia de todos los endpoints (método, auth, parámetros, respuestas).
- [Frontend](docs/FRONTEND.md) — páginas, partials, JS, rutas por rol.
- [Operaciones](docs/OPERATIONS.md) — setup, env vars, heartbeat, backups, troubleshooting.
- [Seguridad](docs/SECURITY.md) — auth, RBAC, XSS/SQLi prevention, auditoría.

---

## Roles

- **ADMIN**: todo. CRUD de aulas/PCs/usuarios, auditoría, estadísticas, edición de info del aula.
- **TECNICO**: ve y resuelve tickets, ve estadísticas, ve contraseñas de aulas.
- **PROFESOR**: abre tickets, ve estado de PCs, consulta info del aula (sin contraseñas).

---

## Licencia

Proyecto institucional de la Escuela Técnica N°20. Uso interno.
