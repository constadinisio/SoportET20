<?php
declare(strict_types=1);

// backend/aulas_info.php
// GET ?id_aula=X         → info de un aula (todos los roles; passwords solo ADMIN+TECNICO)
// PUT {id_aula, ...}     → actualiza la info del aula (solo ADMIN)

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/session_check.php';

header('Content-Type: application/json');

$method   = $_SERVER['REQUEST_METHOD'];
$rolActual = $_SESSION['rol'] ?? '';
$puedeVerPasswords = in_array($rolActual, ['ADMIN', 'TECNICO'], true);

// --- GET ---
if ($method === 'GET') {
    $idAula = trim((string)($_GET['id_aula'] ?? ''));
    if ($idAula === '') {
        jsonResponse(['error' => 'Se requiere id_aula.'], 400);
    }

    $stmt = $pdo->prepare(
        'SELECT ai.id_aula, a.nombre AS aula_nombre, ai.access_point, ai.passwords, ai.software, ai.actualizado_en
         FROM aulas a
         LEFT JOIN aulas_info ai ON ai.id_aula = a.id
         WHERE a.id = ?'
    );
    $stmt->execute([$idAula]);
    $row = $stmt->fetch();

    if (!$row) {
        jsonResponse(['error' => 'Aula no encontrada.'], 404);
    }

    $passwords = $row['passwords'] ? json_decode($row['passwords'], true) : null;
    $software  = $row['software']  ? json_decode($row['software'],  true) : null;

    jsonResponse([
        'id_aula'        => $row['id_aula'] ?? $idAula,
        'aula_nombre'    => $row['aula_nombre'],
        'access_point'   => $row['access_point'],
        'passwords'      => $puedeVerPasswords ? $passwords : null,
        'passwords_ocultas' => !$puedeVerPasswords && $passwords !== null,
        'software'       => $software,
        'actualizado_en' => $row['actualizado_en'],
    ]);
}

// --- PUT (solo ADMIN) ---
if ($method === 'PUT') {
    requiereAdmin();

    $input = leerJsonInput();
    $idAula = trim((string)($input['id_aula'] ?? ''));
    if ($idAula === '') {
        jsonResponse(['success' => false, 'message' => 'Se requiere id_aula.'], 400);
    }

    // Validar que el aula existe
    $stmt = $pdo->prepare('SELECT id FROM aulas WHERE id = ?');
    $stmt->execute([$idAula]);
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Aula no encontrada.'], 404);
    }

    $accessPoint = isset($input['access_point']) ? trim((string)$input['access_point']) : null;
    if ($accessPoint === '') {
        $accessPoint = null;
    }

    $passwords = isset($input['passwords']) && is_array($input['passwords'])
        ? json_encode($input['passwords'], JSON_UNESCAPED_UNICODE)
        : null;

    $software = isset($input['software']) && is_array($input['software'])
        ? json_encode($input['software'], JSON_UNESCAPED_UNICODE)
        : null;

    $stmt = $pdo->prepare(
        'INSERT INTO aulas_info (id_aula, access_point, passwords, software)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            access_point = VALUES(access_point),
            passwords    = VALUES(passwords),
            software     = VALUES(software)'
    );
    $stmt->execute([$idAula, $accessPoint, $passwords, $software]);

    registrarAccion(
        $pdo,
        (int)$_SESSION['user_id'],
        'AULA_INFO_ACTUALIZADA',
        json_encode(['id_aula' => $idAula])
    );

    jsonResponse(['success' => true, 'message' => 'Info del aula actualizada.']);
}

jsonResponse(['error' => 'Método no permitido.'], 405);
