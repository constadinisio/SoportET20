# Base de Datos

## Motor

**MySQL 8** con:
- Engine: **InnoDB** (foreign keys + transactions).
- Charset: **utf8mb4** / collation **utf8mb4_unicode_ci**.
- `event_scheduler = ON` (para limpieza automática de notificaciones viejas).

## Esquema

```
┌─────────────────┐
│    usuarios     │
│─────────────────│
│ id (PK)         │◄──┐
│ usuario (UQ)    │   │
│ clave           │   │
│ nombre_completo │   │
│ rol (ENUM)      │   │
│ activo          │   │
│ creado_en       │   │
└─────────────────┘   │
                      │
                      │                    ┌──────────────────┐
                      ├───────────────────►│   log_acciones   │
                      │                    │──────────────────│
                      │                    │ id (PK)          │
                      │                    │ id_usuario (FK)  │
                      │                    │ accion           │
                      │                    │ detalle (JSON)   │
                      │                    │ ip               │
                      │                    │ creado_en        │
                      │                    └──────────────────┘
                      │
                      │                    ┌────────────────────┐
                      ├───────────────────►│  notificaciones    │
                      │                    │────────────────────│
                      │                    │ id (PK)            │
                      │                    │ id_usuario_destino │
                      │                    │ tipo (ENUM)        │
                      │                    │ titulo, mensaje    │
                      │                    │ entidad_tipo       │
                      │                    │ entidad_id         │
                      │                    │ leida              │
                      │                    │ creada_en, leida_en│
                      │                    └────────────────────┘
                      │
┌─────────────────┐   │
│     aulas       │   │    ┌─────────────────┐
│─────────────────│   │    │   aulas_info    │
│ id (PK)         │◄──┼────│─────────────────│
│ nombre          │   │    │ id_aula (PK/FK) │
│ piso            │   │    │ access_point    │
│ capacidad_pcs   │   │    │ passwords (JSON)│
│ activa          │   │    │ software (JSON) │
│ creado_en       │   │    │ actualizado_en  │
└────┬────────────┘   │    └─────────────────┘
     │                │
     │                │
     ▼                │
┌─────────────────┐   │    ┌──────────────────┐
│  computadoras   │   │    │     tickets      │
│─────────────────│   │    │──────────────────│
│ id (PK)         │◄──┼────│ id (PK)          │
│ id_aula (FK)    │   │    │ id_pc (FK)       │
│ nombre          │   │    │ id_usuario (FK)  │─┐
│ estado (ENUM)   │   │    │ tipo (ENUM)      │ │
│ ip, mac         │   │    │ prioridad (ENUM) │ │
│ observaciones   │   │    │ descripcion      │ │
│ specs (JSON)    │   │    │ estado (ENUM)    │ │
│ creado_en       │   │    │ nota_resolucion  │ │
└─────────────────┘   │    │ resuelto_por (FK)│─┤
                      │    │ creado_en        │ │
                      │    │ cerrado_en       │ │
                      │    └──────────────────┘ │
                      │                         │
                      └─────────────────────────┘
```

## Tablas

### `usuarios`

Cuentas de acceso al sistema.

| Campo             | Tipo                            | Notas                                |
|-------------------|---------------------------------|--------------------------------------|
| id                | INT UNSIGNED AUTO_INCREMENT PK  |                                      |
| usuario           | VARCHAR(50) UNIQUE              | Login name                           |
| clave             | VARCHAR(255)                    | bcrypt hash (`password_hash()`)      |
| nombre_completo   | VARCHAR(100)                    |                                      |
| rol               | ENUM('ADMIN','TECNICO','PROFESOR') |                                   |
| activo            | TINYINT(1) default 1            | Soft-delete                          |
| creado_en         | DATETIME default NOW            |                                      |

**Índices**: `(rol, activo)` para queries de notificación por rol.

### `aulas`

Aulas de la escuela.

| Campo           | Tipo                          | Notas                           |
|-----------------|-------------------------------|----------------------------------|
| id              | VARCHAR(10) PK                | Ej: `'4'`, `'6A'`, `'101'`       |
| nombre          | VARCHAR(100)                  | Ej: `'Aula 6° A'`                |
| piso            | INT                           |                                  |
| capacidad_pcs   | INT                           |                                  |
| activa          | TINYINT(1) default 1          | Soft-delete (no se puede desactivar si tiene PCs) |
| creado_en       | DATETIME default NOW          |                                  |

### `aulas_info`

Info contextual del aula (1:1 con aulas).

| Campo           | Tipo                          | Notas                                             |
|-----------------|-------------------------------|---------------------------------------------------|
| id_aula         | VARCHAR(10) PK / FK → aulas   | ON DELETE CASCADE                                 |
| access_point    | VARCHAR(50) NULL              | Código del AP (ej: `RC. F19`)                     |
| passwords       | JSON NULL                     | `{"alumno":"...","cfp":"...","admin":null}`       |
| software        | JSON NULL                     | `{"instalados":["..."],"no_instalados":["..."]}`  |
| actualizado_en  | DATETIME ON UPDATE CURRENT    | Auto-timestamp                                    |

