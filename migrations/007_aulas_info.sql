-- migrations/007_aulas_info.sql
-- Info contextual de cada aula (NO va en la tabla aulas para no ensuciar el ABM operativo):
--   access_point: código del AP (ej: "RC. F19", "RD. G18")
--   passwords:    JSON con cuentas por aula {alumno, admin, cfp, ifts, ...}
--   software:     JSON con instalados y no_instalados

CREATE TABLE IF NOT EXISTS aulas_info (
    id_aula       VARCHAR(10) NOT NULL PRIMARY KEY,
    access_point  VARCHAR(50) DEFAULT NULL,
    passwords     JSON DEFAULT NULL,
    software      JSON DEFAULT NULL,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_aulas_info_aula FOREIGN KEY (id_aula) REFERENCES aulas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
