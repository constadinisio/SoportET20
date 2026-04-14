<?php
declare(strict_types=1);

// backend/aulas.php - CRUD de aulas
// GET     -> lista (cualquier usuario logueado)
// POST    -> crear (solo ADMIN)
// PUT     -> actualizar (solo ADMIN)
// DELETE  -> soft-delete (solo ADMIN)

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/session_check.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// --- GET: Listar aulas (incluye contadores de PCs) ---
if ($method === 'GET') {
    $incluirInactivas = isset($_GET['incluir_inactivas']) && $_GET['incluir_inactivas'] === '1';

    $sql = 'SELECT a.id, a.nombre, a.piso, a.capacidad_pcs, a.activa,
                   COUNT(c.id) AS total_pcs,
                   SUM(c.estado = "OPERATIVA") AS pcs_operativas,
                   SUM(c.estado = "FUERA_SERVICIO") AS pcs_fuera_servicio,
                   SUM(c.estado = "MANTENIMIENTO") AS pcs_mantenimiento
            FROM aulas a
            LEFT JOIN computadoras c ON c.id_aula = a.id';

    if (!$incluirInactivas) {
        $sql .= ' WHERE a.activa = 1';
    }

    $sql .= ' GROUP BY a.id ORDER BY a.id';

    $stmt = $pdo->query($sql);
    jsonResponse($stmt->fetchAll());
}

// --- POST: Crear aula (ADMIN) ---
if ($method === 'POST') {
    requiereAdmin();

    $input = leerJsonInput();
    $id            = trim($input['id'] ?? '');
    $nombre        = trim($input['nombre'] ?? '');
    $piso          = (int)($input['piso'] ?? 0);
    $capacidadPcs  = (int)($input['capacidad_pcs'] ?? 0);

    if ($id === '' || $nombre === '') {
        jsonResponse(['success' => false, 'message' => 'Se requieren id y nombre.'], 400);
    }

    if (!preg_match('/^[A-Za-z0-9\-]{1,10}$/', $id)) {
        jsonResponse(['success' => false, 'message' => 'El ID solo puede contener letras, números y guiones (máx 10).'], 400);
    }

    // Verificar que no exista
    $stmt = $pdo->prepare('SELECT id FROM aulas WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Ya existe un aula con ese ID.'], 409);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO aulas (id, nombre, piso, capacidad_pcs) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$id, $nombre, $piso, $capacidadPcs]);

        registrarAccion(
            $pdo,
            usuarioActualId(),
            'AULA_CREADA',
            json_encode([
                'id'            => $id,
                'nombre'        => $nombre,
                'piso'          => $piso,
                'capacidad_pcs' => $capacidadPcs,
            ])
        );

        $pdo->commit();
        jsonResponse([
            'success' => true,
            'message' => 'Aula creada correctamente.',
            'aula'    => ['id' => $id, 'nombre' => $nombre, 'piso' => $piso, 'capacidad_pcs' => $capacidadPcs],
        ], 201);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Error al crear el aula.'], 500);
    }
}

// --- PUT: Actualizar aula (ADMIN) ---
if ($method === 'PUT') {
    requiereAdmin();

    $input = leerJsonInput();
    $id = trim($input['id'] ?? '');

    if ($id === '') {
        jsonResponse(['success' => false, 'message' => 'Se requiere el ID del aula.'], 400);
    }

    $stmt = $pdo->prepare('SELECT * FROM aulas WHERE id = ?');
    $stmt->execute([$id]);
    $aulaActual = $stmt->fetch();
    if (!$aulaActual) {
        jsonResponse(['success' => false, 'message' => 'Aula no encontrada.'], 404);
    }

    $campos = [];
    $params = [];

    if (array_key_exists('nombre', $input)) {
        $nombre = trim($input['nombre']);
        if ($nombre === '') {
            jsonResponse(['success' => false, 'message' => 'El nombre no puede estar vacío.'], 400);
        }
        $campos[] = 'nombre = ?';
        $params[] = $nombre;
    }

    if (array_key_exists('piso', $input)) {
        $campos[] = 'piso = ?';
        $params[] = (int)$input['piso'];
    }

    if (array_key_exists('capacidad_pcs', $input)) {
        $campos[] = 'capacidad_pcs = ?';
        $params[] = (int)$input['capacidad_pcs'];
    }

    if (array_key_exists('activa', $input)) {
        $campos[] = 'activa = ?';
        $params[] = (int)(bool)$input['activa'];
    }

    if (empty($campos)) {
        jsonResponse(['success' => false, 'message' => 'No hay campos para actualizar.'], 400);
    }

    $params[] = $id;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('UPDATE aulas SET ' . implode(', ', $campos) . ' WHERE id = ?');
        $stmt->execute($params);

        registrarAccion(
            $pdo,
            usuarioActualId(),
            'AULA_ACTUALIZADA',
            json_encode(['id' => $id, 'cambios' => $input])
        );

        $pdo->commit();
        jsonResponse(['success' => true, 'message' => 'Aula actualizada correctamente.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Error al actualizar el aula.'], 500);
    }
}

// --- DELETE: Soft-delete (ADMIN) ---
if ($method === 'DELETE') {
    requiereAdmin();

    // DELETE no siempre trae body; usamos query string como fallback
    $input = leerJsonInput();
    $id = trim($input['id'] ?? $_GET['id'] ?? '');

    if ($id === '') {
        jsonResponse(['success' => false, 'message' => 'Se requiere el ID del aula.'], 400);
    }

    // Verificar que no tenga PCs activas
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM computadoras WHERE id_aula = ?');
    $stmt->execute([$id]);
    $totalPcs = (int)$stmt->fetchColumn();

    if ($totalPcs > 0) {
        jsonResponse([
            'success' => false,
            'message' => "No se puede desactivar: el aula tiene {$totalPcs} PC(s) asociada(s). Eliminá las PCs primero.",
        ], 409);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('UPDATE aulas SET activa = 0 WHERE id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'message' => 'Aula no encontrada.'], 404);
        }

        registrarAccion(
            $pdo,
            usuarioActualId(),
            'AULA_DESACTIVADA',
            json_encode(['id' => $id])
        );

        $pdo->commit();
        jsonResponse(['success' => true, 'message' => 'Aula desactivada correctamente.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Error al desactivar el aula.'], 500);
    }
}

jsonResponse(['error' => 'Método no permitido.'], 405);