Separada de `aulas` para no mezclar datos operativos (capacidad, piso) con contextuales (passwords).

### `computadoras`

PCs del parque informático.

| Campo           | Tipo                          | Notas                                             |
|-----------------|-------------------------------|---------------------------------------------------|
| id              | VARCHAR(20) PK                | Ej: `'4-12'`, `'6A-PROF'`                         |
| id_aula         | VARCHAR(10) FK → aulas        |                                                   |
| nombre          | VARCHAR(50) NULL              | Ej: `'PC 12'`, `'PC Profesor'`                    |
| estado          | ENUM(5) default 'OPERATIVA'   | Ver abajo                                         |
| ip              | VARCHAR(45) NULL              | IPv4 o IPv6                                       |
| mac             | VARCHAR(17) NULL              |                                                   |
| observaciones   | TEXT NULL                     | Rack, notas libres                                |
| specs           | JSON NULL                     | `{cpu, ram, os, placa, serial, discos[], ...}`    |
| creado_en       | DATETIME default NOW          |                                                   |

**Estados posibles** de la PC:
- `OPERATIVA` (verde): funcionando normalmente.
- `MANTENIMIENTO` (amarillo): pendiente o en trabajo menor (falta componente, upgrade).
- `FUERA_SERVICIO` (rojo): no usable, ticket crítico o alta prioridad.
- `HIBERNANDO` (gris): ping fallido fuera de horario (seteado por heartbeat).
- `ALERTA` (cyan): pingeable pero fuera de horario, posible intrusión.

**Índices**: `(id_aula, estado)` para filtros del dashboard.

### `tickets`

Reportes de incidencias.

| Campo            | Tipo                                              | Notas                                 |
|------------------|---------------------------------------------------|---------------------------------------|
| id               | INT UNSIGNED AUTO_INCREMENT PK                    |                                       |
| id_pc            | VARCHAR(20) FK → computadoras                     |                                       |
| id_usuario       | INT UNSIGNED FK → usuarios                        | Reporter                              |
| tipo             | ENUM('HARDWARE','SOFTWARE','RED','PERIFERICO','OTRO') |                                   |
| prioridad        | ENUM('BAJA','MEDIA','ALTA','CRITICA')             |                                       |
| descripcion      | TEXT                                              |                                       |
| estado           | ENUM('ABIERTO','EN_PROGRESO','RESUELTO','CERRADO') default 'ABIERTO' |                    |
| nota_resolucion  | TEXT NULL                                         |                                       |
| resuelto_por     | INT UNSIGNED FK → usuarios NULL                   |                                       |
| creado_en        | DATETIME default NOW                              |                                       |
| cerrado_en       | DATETIME NULL                                     | Seteado al pasar a RESUELTO/CERRADO   |

**Índices**:
- `(estado, creado_en DESC)` — lista de tickets ordenados por fecha.
- `(id_pc, creado_en)` — historial por PC.
- `(prioridad, estado)` — dashboard de críticos.

### `log_acciones`

Auditoría.

| Campo         | Tipo                          | Notas                              |
|---------------|-------------------------------|------------------------------------|
| id            | INT UNSIGNED AUTO_INCREMENT PK |                                   |
| id_usuario    | INT UNSIGNED FK → usuarios NULL| NULL para acciones del sistema    |
| accion        | VARCHAR(50)                   | Código (ver abajo)                 |
| detalle       | TEXT NULL                     | JSON con contexto                  |
| ip            | VARCHAR(45) NULL              |                                    |
| creado_en     | DATETIME default NOW          |                                    |

**Códigos de acción usados**:

| Código                      | Disparador                                    |
|-----------------------------|-----------------------------------------------|
| `LOGIN_EXITOSO`             | POST /auth.php con credenciales válidas       |
| `LOGIN_FALLIDO`             | POST /auth.php con credenciales inválidas     |
| `LOGIN_USUARIO_INACTIVO`    | POST /auth.php con usuario desactivado        |
| `LOGOUT`                    | DELETE /auth.php                              |
| `TICKET_CREADO`             | POST /tickets.php                             |
| `TICKET_ACTUALIZADO`        | PATCH /tickets.php                            |
| `CAMBIO_ESTADO_PC`          | Automático al crear/resolver ticket           |
| `AULA_CREADA`               | POST /aulas.php                               |
| `AULA_ACTUALIZADA`          | PUT /aulas.php                                |
| `AULA_DESACTIVADA`          | DELETE /aulas.php                             |
| `AULA_INFO_ACTUALIZADA`     | PUT /aulas_info.php                           |
| `PC_CREADA`                 | POST /computers.php                           |
| `PC_ACTUALIZADA`            | PUT /computers.php                            |
| `PC_ELIMINADA`              | DELETE /computers.php                         |
| `USUARIO_CREADO`            | POST /usuarios.php                            |
| `USUARIO_ACTUALIZADO`       | PUT /usuarios.php                             |
| `USUARIO_DESACTIVADO`       | DELETE /usuarios.php                          |
| `HEARTBEAT_CAMBIO_ESTADO`   | bin/heartbeat.php al detectar cambio          |

