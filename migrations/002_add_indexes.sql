-- =============================================================================
-- Migración: 002_add_indexes.sql
-- Proyecto:  EduMonitor-ET20 (Escuela Técnica N°20 D.E. 20)
-- Fecha:     2026-04-13
-- Propósito: Agregar índices secundarios para acelerar las consultas más
--            frecuentes de la aplicación PHP (listados de tickets, grilla
--            de PCs por aula, filtros de administración de usuarios).
--
-- Principios aplicados:
--   * Cada índice responde a un patrón de consulta REAL documentado en el
--     código PHP (ver comentarios "Justificación").
--   * Se evitan índices redundantes con PRIMARY KEY o UNIQUE existentes.
--   * Se priorizan índices compuestos cuando la consulta filtra y ordena
--     por columnas diferentes (regla: columnas de igualdad primero, de rango
--     o de ordenamiento al final).
--   * No se tocan índices de log_acciones (ya tiene idx_log_usuario e
--     idx_log_fecha creados en database_v2.sql).
--   * La migración es idempotente: cada ALTER TABLE ADD INDEX se envuelve
--     en un procedimiento que consulta information_schema.STATISTICS, de
--     modo que re-ejecutar este archivo no produce errores "Duplicate key
--     name". Esto permite compatibilidad con MySQL 5.7+ (no depende de
--     CREATE INDEX IF NOT EXISTS, disponible solo desde 8.0.29).
--
-- Reversión: ver bloque "ROLLBACK" al final del archivo (comentado).
-- =============================================================================

USE soportet20_db;

-- -----------------------------------------------------------------------------
-- Helper: procedimiento reutilizable para agregar un índice solo si no existe.
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_add_index_if_not_exists;

DELIMITER $$
CREATE PROCEDURE sp_add_index_if_not_exists(
    IN p_table      VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN p_index_def  VARCHAR(255)
)
BEGIN
    DECLARE v_count INT DEFAULT 0;

    SELECT COUNT(*) INTO v_count
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = p_table
      AND INDEX_NAME   = p_index_name;

    IF v_count = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `',
                          p_index_name, '` ', p_index_def);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- =============================================================================
-- TABLA: tickets
-- =============================================================================
-- Esta tabla concentra la mayor parte del tráfico de lectura de la app:
-- panel de tickets activos, historial por PC, dashboards por aula y
-- estadísticas por prioridad.

