<?php
declare(strict_types=1);

// backend/notificaciones.php - Notificaciones in-app del usuario autenticado.
// GET                 → lista últimas 20 propias (más 10 leídas para contexto)
// GET ?count=1        → solo cantidad de no leídas (ligero, para polling)
// PATCH {id}          → marcar una como leída
// POST {accion:'todas'} → marcar todas propias como leídas

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/session_check.php';

header('Content-Type: application/json');

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    jsonResponse(['error' => 'No autorizado.'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

// --- GET ---
if ($method === 'GET') {
    if (!empty($_GET['count'])) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notificaciones WHERE id_usuario_destino = ? AND leida = 0');
        $stmt->execute([$userId]);
        jsonResponse(['count' => (int)$stmt->fetchColumn()]);
    }

    $stmt = $pdo->prepare(
        'SELECT id, tipo, titulo, mensaje, entidad_tipo, entidad_id, leida, creada_en, leida_en
         FROM notificaciones
         WHERE id_usuario_destino = ?
         ORDER BY leida ASC, creada_en DESC
         LIMIT 30'
    );
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    $stmtCount = $pdo->prepare('SELECT COUNT(*) FROM notificaciones WHERE id_usuario_destino = ? AND leida = 0');
    $stmtCount->execute([$userId]);

    jsonResponse([
        'no_leidas'       => (int)$stmtCount->fetchColumn(),
        'notificaciones' => $rows,
    ]);
}

// --- PATCH: marcar una como leída ---
if ($method === 'PATCH') {
    $input = leerJsonInput();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Se requiere id.'], 400);
    }

    $stmt = $pdo->prepare(
        'UPDATE notificaciones
         SET leida = 1, leida_en = NOW()
         WHERE id = ? AND id_usuario_destino = ? AND leida = 0'
    );
    $stmt->execute([$id, $userId]);

    jsonResponse(['success' => true, 'updated' => $stmt->rowCount()]);
}

// --- POST: marcar todas como leídas ---
if ($method === 'POST') {
    $input = leerJsonInput();
    if (($input['accion'] ?? '') !== 'todas') {
        jsonResponse(['success' => false, 'message' => 'Acción no válida.'], 400);
    }

    $stmt = $pdo->prepare(
        'UPDATE notificaciones
         SET leida = 1, leida_en = NOW()
         WHERE id_usuario_destino = ? AND leida = 0'
    );
    $stmt->execute([$userId]);

    jsonResponse(['success' => true, 'updated' => $stmt->rowCount()]);
}

jsonResponse(['error' => 'Método no permitido.'], 405);
