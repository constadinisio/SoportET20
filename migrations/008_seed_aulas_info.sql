-- migrations/008_seed_aulas_info.sql
-- Seed de aulas_info desde los 4 CSVs del 6to año.
-- Software común a las 4 aulas. En Aula 4, Colab y Geany están marcados como "No".

SET @sw_comun = JSON_ARRAY(
    'Android Studio','Visual Studio','Python','XAMPP','NetBeans','Arduino',
    'FileZilla Pro','GitHub','HeidiSQL','MuseHub','Notepad++','Sublime Text',
    'UltiMaker Cura','Node','Office','Cisco Packet','PSeInt','Colab','Geany',
    'Photoshop','Premiere','Audition','Illustrator'
);

-- Aula 4: Colab y Geany NO instalados
INSERT INTO aulas_info (id_aula, access_point, passwords, software)
VALUES (
    '4',
    'RD. G18',
    JSON_OBJECT('alumno','AlumnoET20','ifts','IFTS','admin',NULL),
    JSON_OBJECT(
        'instalados',    JSON_ARRAY('Android Studio','Visual Studio','Python','XAMPP','NetBeans','Arduino','FileZilla Pro','GitHub','HeidiSQL','MuseHub','Notepad++','Sublime Text','UltiMaker Cura','Node','Office','Cisco Packet','PSeInt','Photoshop','Premiere','Audition','Illustrator'),
        'no_instalados', JSON_ARRAY('Colab','Geany')
    )
)
ON DUPLICATE KEY UPDATE access_point = VALUES(access_point), passwords = VALUES(passwords), software = VALUES(software);

-- Aula 5
INSERT INTO aulas_info (id_aula, access_point, passwords, software)
VALUES (
    '5',
    NULL,
    JSON_OBJECT('alumno','AlumnoET20','cfp','CFP','admin',NULL),
    JSON_OBJECT('instalados', @sw_comun, 'no_instalados', JSON_ARRAY())
)
ON DUPLICATE KEY UPDATE access_point = VALUES(access_point), passwords = VALUES(passwords), software = VALUES(software);

-- Aula 6 A
INSERT INTO aulas_info (id_aula, access_point, passwords, software)
VALUES (
    '6A',
    'RC. F19',
    JSON_OBJECT('alumno','AlumnoET20','cfp','CFP','admin',NULL),
    JSON_OBJECT('instalados', @sw_comun, 'no_instalados', JSON_ARRAY())
)
ON DUPLICATE KEY UPDATE access_point = VALUES(access_point), passwords = VALUES(passwords), software = VALUES(software);

-- Aula 6 B
INSERT INTO aulas_info (id_aula, access_point, passwords, software)
VALUES (
    '6B',
    'RC. F19',
    JSON_OBJECT('alumno','AlumnoET20','cfp','CFP','admin',NULL),
    JSON_OBJECT('instalados', @sw_comun, 'no_instalados', JSON_ARRAY())
)
ON DUPLICATE KEY UPDATE access_point = VALUES(access_point), passwords = VALUES(passwords), software = VALUES(software);