-- -----------------------------------------------------------------------------
-- Índice: idx_tickets_estado_creado
-- Columnas: (estado, creado_en DESC)
-- Justificación (patrón #1 y #3):
--   Consulta caliente del panel principal y de computers.php:
--     SELECT ... FROM tickets
--     WHERE estado IN ('ABIERTO','EN_PROGRESO')
--     ORDER BY creado_en DESC;
--   El índice compuesto permite que MySQL:
--     (a) use "Index Range Scan" sobre estado con el IN(...),
--     (b) lea las filas ya ordenadas por creado_en, evitando "filesort".
--   NOTA: MySQL almacena BTREE en orden ascendente; para ORDER BY ... DESC
--   recorre el índice al revés (backward index scan) sin costo extra.
-- -----------------------------------------------------------------------------
CALL sp_add_index_if_not_exists(
    'tickets',
    'idx_tickets_estado_creado',
    '(estado, creado_en)'
);

-- -----------------------------------------------------------------------------
-- Índice: idx_tickets_pc_creado
-- Columnas: (id_pc, creado_en)
-- Justificación (patrón #1 - detalle de PC):
--   En computers.php (detalle de una PC) se listan los tickets históricos
--   del equipo ordenados por fecha:
--     SELECT ... FROM tickets
--     WHERE id_pc = ?
--     ORDER BY creado_en DESC;
--   Sin este índice, MySQL usaría el FK sobre id_pc (que ya existe implícito)
--   pero tendría que ordenar en memoria. El compuesto elimina el filesort
--   y sirve también para COUNT(*) de tickets por PC.
--   Reemplaza ventajosamente al índice auto-creado por la FK de id_pc.
-- -----------------------------------------------------------------------------
CALL sp_add_index_if_not_exists(
    'tickets',
    'idx_tickets_pc_creado',
    '(id_pc, creado_en)'
);

-- -----------------------------------------------------------------------------
-- Índice: idx_tickets_prioridad_estado
-- Columnas: (prioridad, estado)
-- Justificación (patrón #2):
--   Filtros combinados frecuentes en la vista de administración:
--     SELECT ... FROM tickets
--     WHERE prioridad = 'ALTA' AND estado IN ('ABIERTO','EN_PROGRESO');
--   También sirve al dashboard "tickets urgentes abiertos". La columna
--   prioridad tiene baja cardinalidad (3-4 valores) pero combinada con
--   estado reduce significativamente el conjunto candidato.
-- -----------------------------------------------------------------------------
CALL sp_add_index_if_not_exists(
    'tickets',
    'idx_tickets_prioridad_estado',
    '(prioridad, estado)'
);

-- -----------------------------------------------------------------------------
-- Índice: idx_tickets_creado_en
-- Columnas: (creado_en)
-- Justificación (patrón #2 y #3):
--   Consultas por rango de fechas sin otros filtros (reportes mensuales):
--     SELECT ... FROM tickets
--     WHERE creado_en BETWEEN ? AND ?
--     ORDER BY creado_en DESC;
--   Aunque idx_tickets_estado_creado cubre creado_en, NO puede usarse si la
--   consulta no filtra por estado (prefijo izquierdo obligatorio en BTREE).
--   Este índice simple cubre ese caso y además acelera ORDER BY global.
-- -----------------------------------------------------------------------------
CALL sp_add_index_if_not_exists(
    'tickets',
    'idx_tickets_creado_en',
    '(creado_en)'
);

-- -----------------------------------------------------------------------------
-- Índice: idx_tickets_resuelto_por
-- Columnas: (resuelto_por)
-- Justificación:
--   Listado "tickets resueltos por técnico X" en el panel de métricas:
--     SELECT ... FROM tickets WHERE resuelto_por = ? AND estado = 'RESUELTO';
--   La columna es NULLABLE (muchos NULLs en tickets abiertos). MySQL
--   indexa NULLs en BTREE, pero solo las filas con valor no nulo son
--   consultadas, así que el índice es compacto. También evita full scans
--   al generar el ranking de técnicos.
-- -----------------------------------------------------------------------------
CALL sp_add_index_if_not_exists(
    'tickets',
    'idx_tickets_resuelto_por',
    '(resuelto_por)'
);

-- NOTA: NO se crea índice adicional sobre id_usuario porque la FK que lo
-- referencia ya crea automáticamente un índice secundario sobre esa columna
-- (InnoDB requiere índice en columnas FK). Lo mismo aplicaría a id_pc si no
-- fuera porque idx_tickets_pc_creado lo reemplaza con mejor cobertura.

-- =============================================================================
-- TABLA: computadoras
-- =============================================================================
-- Tabla consultada intensamente por home.html (grilla de PCs por aula)
-- y por los endpoints de inventario.

-- -----------------------------------------------------------------------------
-- Índice: idx_computadoras_aula_estado
-- Columnas: (id_aula, estado)
-- Justificación (patrones #4 y #5):
--   La grilla principal home.html agrupa PCs por aula y colorea según estado:
--     SELECT estado, COUNT(*) FROM computadoras
--     WHERE id_aula = ? GROUP BY estado;
--   Además:
--     SELECT ... FROM computadoras WHERE id_aula = ? AND estado = 'OPERATIVA';
--   El compuesto (id_aula, estado) sirve como covering index para estos
--   GROUP BY y filtros, y reemplaza al índice auto-creado por la FK id_aula
--   con una estructura más útil.
--   REGLA: id_aula va primero porque es la columna de igualdad con mayor
--   cardinalidad (muchas aulas) y la que filtra en cada request.
-- -----------------------------------------------------------------------------
CALL sp_add_index_if_not_exists(
    'computadoras',
    'idx_computadoras_aula_estado',
    '(id_aula, estado)'
);

-- -----------------------------------------------------------------------------
-- Índice: idx_computadoras_estado
-- Columnas: (estado)
-- Justificación (patrón #5 - agregados globales):
--   Dashboard institucional:
--     SELECT estado, COUNT(*) FROM computadoras GROUP BY estado;
--     SELECT COUNT(*) FROM computadoras WHERE estado = 'EN_REPARACION';
--   Sin filtro por aula, idx_computadoras_aula_estado no es aplicable
--   (prefijo izquierdo). La columna estado es ENUM de 5 valores, así que
--   el índice es muy compacto y MySQL puede usarlo para "loose index scan".
-- -----------------------------------------------------------------------------
CALL sp_add_index_if_not_exists(
    'computadoras',
    'idx_computadoras_estado',
    '(estado)'
);

-- NOTA: NO se indexan `ip` ni `mac` porque se consultan raramente y solo
-- desde herramientas de diagnóstico puntuales. Si en el futuro se agrega
-- un buscador por IP, considerar índice UNIQUE sobre mac (sería ideal).

-- =============================================================================
-- TABLA: usuarios
-- =============================================================================
-- Tabla chica (decenas de filas) pero se consulta en cada login y en el
-- panel de administración.

-- -----------------------------------------------------------------------------
-- Índice: idx_usuarios_rol_activo
-- Columnas: (rol, activo)
-- Justificación (patrón #8):
--   Panel de admin y selectores de asignación de tickets:
--     SELECT ... FROM usuarios WHERE rol = 'TECNICO' AND activo = 1
--     ORDER BY nombre_completo;
--   Aunque la tabla es pequeña, este índice permite skip scans en selects
--   de listas desplegables y mejora la semántica del plan de ejecución
--   (MySQL puede decidir "ref" en lugar de full scan).
--   NOTA: El UNIQUE sobre `usuario` ya cubre el patrón #7 (login).
-- -----------------------------------------------------------------------------
CALL sp_add_index_if_not_exists(
    'usuarios',
    'idx_usuarios_rol_activo',
    '(rol, activo)'
);

-- NOTA: NO se crea índice sobre (activo) solo, porque con una tabla tan
-- pequeña MySQL preferirá un full scan de todos modos, y el índice agregaría
-- costo de escritura sin beneficio medible.

-- =============================================================================
-- TABLA: aulas
-- =============================================================================
-- Catálogo muy chico (<50 filas). No se agregan índices: cualquier filtro
-- lo resuelve MySQL con un full scan más rápido que con lookup por índice.
-- El PRIMARY KEY sobre `id` es suficiente para los JOINs con computadoras.

-- =============================================================================
-- TABLA: log_acciones
-- =============================================================================
-- Excluida por requerimiento explícito: ya cuenta con idx_log_usuario e
-- idx_log_fecha definidos en database_v2.sql. Para el panel administrativo
-- futuro (patrón #6) evaluar, en una migración posterior, un índice
-- compuesto (accion, creado_en) si se agrega filtro por tipo de acción.

-- =============================================================================
-- Limpieza: eliminar el procedimiento helper (no se necesita en runtime)
-- =============================================================================
DROP PROCEDURE IF EXISTS sp_add_index_if_not_exists;

-- =============================================================================
-- VERIFICACIÓN CON EXPLAIN
-- =============================================================================
-- Ejecutar estas consultas manualmente tras aplicar la migración y confirmar
-- que la columna "key" del resultado muestra el índice esperado y que
-- "Extra" NO contiene "Using filesort" ni "Using temporary" en los casos
-- marcados. Idealmente usar EXPLAIN FORMAT=JSON para ver cost y rows_examined.
--
-- -- Patrón #1 y #3: tickets activos ordenados por fecha
-- -- Debe usar: idx_tickets_estado_creado  |  Extra: Using index condition, Backward index scan
-- EXPLAIN SELECT id, id_pc, prioridad, descripcion, creado_en
--   FROM tickets
--   WHERE estado IN ('ABIERTO','EN_PROGRESO')
--   ORDER BY creado_en DESC
--   LIMIT 50;
--
-- -- Patrón #1 detalle-PC: historial de tickets de una PC
-- -- Debe usar: idx_tickets_pc_creado  |  Extra sin "Using filesort"
-- EXPLAIN SELECT id, estado, prioridad, creado_en
--   FROM tickets
--   WHERE id_pc = 'PC-A101-01'
--   ORDER BY creado_en DESC;
--
-- -- Patrón #2: combinación prioridad + estado
-- -- Debe usar: idx_tickets_prioridad_estado
-- EXPLAIN SELECT COUNT(*) FROM tickets
--   WHERE prioridad = 'ALTA' AND estado IN ('ABIERTO','EN_PROGRESO');
--
-- -- Patrón #2/#3: rango de fechas sin filtro de estado
-- -- Debe usar: idx_tickets_creado_en
-- EXPLAIN SELECT id, estado, prioridad FROM tickets
--   WHERE creado_en BETWEEN '2026-04-01' AND '2026-04-30'
--   ORDER BY creado_en DESC;
--
-- -- Ranking de técnicos resolutores
-- -- Debe usar: idx_tickets_resuelto_por
-- EXPLAIN SELECT resuelto_por, COUNT(*) FROM tickets
--   WHERE resuelto_por IS NOT NULL AND estado = 'RESUELTO'
--   GROUP BY resuelto_por;
--
-- -- Patrón #4: PCs por aula (grilla home)
-- -- Debe usar: idx_computadoras_aula_estado
-- EXPLAIN SELECT id, nombre, estado FROM computadoras
--   WHERE id_aula = 'A101';
--
-- -- Patrón #5: agregado por estado en una aula
-- -- Debe usar: idx_computadoras_aula_estado  |  Extra: Using index
-- EXPLAIN SELECT estado, COUNT(*) FROM computadoras
--   WHERE id_aula = 'A101'
--   GROUP BY estado;
--
-- -- Patrón #5 global: conteo por estado en toda la escuela
-- -- Debe usar: idx_computadoras_estado  |  Extra: Using index
-- EXPLAIN SELECT estado, COUNT(*) FROM computadoras
--   GROUP BY estado;
--
-- -- Patrón #8: usuarios activos por rol
-- -- Debe usar: idx_usuarios_rol_activo
-- EXPLAIN SELECT id, usuario, nombre_completo FROM usuarios
--   WHERE rol = 'TECNICO' AND activo = 1;
--
-- -- Para ver tamaño real de cada índice (útil tras estabilizar datos):
-- -- SELECT TABLE_NAME, INDEX_NAME,
-- --        ROUND(STAT_VALUE * @@innodb_page_size / 1024 / 1024, 2) AS size_mb
-- --   FROM mysql.innodb_index_stats
-- --   WHERE DATABASE_NAME = 'soportet20_db' AND STAT_NAME = 'size'
-- --   ORDER BY STAT_VALUE DESC;

-- =============================================================================
-- ROLLBACK (ejecutar manualmente si es necesario revertir)
-- =============================================================================
-- ALTER TABLE tickets      DROP INDEX idx_tickets_estado_creado;
-- ALTER TABLE tickets      DROP INDEX idx_tickets_pc_creado;
-- ALTER TABLE tickets      DROP INDEX idx_tickets_prioridad_estado;
-- ALTER TABLE tickets      DROP INDEX idx_tickets_creado_en;
-- ALTER TABLE tickets      DROP INDEX idx_tickets_resuelto_por;
-- ALTER TABLE computadoras DROP INDEX idx_computadoras_aula_estado;
-- ALTER TABLE computadoras DROP INDEX idx_computadoras_estado;
-- ALTER TABLE usuarios     DROP INDEX idx_usuarios_rol_activo;

-- =============================================================================
-- FIN 002_add_indexes.sql
-- =============================================================================
