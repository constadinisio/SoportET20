-- ============================================================
-- EduMonitor ET20 - Schema v2
-- Base de datos: soportet20_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS soportet20_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE soportet20_db;

-- ============================================================
-- 1. USUARIOS
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL COMMENT 'Hash generado con password_hash()',
    nombre_completo VARCHAR(100) NOT NULL DEFAULT '',
    rol ENUM('ADMIN', 'TECNICO', 'PROFESOR') NOT NULL DEFAULT 'PROFESOR',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 2. AULAS
-- ============================================================
CREATE TABLE IF NOT EXISTS aulas (
    id VARCHAR(10) PRIMARY KEY COMMENT 'Identificador del aula, ej: 101, 102',
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre descriptivo, ej: Laboratorio de Informática 1',
    piso INT UNSIGNED NOT NULL DEFAULT 0,
    capacidad_pcs INT UNSIGNED NOT NULL DEFAULT 0,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 3. COMPUTADORAS
-- ============================================================
-- Estados:
--   OPERATIVA     (Verde)  -> Funcionando correctamente
--   MANTENIMIENTO (Amarillo) -> En revisión preventiva o correctiva
--   FUERA_SERVICIO(Rojo)   -> No funciona, requiere reparación
--   HIBERNANDO    (Gris)   -> Apagada / fuera de uso temporal
--   ALERTA        (Cian)   -> Actividad detectada fuera de horario
-- ============================================================
CREATE TABLE IF NOT EXISTS computadoras (
    id VARCHAR(20) PRIMARY KEY COMMENT 'Formato: AULA-NN, ej: 101-05',
    id_aula VARCHAR(10) NOT NULL,
    nombre VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Nombre descriptivo opcional',
    estado ENUM('OPERATIVA', 'MANTENIMIENTO', 'FUERA_SERVICIO', 'HIBERNANDO', 'ALERTA') NOT NULL DEFAULT 'OPERATIVA',
    ip VARCHAR(45) DEFAULT NULL,
    mac VARCHAR(17) DEFAULT NULL,
    observaciones TEXT DEFAULT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_computadoras_aula FOREIGN KEY (id_aula) REFERENCES aulas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 4. TICKETS
-- ============================================================
CREATE TABLE IF NOT EXISTS tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pc VARCHAR(20) NOT NULL,
    id_usuario INT UNSIGNED NOT NULL COMMENT 'Quien reporta',
    tipo ENUM('HARDWARE', 'SOFTWARE', 'RED', 'PERIFERICO', 'OTRO') NOT NULL DEFAULT 'OTRO',
    prioridad ENUM('BAJA', 'MEDIA', 'ALTA', 'CRITICA') NOT NULL DEFAULT 'MEDIA',
    descripcion TEXT NOT NULL,
    estado ENUM('ABIERTO', 'EN_PROGRESO', 'RESUELTO', 'CERRADO') NOT NULL DEFAULT 'ABIERTO',
    resuelto_por INT UNSIGNED DEFAULT NULL COMMENT 'Técnico que resolvió',
    nota_resolucion TEXT DEFAULT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    cerrado_en TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_tickets_pc FOREIGN KEY (id_pc) REFERENCES computadoras(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_tickets_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_tickets_tecnico FOREIGN KEY (resuelto_por) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 5. LOG DE ACCIONES (Auditoría)
-- ============================================================
CREATE TABLE IF NOT EXISTS log_acciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED DEFAULT NULL,
    accion VARCHAR(255) NOT NULL,
    detalle TEXT DEFAULT NULL COMMENT 'JSON con contexto adicional',
    ip VARCHAR(45) DEFAULT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_log_usuario (id_usuario),
    INDEX idx_log_fecha (creado_en)
) ENGINE=InnoDB;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

-- Usuarios (claves hasheadas con password_hash - bcrypt)
-- admin / admin123  |  tecnico / tecnico123  |  profe / profe123
INSERT INTO usuarios (usuario, clave, nombre_completo, rol) VALUES
    ('admin', '$2y$10$KksPmZwa4hWDlfWPZM3Rou/JSS032lVzZaZmvbZgQuWeq28.FePx6', 'Administrador del Sistema', 'ADMIN'),
    ('tecnico', '$2y$10$ntl5EN5sGtVLWkTR8PsFsed3ybs92VjqVcEWZCVSt5OCf6dFgkZ.y', 'Técnico de Soporte', 'TECNICO'),
    ('profe', '$2y$10$N391q4Dit4LDxrwLWd6FbeZk1EPWWulwrZl0FXt.eLxaC4p4NtF12', 'Profesor de Prueba', 'PROFESOR');

-- Aulas
INSERT INTO aulas (id, nombre, piso, capacidad_pcs) VALUES
    ('101', 'Laboratorio de Informática 1', 1, 12),
    ('102', 'Laboratorio de Informática 2', 1, 12),
    ('103', 'Laboratorio de Redes', 1, 8),
    ('104', 'Taller de Electrónica', 1, 6),
    ('105', 'Aula Multimedia', 1, 10);

-- Computadoras de ejemplo (Aula 101)
INSERT INTO computadoras (id, id_aula, nombre, estado) VALUES
    ('101-01', '101', 'PC Docente', 'OPERATIVA'),
    ('101-02', '101', 'Estación 1', 'OPERATIVA'),
    ('101-03', '101', 'Estación 2', 'FUERA_SERVICIO'),
    ('101-04', '101', 'Estación 3', 'OPERATIVA'),
    ('101-05', '101', 'Estación 4', 'MANTENIMIENTO'),
    ('101-06', '101', 'Estación 5', 'OPERATIVA'),
    ('101-07', '101', 'Estación 6', 'HIBERNANDO'),
    ('101-08', '101', 'Estación 7', 'OPERATIVA'),
    ('101-09', '101', 'Estación 8', 'OPERATIVA'),
    ('101-10', '101', 'Estación 9', 'ALERTA'),
    ('101-11', '101', 'Estación 10', 'OPERATIVA'),
    ('101-12', '101', 'Estación 11', 'OPERATIVA');

-- Computadoras de ejemplo (Aula 104)
INSERT INTO computadoras (id, id_aula, nombre, estado) VALUES
    ('104-01', '104', 'PC Docente', 'OPERATIVA'),
    ('104-02', '104', 'Estación 1', 'OPERATIVA'),
    ('104-03', '104', 'Estación 2', 'OPERATIVA'),
    ('104-04', '104', 'Estación 3', 'FUERA_SERVICIO'),
    ('104-05', '104', 'Estación 4', 'OPERATIVA'),
    ('104-06', '104', 'Estación 5', 'OPERATIVA');

-- Ticket de ejemplo
INSERT INTO tickets (id_pc, id_usuario, tipo, prioridad, descripcion, estado) VALUES
    ('101-03', 1, 'HARDWARE', 'ALTA', 'La PC no enciende. Se presiona el botón de power y no hay respuesta. Sin luces en el gabinete.', 'ABIERTO'),
    ('104-04', 1, 'RED', 'MEDIA', 'No se detecta cable de red. El ícono de red muestra desconectado.', 'ABIERTO');

-- Log inicial
INSERT INTO log_acciones (id_usuario, accion, detalle) VALUES
    (1, 'SISTEMA_INICIALIZADO', '{"version": "2.0", "nota": "Migración desde schema v1"}');
