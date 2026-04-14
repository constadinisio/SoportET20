-- migrations/005_seed_aulas_4_5_6B.sql
-- Seed de Aulas 4, 5 y 6° B (6to año) desde los CSVs compartidos.
-- Complementa 004_seed_aula_6A.sql. Re-ejecutable vía INSERT IGNORE.
--
-- Mapeo estado CSV → ENUM computadoras.estado:
--   "Funciona"                                         → OPERATIVA
--   "Funciona, le falta la gráfica"                    → MANTENIMIENTO  (GPU faltante pero operativa)
--   "Cambiar a windows 10"                             → MANTENIMIENTO  (upgrade pendiente)
--   "No bootea (revisar disco), le falta la gráfica"  → FUERA_SERVICIO
--   "No da imagen (esta siendo revisada)"              → MANTENIMIENTO
--
-- Datos contextuales (NO van en estas tablas, se mostrarán en la web más adelante):
--   AULA 4  — Access Point: RD. G18  |  Passwords: Alumno=AlumnoET20, IFTS=IFTS, Admin=(sin dato)
--   AULA 5  — Access Point: (sin dato en CSV)  |  Passwords: Alumno=AlumnoET20, CFP=CFP, Admin=(sin dato)
--   AULA 6B — Access Point: RC. F19  |  Passwords: Alumno=AlumnoET20, CFP=CFP, Admin=(sin dato)
--   Software común en las 4 aulas (mismo stack): Android Studio, Visual Studio, Python, XAMPP,
--     NetBeans, Arduino, FileZilla Pro, GitHub, HeidiSQL, MuseHub, Notepad++, Sublime Text,
--     UltiMaker Cura, Node, Office, Cisco Packet, PSeInt, Colab, Geany, Photoshop, Premiere,
--     Audition, Illustrator. (En Aula 4: Colab y Geany marcados como "No".)

-- ============================================================================
-- AULA 4 — 28 alumnos + 1 profesor (PC 29)  — Optiplex 7071
-- ============================================================================

INSERT IGNORE INTO aulas (id, nombre, piso, capacidad_pcs, activa)
VALUES ('4', 'Aula 4', 1, 29, 1);

