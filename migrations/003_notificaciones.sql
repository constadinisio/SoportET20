-- migrations/003_notificaciones.sql
-- Tabla de notificaciones in-app (sin servicios externos).
-- Disparadas desde backend/tickets.php al crear/resolver tickets.

CREATE TABLE IF NOT EXISTS notificaciones (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario_destino INT UNSIGNED NOT NULL,
    tipo              ENUM('TICKET_CREADO','TICKET_CRITICO','TICKET_RESUELTO','SISTEMA') NOT NULL,
    titulo            VARCHAR(150) NOT NULL,
    mensaje           VARCHAR(500) NOT NULL,
    entidad_tipo     VARCHAR(20) DEFAULT NULL,
    entidad_id        VARCHAR(50) DEFAULT NULL,
    leida             TINYINT(1) NOT NULL DEFAULT 0,
    creada_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    leida_en          DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_notif_usuario FOREIGN KEY (id_usuario_destino) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_notif_destino_leida (id_usuario_destino, leida, creada_en DESC),
    INDEX idx_notif_leida_en (leida, leida_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto-limpieza: notificaciones leídas con más de 30 días se borran.
-- Requiere que event_scheduler esté ON (ver abajo).
SET GLOBAL event_scheduler = ON;

DROP EVENT IF EXISTS ev_limpiar_notificaciones_viejas;

CREATE EVENT ev_limpiar_notificaciones_viejas
ON SCHEDULE EVERY 1 DAY
  STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY + INTERVAL 3 HOUR)
DO
  DELETE FROM notificaciones
  WHERE leida = 1
    AND leida_en IS NOT NULL
    AND leida_en < NOW() - INTERVAL 30 DAY;

-- Verificación: listar eventos activos.
-- SHOW EVENTS FROM soportet20_db;
