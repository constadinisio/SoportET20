-- migrations/006_seed_tickets_iniciales.sql
-- Crea tickets abiertos para todas las PCs que están en MANTENIMIENTO o FUERA_SERVICIO,
-- respetando la misma lógica de tickets.php:
--   FUERA_SERVICIO → prioridad ALTA
--   MANTENIMIENTO  → prioridad MEDIA
--
-- ⚠ Ejecutar DESPUÉS de reset_aulas_pcs.sql + 004 + 005.
-- Re-ejecutar sin reset previo crea duplicados (tickets tiene AUTO_INCREMENT).
--
-- Reporter: se asume que todos los tickets los abrió "admin" (seed inicial).
-- Cuando reportes nuevos desde la web, ahí queda el usuario real.

SET @reporter_id = (SELECT id FROM usuarios WHERE usuario = 'admin' LIMIT 1);

INSERT INTO tickets (id_pc, id_usuario, tipo, prioridad, descripcion, estado) VALUES
-- ── Aula 4 (9 mantenimientos + 1 fuera de servicio) ────────────────────────
('4-02',  @reporter_id, 'HARDWARE', 'MEDIA',
  'Falta tarjeta gráfica. La PC funciona pero opera con gráficos integrados.', 'ABIERTO'),

('4-05',  @reporter_id, 'HARDWARE', 'MEDIA',
  'Falta tarjeta gráfica. La PC funciona pero opera con gráficos integrados.', 'ABIERTO'),

('4-06',  @reporter_id, 'HARDWARE', 'MEDIA',
  'Falta tarjeta gráfica. La PC funciona pero opera con gráficos integrados.', 'ABIERTO'),

('4-11',  @reporter_id, 'HARDWARE', 'MEDIA',
  'Falta tarjeta gráfica. La PC funciona pero opera con gráficos integrados.', 'ABIERTO'),

('4-12',  @reporter_id, 'HARDWARE', 'ALTA',
  'No bootea — revisar disco. Además le falta la tarjeta gráfica. PC fuera de servicio.', 'ABIERTO'),

('4-15',  @reporter_id, 'SOFTWARE', 'BAJA',
  'Migrar sistema operativo a Windows 10 Pro (el resto del aula ya está en esa versión).', 'ABIERTO'),

('4-16',  @reporter_id, 'SOFTWARE', 'BAJA',
  'Migrar sistema operativo a Windows 10 Pro (el resto del aula ya está en esa versión).', 'ABIERTO'),

('4-19',  @reporter_id, 'HARDWARE', 'MEDIA',
  'Falta tarjeta gráfica. La PC funciona pero opera con gráficos integrados.', 'ABIERTO'),

('4-20',  @reporter_id, 'HARDWARE', 'MEDIA',
  'Falta tarjeta gráfica. La PC funciona pero opera con gráficos integrados.', 'ABIERTO'),

('4-27',  @reporter_id, 'HARDWARE', 'MEDIA',
  'Falta tarjeta gráfica. La PC funciona pero opera con gráficos integrados.', 'ABIERTO'),

-- ── Aula 6 B (1 mantenimiento) ─────────────────────────────────────────────
('6B-03', @reporter_id, 'HARDWARE', 'MEDIA',
  'No da imagen. Está siendo revisada.', 'ABIERTO');

-- Verificación:
--   SELECT t.id, t.id_pc, t.tipo, t.prioridad, t.estado, c.estado AS estado_pc
--   FROM tickets t JOIN computadoras c ON c.id = t.id_pc
--   ORDER BY t.id_pc;
