-- migrations/004_seed_aula_6A.sql
-- Seed del Aula 6° A (6to año) desde el CSV compartido por el usuario.
-- 15 PCs de alumnos + 1 PC profesor, todas Dell con Windows 10 Pro.
-- Todas en estado OPERATIVA (CSV dice "Funciona" para las 15).
-- Re-ejecutable: usa INSERT IGNORE para no explotar si ya existen.
--
-- Datos contextuales que no van en estas tablas (referencia):
--   * Contraseñas:  Alumno=AlumnoET20  |  CFP=CFP  |  Admin=(sin dato)
--   * Access Point: RC. F19
--   * Software común instalado: Android Studio, Visual Studio, Python, XAMPP,
--     NetBeans, Arduino, FileZilla Pro, GitHub, HeidiSQL, MuseHub, Notepad++,
--     Sublime Text, UltiMaker Cura, Node, Office, Cisco Packet, PSeInt, Colab,
--     Geany, Photoshop, Premiere, Audition, Illustrator.

INSERT IGNORE INTO aulas (id, nombre, piso, capacidad_pcs, activa)
VALUES ('6A', 'Aula 6° A', 2, 16, 1);

-- PCs de alumnos (posición en rack entre paréntesis en observaciones) ---------

INSERT IGNORE INTO computadoras (id, id_aula, nombre, estado, ip, mac, observaciones, specs) VALUES
('6A-01',   '6A', 'PC 1',  'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os', 'Windows 10 Pro', 'nota', 'Cuenta con GPU, RAM y componentes esenciales')),

('6A-02',   '6A', 'PC 2',  'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os', 'Windows 10 Pro', 'nota', 'Cuenta con GPU, RAM y componentes esenciales')),

('6A-03',   '6A', 'PC 3',  'OPERATIVA', NULL, NULL, 'Rack: E3',
  JSON_OBJECT('os', 'Windows 10 Pro', 'nota', 'Cuenta con GPU, RAM y componentes esenciales')),

('6A-04',   '6A', 'PC 4',  'OPERATIVA', NULL, NULL, 'Rack: E4',
  JSON_OBJECT('os', 'Windows 10 Pro', 'nota', 'Cuenta con GPU, RAM y componentes esenciales')),

('6A-05',   '6A', 'PC 5',  'OPERATIVA', NULL, NULL, 'Rack: E12',
  JSON_OBJECT('os', 'Windows 10 Pro', 'nota', 'Cuenta con GPU, RAM y componentes esenciales')),

('6A-06',   '6A', 'PC 6',  'OPERATIVA', NULL, NULL, 'Rack: E11',
  JSON_OBJECT('os', 'Windows 10 Pro', 'nota', 'Cuenta con GPU, RAM y componentes esenciales')),

('6A-07',   '6A', 'PC 7',  'OPERATIVA', NULL, NULL, 'Rack: D22',
  JSON_OBJECT('os', 'Windows 10 Pro', 'nota', 'Cuenta con GPU, RAM y componentes esenciales')),

('6A-08',   '6A', 'PC 8',  'OPERATIVA', NULL, NULL, 'Rack: D21',
  JSON_OBJECT('os', 'Windows 10 Pro', 'nota', 'Cuenta con GPU, RAM y componentes esenciales')),

('6A-09',   '6A', 'PC 9',  'OPERATIVA', NULL, NULL, 'Rack: E14',
  JSON_OBJECT('os', 'Windows 10 Pro', 'nota', 'Cuenta con GPU, RAM y componentes esenciales')),

('6A-10',   '6A', 'PC 10', 'OPERATIVA', NULL, NULL, 'Rack: E13',
  JSON_OBJECT('os', 'Windows 10 Pro', 'nota', 'Cuenta con GPU, RAM y componentes esenciales')),

('6A-11',   '6A', 'PC 11', 'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os', 'Windows 10 Pro', 'nota', 'Cuenta con GPU, RAM y componentes esenciales')),

('6A-12',   '6A', 'PC 12', 'OPERATIVA', NULL, NULL, 'Rack: E7',
  JSON_OBJECT('os', 'Windows 10 Pro', 'nota', 'Cuenta con GPU, RAM y componentes esenciales')),

('6A-13',   '6A', 'PC 13', 'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os', 'Windows 10 Pro', 'nota', 'Cuenta con GPU, RAM y componentes esenciales')),

('6A-14',   '6A', 'PC 14', 'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os', 'Windows 10 Pro', 'nota', 'Cuenta con GPU, RAM y componentes esenciales')),

('6A-15',   '6A', 'PC 15', 'OPERATIVA', NULL, NULL, 'Rack: sin posición registrada',
  JSON_OBJECT('os', 'Windows 10 Pro', 'nota', 'Cuenta con GPU, RAM y componentes esenciales')),

-- PC del profesor ------------------------------------------------------------
('6A-PROF', '6A', 'PC Profesor', 'OPERATIVA', NULL, NULL, 'PC de escritorio docente',
  JSON_OBJECT('os', 'Windows 10 Pro', 'nota', 'Cuenta con GPU, RAM y componentes esenciales'));

-- Verificación rápida post-ejecución:
--   SELECT id, nombre, estado, observaciones FROM computadoras WHERE id_aula = '6A' ORDER BY id;
