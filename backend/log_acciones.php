<?php
declare(strict_types=1);

// backend/log_acciones.php - Consulta del log de auditoría (solo ADMIN)
// GET ?usuario=X&accion=Y&desde=YYYY-MM-DD&hasta=YYYY-MM-DD&page=1&limit=50

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/session_check.php';

header('Content-Type: application/json');
requiereAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Método no permitido.'], 405);
}

// --- Parámetros de filtrado ---
$idUsuario = isset($_GET['id_usuario']) && $_GET['id_usuario'] !== '' ? (int)$_GET['id_usuario'] : null;
$accion    = isset($_GET['accion']) && $_GET['accion'] !== '' ? trim($_GET['accion']) : null;
$desde     = isset($_GET['desde']) && $_GET['desde'] !== '' ? trim($_GET['desde']) : null;
$hasta     = isset($_GET['hasta']) && $_GET['hasta'] !== '' ? trim($_GET['hasta']) : null;

// Paginación
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = min(200, max(10, (int)($_GET['limit'] ?? 50)));
$offset = ($page - 1) * $limit;

// --- Construir WHERE dinámico ---
$where  = [];
$params = [];

if ($idUsuario !== null) {
    $where[] = 'l.id_usuario = ?';
    $params[] = $idUsuario;
}

if ($accion !== null) {
    // Permitir búsqueda parcial con LIKE
    $where[] = 'l.accion LIKE ?';
    $params[] = '%' . $accion . '%';
}

if ($desde !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
    $where[] = 'l.creado_en >= ?';
    $params[] = $desde . ' 00:00:00';
}

if ($hasta !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
    $where[] = 'l.creado_en <= ?';
    $params[] = $hasta . ' 23:59:59';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// --- Contar total para paginación ---
$sqlCount = "SELECT COUNT(*) FROM log_acciones l {$whereSql}";
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

// --- Consulta principal (con JOIN a usuarios) ---
// LIMIT/OFFSET bindeados como enteros (requiere PDO::ATTR_EMULATE_PREPARES=false, ya configurado en db.php)
$sql = "SELECT l.id, l.id_usuario, l.accion, l.detalle, l.ip, l.creado_en,
               u.usuario, u.nombre_completo, u.rol
        FROM log_acciones l
        LEFT JOIN usuarios u ON u.id = l.id_usuario
        {$whereSql}
        ORDER BY l.creado_en DESC, l.id DESC
        LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);

// Bindear los filtros primero (posicionales, en orden de construcción)
$paramIndex = 1;
foreach ($params as $p) {
    $stmt->bindValue($paramIndex++, $p);
}
// Bindear LIMIT/OFFSET como enteros estrictos
$stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
$stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// Intentar parsear el campo detalle si parece JSON
foreach ($rows as &$r) {
    if (!empty($r['detalle'])) {
        $decoded = json_decode($r['detalle'], true);
        $r['detalle_json'] = is_array($decoded) ? $decoded : null;
    } else {
        $r['detalle_json'] = null;
    }
}

jsonResponse([
    'total'        => $total,
    'page'         => $page,
    'limit'        => $limit,
    'total_pages'  => (int)ceil($total / $limit),
    'logs'         => $rows,
]);