INSERT IGNORE INTO computadoras (id, id_aula, nombre, estado, ip, mac, observaciones, specs) VALUES
('4-01', '4', 'PC 1',  'OPERATIVA',      NULL, NULL, 'Rack: B1',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071')),
('4-02', '4', 'PC 2',  'MANTENIMIENTO',  NULL, NULL, 'Le falta la gráfica. Rack: B2',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','98YFN23','componentes_faltantes', JSON_ARRAY('GPU'))),
('4-03', '4', 'PC 3',  'OPERATIVA',      NULL, NULL, 'Rack: A17',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071')),
('4-04', '4', 'PC 4',  'OPERATIVA',      NULL, NULL, 'Rack: A18',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071')),
('4-05', '4', 'PC 5',  'MANTENIMIENTO',  NULL, NULL, 'Le falta la gráfica. Rack: A9',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','97FFN23','componentes_faltantes', JSON_ARRAY('GPU'))),
('4-06', '4', 'PC 6',  'MANTENIMIENTO',  NULL, NULL, 'Le falta la gráfica. Rack: A10',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','98BNN23','componentes_faltantes', JSON_ARRAY('GPU'))),
('4-07', '4', 'PC 7',  'OPERATIVA',      NULL, NULL, 'Rack: A19',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071')),
('4-08', '4', 'PC 8',  'OPERATIVA',      NULL, NULL, 'Rack: A20',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071')),
('4-09', '4', 'PC 9',  'OPERATIVA',      NULL, NULL, 'Rack: A11',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071')),
('4-10', '4', 'PC 10', 'OPERATIVA',      NULL, NULL, 'Rack: A12',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071')),
('4-11', '4', 'PC 11', 'MANTENIMIENTO',  NULL, NULL, 'Le falta la gráfica. Rack: A3',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','99DNN23','componentes_faltantes', JSON_ARRAY('GPU'))),
('4-12', '4', 'PC 12', 'FUERA_SERVICIO', NULL, NULL, 'No bootea (revisar disco). Le falta la gráfica. Rack: A4',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','98NPN23','componentes_faltantes', JSON_ARRAY('GPU','Disco revisar'))),
('4-13', '4', 'PC 13', 'OPERATIVA',      NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','989BPN23')),
('4-14', '4', 'PC 14', 'OPERATIVA',      NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','98BNH23')),
('4-15', '4', 'PC 15', 'MANTENIMIENTO',  NULL, NULL, 'Cambiar a Windows 10. Rack: sin posición registrada',
  JSON_OBJECT('os','Pendiente Win10','marca','Dell','modelo','Optiplex 7071')),
('4-16', '4', 'PC 16', 'MANTENIMIENTO',  NULL, NULL, 'Cambiar a Windows 10. Rack: sin posición registrada',
  JSON_OBJECT('os','Pendiente Win10','marca','Dell','modelo','Optiplex 7071')),
('4-17', '4', 'PC 17', 'OPERATIVA',      NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071')),
('4-18', '4', 'PC 18', 'OPERATIVA',      NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','99CMN23')),
('4-19', '4', 'PC 19', 'MANTENIMIENTO',  NULL, NULL, 'Le falta la gráfica. Rack: A5',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','97KHN23','componentes_faltantes', JSON_ARRAY('GPU'))),
('4-20', '4', 'PC 20', 'MANTENIMIENTO',  NULL, NULL, 'Le falta la gráfica. Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','989HN23','componentes_faltantes', JSON_ARRAY('GPU'))),
('4-21', '4', 'PC 21', 'OPERATIVA',      NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','97QGN23')),
('4-22', '4', 'PC 22', 'OPERATIVA',      NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','980FN23')),
('4-23', '4', 'PC 23', 'OPERATIVA',      NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','98DMN23')),
('4-24', '4', 'PC 24', 'OPERATIVA',      NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','97WGN23')),
('4-25', '4', 'PC 25', 'OPERATIVA',      NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','97MMN23')),
('4-26', '4', 'PC 26', 'OPERATIVA',      NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','990FN23')),
('4-27', '4', 'PC 27', 'MANTENIMIENTO',  NULL, NULL, 'Le falta la gráfica. Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','98ZLN23','componentes_faltantes', JSON_ARRAY('GPU'))),
('4-28', '4', 'PC 28', 'OPERATIVA',      NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071','serial','97XGN23')),
('4-PROF','4', 'PC Profesor (PC 29)', 'OPERATIVA', NULL, NULL, 'PC del docente',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','Optiplex 7071'));

-- ============================================================================
-- AULA 5 — 21 PCs (sin PC Profesor dedicada)  — OptiPlex 5050
-- ============================================================================

INSERT IGNORE INTO aulas (id, nombre, piso, capacidad_pcs, activa)
VALUES ('5', 'Aula 5', 1, 21, 1);

INSERT IGNORE INTO computadoras (id, id_aula, nombre, estado, ip, mac, observaciones, specs) VALUES
('5-01', '5', 'PC 1',  'OPERATIVA', NULL, NULL, 'Rack: C14',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-02', '5', 'PC 2',  'OPERATIVA', NULL, NULL, 'Rack: C13',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-03', '5', 'PC 3',  'OPERATIVA', NULL, NULL, 'Rack: C6',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-04', '5', 'PC 4',  'OPERATIVA', NULL, NULL, 'Rack: C5',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-05', '5', 'PC 5',  'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-06', '5', 'PC 6',  'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-07', '5', 'PC 7',  'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-08', '5', 'PC 8',  'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-09', '5', 'PC 9',  'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-10', '5', 'PC 10', 'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-11', '5', 'PC 11', 'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-12', '5', 'PC 12', 'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-13', '5', 'PC 13', 'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-14', '5', 'PC 14', 'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-15', '5', 'PC 15', 'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-16', '5', 'PC 16', 'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-17', '5', 'PC 17', 'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-18', '5', 'PC 18', 'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-19', '5', 'PC 19', 'OPERATIVA', NULL, NULL, 'Rack: C4',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-20', '5', 'PC 20', 'OPERATIVA', NULL, NULL, 'Rack: C3',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050')),
('5-21', '5', 'PC 21', 'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','modelo','OptiPlex 5050'));

-- ============================================================================
-- AULA 6 B — 11 PCs  — Dell
-- ============================================================================

INSERT IGNORE INTO aulas (id, nombre, piso, capacidad_pcs, activa)
VALUES ('6B', 'Aula 6° B', 2, 11, 1);

INSERT IGNORE INTO computadoras (id, id_aula, nombre, estado, ip, mac, observaciones, specs) VALUES
('6B-01', '6B', 'PC 1',  'OPERATIVA',     NULL, NULL, 'Rack: E19',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell')),
('6B-02', '6B', 'PC 2',  'OPERATIVA',     NULL, NULL, 'Rack: E20',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell','serial','3KSTHQ2')),
('6B-03', '6B', 'PC 3',  'MANTENIMIENTO', NULL, NULL, 'No da imagen (está siendo revisada). Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell')),
('6B-04', '6B', 'PC 4',  'OPERATIVA',     NULL, NULL, 'Rack: E21',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell')),
('6B-05', '6B', 'PC 5',  'OPERATIVA',     NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell')),
('6B-06', '6B', 'PC 6',  'OPERATIVA',     NULL, NULL, 'Rack: E22',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell')),
('6B-07', '6B', 'PC 7',  'OPERATIVA',     NULL, NULL, 'Rack: E23',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell')),
('6B-08', '6B', 'PC 8',  'OPERATIVA',     NULL, NULL, 'Rack: E24',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell')),
('6B-09', '6B', 'PC 9',  'OPERATIVA',     NULL, NULL, 'Rack: F4',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell')),
('6B-10', '6B', 'PC 10', 'OPERATIVA',     NULL, NULL, 'Rack: F3',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell')),
('6B-11', '6B', 'PC 11', 'OPERATIVA',     NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os','Windows 10 Pro','marca','Dell'));

-- Verificación:
--   SELECT id_aula, estado, COUNT(*) FROM computadoras GROUP BY id_aula, estado ORDER BY id_aula, estado;