### `notificaciones`

Notificaciones in-app.

| Campo                | Tipo                          | Notas                                |
|----------------------|-------------------------------|--------------------------------------|
| id                   | BIGINT UNSIGNED AUTO_INCREMENT PK |                                  |
| id_usuario_destino   | INT UNSIGNED FK → usuarios    | ON DELETE CASCADE                    |
| tipo                 | ENUM('TICKET_CREADO','TICKET_CRITICO','TICKET_RESUELTO','SISTEMA') |       |
| titulo               | VARCHAR(150)                  |                                      |
| mensaje              | VARCHAR(500)                  |                                      |
| entidad_tipo         | VARCHAR(20) NULL              | `'ticket'` / `'pc'` / `null`         |
| entidad_id           | VARCHAR(50) NULL              | ID del recurso referenciado          |
| leida                | TINYINT(1) default 0          |                                      |
| creada_en            | DATETIME default NOW          |                                      |
| leida_en             | DATETIME NULL                 |                                      |

**Índices**:
- `(id_usuario_destino, leida, creada_en DESC)` — query principal del dropdown.
- `(leida, leida_en)` — para el evento de limpieza.

**Evento**: `ev_limpiar_notificaciones_viejas` corre diariamente a las 3 AM y borra notificaciones leídas >30 días.

## Migraciones

El esquema se construye incrementalmente. Orden de ejecución:

| # | Archivo | Qué hace |
|---|---|---|
| — | `database_v2.sql` | Schema base: usuarios, aulas, computadoras, tickets, log_acciones + seed de usuarios y datos mínimos. |
| 001 | `migrations/001_add_specs_pc.sql` | Agrega columna `specs JSON` a computadoras. |
| 002 | `migrations/002_add_indexes.sql` | 8 índices via stored procedure idempotente con `information_schema` check. |
| 003 | `migrations/003_notificaciones.sql` | Tabla `notificaciones` + evento de limpieza + `SET GLOBAL event_scheduler = ON`. |
| 004 | `migrations/004_seed_aula_6A.sql` | Seed Aula 6° A (16 PCs Dell) desde CSV. |
| 005 | `migrations/005_seed_aulas_4_5_6B.sql` | Seed aulas 4 (29), 5 (21) y 6B (11) desde CSVs. |
| 006 | `migrations/006_seed_tickets_iniciales.sql` | 11 tickets abiertos para PCs en mantenimiento/fuera_servicio del seed. |
| 007 | `migrations/007_aulas_info.sql` | Tabla `aulas_info` con FK 1:1. |
| 008 | `migrations/008_seed_aulas_info.sql` | Seed de info (AP, passwords, software) de las 4 aulas. |
| — | `migrations/reset_aulas_pcs.sql` | Wipe de aulas + computadoras + tickets (NO toca usuarios ni audit). Útil para re-seed. |

## Convenciones

- **IDs naturales** donde tiene sentido (aulas usan `'6A'`, PCs usan `'6A-01'`). Más legible que `BIGINT` en reportes y seed data.
- **IDs auto-incrementales** para tablas con alta rotación (tickets, log_acciones, notificaciones).
- **Soft-delete** (`activa` / `activo`) para aulas y usuarios. No se borra hard para preservar integridad de logs.
- **Hard-delete** para PCs (no tienen soft-delete) y tickets (se cierran, no se borran).
- **JSON columns** para datos semi-estructurados (specs, passwords, software): queries se pueden hacer con `JSON_EXTRACT`/`->>`.
- **Timestamps**: todas las tablas tienen `creado_en`. Las que se modifican tienen `actualizado_en` con `ON UPDATE CURRENT_TIMESTAMP`.

## Seed de desarrollo

Correr en orden (después del schema base):

```bash
mysql -u root soportet20_db < migrations/001_add_specs_pc.sql
mysql -u root soportet20_db < migrations/002_add_indexes.sql
mysql -u root soportet20_db < migrations/003_notificaciones.sql
mysql -u root soportet20_db < migrations/004_seed_aula_6A.sql
mysql -u root soportet20_db < migrations/005_seed_aulas_4_5_6B.sql
mysql -u root soportet20_db < migrations/006_seed_tickets_iniciales.sql
mysql -u root soportet20_db < migrations/007_aulas_info.sql
mysql -u root soportet20_db < migrations/008_seed_aulas_info.sql
```

## Re-seed desde cero

```bash
mysql -u root soportet20_db < migrations/reset_aulas_pcs.sql
# luego 004 → 008
```

## Backup

```bash
# Full dump
mysqldump -u root --single-transaction --routines --events soportet20_db > backup_$(date +%F).sql

# Solo datos (sin schema)
mysqldump -u root --no-create-info soportet20_db > data_$(date +%F).sql

# Solo schema
mysqldump -u root --no-data --routines --events soportet20_db > schema_$(date +%F).sql
```

`--events` incluye el event scheduler, `--routines` incluye stored procedures (hay uno en migrations/002).
