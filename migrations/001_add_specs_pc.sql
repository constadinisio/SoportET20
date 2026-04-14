-- ============================================================
-- Migration 001: Agregar columna specs JSON a computadoras
-- Fecha: 2026-04-13
-- ============================================================

USE soportet20_db;

-- Solo agregar si no existe
ALTER TABLE computadoras
    ADD COLUMN specs JSON NULL COMMENT 'Especificaciones técnicas: cpu, ram, discos, os'
    AFTER observaciones;

-- ============================================================
-- Datos de ejemplo (PCs del aula 101)
-- ============================================================
UPDATE computadoras SET specs = JSON_OBJECT(
    'cpu', 'Intel Core i5-10400 @ 2.90GHz',
    'ram', '8 GB DDR4',
    'discos', JSON_ARRAY(
        JSON_OBJECT('tipo', 'SSD', 'capacidad', '240 GB', 'modelo', 'Kingston A400'),
        JSON_OBJECT('tipo', 'HDD', 'capacidad', '1 TB', 'modelo', 'WD Blue')
    ),
    'os', 'Windows 10 Pro 22H2',
    'placa', 'ASUS H410M-E'
) WHERE id IN ('101-01', '101-02', '101-03', '101-04');

UPDATE computadoras SET specs = JSON_OBJECT(
    'cpu', 'Intel Core i3-9100 @ 3.60GHz',
    'ram', '4 GB DDR4',
    'discos', JSON_ARRAY(
        JSON_OBJECT('tipo', 'HDD', 'capacidad', '500 GB', 'modelo', 'Seagate Barracuda')
    ),
    'os', 'Windows 10 Pro 21H2',
    'placa', 'Gigabyte H310M'
) WHERE id IN ('101-05', '101-06', '101-07', '101-08');

UPDATE computadoras SET specs = JSON_OBJECT(
    'cpu', 'AMD Ryzen 3 3200G',
    'ram', '16 GB DDR4',
    'discos', JSON_ARRAY(
        JSON_OBJECT('tipo', 'NVMe', 'capacidad', '500 GB', 'modelo', 'Samsung 980')
    ),
    'os', 'Windows 11 Pro',
    'placa', 'MSI A320M-A PRO'
) WHERE id IN ('101-09', '101-10', '101-11', '101-12');

UPDATE computadoras SET specs = JSON_OBJECT(
    'cpu', 'Intel Core i7-11700',
    'ram', '16 GB DDR4',
    'discos', JSON_ARRAY(
        JSON_OBJECT('tipo', 'NVMe', 'capacidad', '1 TB', 'modelo', 'Kingston NV2')
    ),
    'os', 'Windows 11 Pro',
    'placa', 'ASUS PRIME B560M-A'
) WHERE id LIKE '104-%';
