-- migrations/reset_aulas_pcs.sql
-- Wipe para re-seed de aulas + computadoras.
-- ⚠ Borra TAMBIÉN tickets (porque tienen FK a computadoras).
-- NO toca: usuarios, log_acciones, notificaciones.

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE tickets;
TRUNCATE TABLE computadoras;
TRUNCATE TABLE aulas;

SET FOREIGN_KEY_CHECKS = 1;

-- Verificación:
--   SELECT 'aulas' t, COUNT(*) FROM aulas
--   UNION SELECT 'computadoras', COUNT(*) FROM computadoras
--   UNION SELECT 'tickets',      COUNT(*) FROM tickets;
-- Deberían dar 0, 0, 0.
