<?php
declare(strict_types=1);

// backend/computers.php - CRUD de computadoras
// GET    -> lista (todos)
// POST   -> crear (ADMIN)
// PUT    -> actualizar metadata (ADMIN)
// PATCH  -> cambiar estado (cualquier usuario logueado)
// DELETE -> eliminar (ADMIN, solo si no tiene tickets abiertos)

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/session_check.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
const ESTADOS_PC = ['OPERATIVA', 'MANTENIMIENTO', 'FUERA_SERVICIO', 'HIBERNANDO', 'ALERTA'];

// --- GET: Listar o detalle ---
if ($method === 'GET') {
    $idPc   = $_GET['id'] ?? null;
    $idAula = $_GET['id_aula'] ?? null;
    $detalle = isset($_GET['detalle']) && $_GET['detalle'] === '1';

    // Detalle de una PC (incluye specs parseadas + ticket activo)
    if ($idPc !== null && $detalle) {
        // Probar con specs primero; si la columna no existe (migración pendiente),
        // caer al SELECT sin specs.
        try {
            $stmt = $pdo->prepare(
                'SELECT c.id, c.id_aula, c.nombre, c.estado, c.ip, c.mac, c.observaciones, c.specs,
                        a.nombre AS aula_nombre
                 FROM computadoras c
                 JOIN aulas a ON a.id = c.id_aula
                 WHERE c.id = ?'
            );
            $stmt->execute([trim($idPc)]);
        } catch (PDOException $e) {
            // Columna specs inexistente → fallback sin ella
            $stmt = $pdo->prepare(
                'SELECT c.id, c.id_aula, c.nombre, c.estado, c.ip, c.mac, c.observaciones,
                        NULL AS specs, a.nombre AS aula_nombre
                 FROM computadoras c
                 JOIN aulas a ON a.id = c.id_aula
                 WHERE c.id = ?'
            );
            $stmt->execute([trim($idPc)]);
        }
        $pc = $stmt->fetch();

        if (!$pc) {
            jsonResponse(['error' => 'PC no encontrada.'], 404);
        }

        // Parsear specs JSON (si está presente)
        $pc['specs'] = !empty($pc['specs']) ? json_decode($pc['specs'], true) : null;

        // Buscar ticket activo (ABIERTO o EN_PROGRESO) más reciente
        $stmt = $pdo->prepare(
            "SELECT t.id, t.tipo, t.prioridad, t.estado, t.descripcion, t.creado_en,
                    u.usuario AS reportado_por, u.nombre_completo AS reportado_por_nombre
             FROM tickets t
             JOIN usuarios u ON u.id = t.id_usuario
             WHERE t.id_pc = ? AND t.estado IN ('ABIERTO', 'EN_PROGRESO')
             ORDER BY t.creado_en DESC
             LIMIT 1"
        );
        $stmt->execute([trim($idPc)]);
        $pc['ticket_activo'] = $stmt->fetch() ?: null;

        // Historial de tickets (resueltos o cerrados), últimos 20
        $stmt = $pdo->prepare(
            "SELECT t.id, t.tipo, t.prioridad, t.estado, t.descripcion, t.nota_resolucion,
                    t.creado_en, t.cerrado_en,
                    u.usuario AS reportado_por, u.nombre_completo AS reportado_por_nombre,
                    r.usuario AS resuelto_por, r.nombre_completo AS resuelto_por_nombre
             FROM tickets t
             JOIN usuarios u ON u.id = t.id_usuario
             LEFT JOIN usuarios r ON r.id = t.resuelto_por
             WHERE t.id_pc = ? AND t.estado IN ('RESUELTO', 'CERRADO')
             ORDER BY COALESCE(t.cerrado_en, t.creado_en) DESC, t.id DESC
             LIMIT 20"
        );
        $stmt->execute([trim($idPc)]);
        $pc['historial_tickets'] = $stmt->fetchAll();

        jsonResponse($pc);
    }

    // Listado filtrado por aula o completo
    // Incluye specs solo si la columna existe (fallback silencioso).
    try {
        if ($idAula !== null) {
            $stmt = $pdo->prepare(
                'SELECT id, id_aula, nombre, estado, ip, mac, observaciones, specs
                 FROM computadoras WHERE id_aula = ? ORDER BY id'
            );
            $stmt->execute([trim($idAula)]);
        } else {
            $stmt = $pdo->query(
                'SELECT id, id_aula, nombre, estado, ip, mac, observaciones, specs
                 FROM computadoras ORDER BY id_aula, id'
            );
        }
    } catch (PDOException $e) {
        if ($idAula !== null) {
            $stmt = $pdo->prepare(
                'SELECT id, id_aula, nombre, estado, ip, mac, observaciones
                 FROM computadoras WHERE id_aula = ? ORDER BY id'
            );
            $stmt->execute([trim($idAula)]);
        } else {
            $stmt = $pdo->query(
                'SELECT id, id_aula, nombre, estado, ip, mac, observaciones
                 FROM computadoras ORDER BY id_aula, id'
            );
        }
    }

    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        if (isset($r['specs'])) {
            $r['specs'] = $r['specs'] !== null ? json_decode($r['specs'], true) : null;
        }
    }
    jsonResponse($rows);
}

// --- Valida y normaliza la estructura de specs.
//     Devuelve el JSON como string listo para guardar, o null si specs está vacío.
function normalizarSpecs($specs): ?string
{
    if ($specs === null || $specs === '' || $specs === []) {
        return null;
    }

    // Si llega como string (p. ej. JSON serializado), intentar decodificarlo
    if (is_string($specs)) {
        $decoded = json_decode($specs, true);
        if (!is_array($decoded)) {
            return null;
        }
        $specs = $decoded;
    }

    if (!is_array($specs)) {
        return null;
    }

    $limpio = [];
    foreach (['cpu', 'ram', 'os', 'placa'] as $campo) {
        if (!empty($specs[$campo]) && is_string($specs[$campo])) {
            $limpio[$campo] = trim($specs[$campo]);
        }
    }

    // Discos: array de objetos {tipo, capacidad, modelo}
    if (!empty($specs['discos']) && is_array($specs['discos'])) {
        $discosLimpios = [];
        foreach ($specs['discos'] as $d) {
            if (!is_array($d)) continue;
            $tipo      = trim((string)($d['tipo'] ?? ''));
            $capacidad = trim((string)($d['capacidad'] ?? ''));
            $modelo    = trim((string)($d['modelo'] ?? ''));
            // Requiere al menos tipo+capacidad para considerarse válido
            if ($tipo === '' || $capacidad === '') continue;
            $discosLimpios[] = [
                'tipo'      => $tipo,
                'capacidad' => $capacidad,
                'modelo'    => $modelo,
            ];
        }
        if (!empty($discosLimpios)) {
            $limpio['discos'] = $discosLimpios;
        }
    }

    return empty($limpio) ? null : json_encode($limpio, JSON_UNESCAPED_UNICODE);
}

// --- POST: Crear (ADMIN) ---
if ($method === 'POST') {
    requiereAdmin();

    $input = leerJsonInput();
    $id            = trim($input['id'] ?? '');
    $idAula        = trim($input['id_aula'] ?? '');
    $nombre        = trim($input['nombre'] ?? '');
    $estado        = strtoupper(trim($input['estado'] ?? 'OPERATIVA'));
    $ip            = trim($input['ip'] ?? '') ?: null;
    $mac           = trim($input['mac'] ?? '') ?: null;
    $observaciones = trim($input['observaciones'] ?? '') ?: null;
    $specsJson     = normalizarSpecs($input['specs'] ?? null);

    if ($id === '' || $idAula === '') {
        jsonResponse(['success' => false, 'message' => 'Se requieren id e id_aula.'], 400);
    }

    if (!preg_match('/^[A-Za-z0-9\-]{1,20}$/', $id)) {
        jsonResponse(['success' => false, 'message' => 'El ID solo puede contener letras, números y guiones (máx 20).'], 400);
    }

    if (!in_array($estado, ESTADOS_PC, true)) {
        jsonResponse(['success' => false, 'message' => 'Estado inválido.'], 400);
    }

    // Verificar aula existe y está activa
    $stmt = $pdo->prepare('SELECT id FROM aulas WHERE id = ? AND activa = 1');
    $stmt->execute([$idAula]);
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'El aula no existe o está inactiva.'], 400);
    }

    // Verificar que no exista la PC
    $stmt = $pdo->prepare('SELECT id FROM computadoras WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Ya existe una PC con ese ID.'], 409);
    }

    try {
        $pdo->beginTransaction();

        // Intenta INSERT con specs; si la columna no existe, fallback sin ella.
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO computadoras (id, id_aula, nombre, estado, ip, mac, observaciones, specs)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$id, $idAula, $nombre, $estado, $ip, $mac, $observaciones, $specsJson]);
        } catch (PDOException $e) {
            // Si el error es por columna faltante, insertar sin specs
            if (strpos($e->getMessage(), 'specs') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
                $stmt = $pdo->prepare(
                    'INSERT INTO computadoras (id, id_aula, nombre, estado, ip, mac, observaciones)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$id, $idAula, $nombre, $estado, $ip, $mac, $observaciones]);
            } else {
                throw $e;
            }
        }

        registrarAccion(
            $pdo,
            usuarioActualId(),
            'PC_CREADA',
            json_encode([
                'id'        => $id,
                'id_aula'   => $idAula,
                'estado'    => $estado,
                'con_specs' => $specsJson !== null,
            ])
        );

        $pdo->commit();
        jsonResponse([
            'success' => true,
            'message' => 'PC creada correctamente.',
            'pc'      => [
                'id' => $id, 'id_aula' => $idAula, 'nombre' => $nombre,
                'estado' => $estado, 'ip' => $ip, 'mac' => $mac,
            ],
        ], 201);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Error al crear la PC.'], 500);
    }
}

// --- PUT: Actualizar metadata (ADMIN) ---
if ($method === 'PUT') {
    requiereAdmin();

    $input = leerJsonInput();
    $id = trim($input['id'] ?? '');

    if ($id === '') {
        jsonResponse(['success' => false, 'message' => 'Se requiere el ID de la PC.'], 400);
    }

    $stmt = $pdo->prepare('SELECT * FROM computadoras WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'PC no encontrada.'], 404);
    }

    $campos = [];
    $params = [];

    if (array_key_exists('nombre', $input)) {
        $campos[] = 'nombre = ?';
        $params[] = trim($input['nombre']);
    }

    if (array_key_exists('id_aula', $input)) {
        $nuevaAula = trim($input['id_aula']);
        $stmt = $pdo->prepare('SELECT id FROM aulas WHERE id = ? AND activa = 1');
        $stmt->execute([$nuevaAula]);
        if (!$stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'El aula destino no existe o está inactiva.'], 400);
        }
        $campos[] = 'id_aula = ?';
        $params[] = $nuevaAula;
    }

    if (array_key_exists('ip', $input)) {
        $ip = trim($input['ip']);
        $campos[] = 'ip = ?';
        $params[] = $ip === '' ? null : $ip;
    }

    if (array_key_exists('mac', $input)) {
        $mac = trim($input['mac']);
        $campos[] = 'mac = ?';
        $params[] = $mac === '' ? null : $mac;
    }

    if (array_key_exists('observaciones', $input)) {
        $obs = trim($input['observaciones']);
        $campos[] = 'observaciones = ?';
        $params[] = $obs === '' ? null : $obs;
    }

    $incluyeSpecs = array_key_exists('specs', $input);
    if ($incluyeSpecs) {
        $campos[] = 'specs = ?';
        $params[] = normalizarSpecs($input['specs']);
    }

    if (empty($campos)) {
        jsonResponse(['success' => false, 'message' => 'No hay campos para actualizar.'], 400);
    }

    $params[] = $id;

    try {
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE computadoras SET ' . implode(', ', $campos) . ' WHERE id = ?');
            $stmt->execute($params);
        } catch (PDOException $e) {
            // Si es porque specs no existe, reintentar sin ese campo
            if ($incluyeSpecs && (strpos($e->getMessage(), 'specs') !== false || strpos($e->getMessage(), 'Unknown column') !== false)) {
                $camposFallback = [];
                $paramsFallback = [];
                foreach ($campos as $idx => $c) {
                    if (strpos($c, 'specs') === 0) continue;
                    $camposFallback[] = $c;
                    $paramsFallback[] = $params[$idx];
                }
                if (!empty($camposFallback)) {
                    $paramsFallback[] = $id;
                    $stmt = $pdo->prepare('UPDATE computadoras SET ' . implode(', ', $camposFallback) . ' WHERE id = ?');
                    $stmt->execute($paramsFallback);
                }
            } else {
                throw $e;
            }
        }

        // No loguear el JSON completo de specs (puede ser largo); solo un flag
        $detalleLog = $input;
        if (isset($detalleLog['specs'])) {
            $detalleLog['specs'] = '(actualizadas)';
        }

        registrarAccion(
            $pdo,
            usuarioActualId(),
            'PC_ACTUALIZADA',
            json_encode(['id' => $id, 'cambios' => $detalleLog])
        );

        $pdo->commit();
        jsonResponse(['success' => true, 'message' => 'PC actualizada correctamente.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Error al actualizar la PC.'], 500);
    }
}

// --- PATCH: Cambiar estado (cualquier usuario logueado) ---
if ($method === 'PATCH') {
    $input = leerJsonInput();
    $idPc        = trim($input['id_pc'] ?? $input['id'] ?? '');
    $nuevoEstado = strtoupper(trim($input['estado'] ?? ''));

    if ($idPc === '' || !in_array($nuevoEstado, ESTADOS_PC, true)) {
        jsonResponse([
            'success' => false,
            'message' => 'Se requiere id_pc y un estado válido: ' . implode(', ', ESTADOS_PC),
        ], 400);
    }

    $stmt = $pdo->prepare('SELECT estado FROM computadoras WHERE id = ?');
    $stmt->execute([$idPc]);
    $pc = $stmt->fetch();

    if (!$pc) {
        jsonResponse(['success' => false, 'message' => 'PC no encontrada.'], 404);
    }

    $estadoAnterior = $pc['estado'];
    if ($estadoAnterior === $nuevoEstado) {
        jsonResponse(['success' => true, 'message' => 'El estado ya es ' . $nuevoEstado]);
    }

    $stmt = $pdo->prepare('UPDATE computadoras SET estado = ? WHERE id = ?');
    $stmt->execute([$nuevoEstado, $idPc]);

    registrarAccion(
        $pdo,
        usuarioActualId(),
        'CAMBIO_ESTADO_PC',
        json_encode([
            'id_pc'           => $idPc,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo'    => $nuevoEstado,
        ])
    );

    jsonResponse([
        'success' => true,
        'message' => "PC {$idPc}: {$estadoAnterior} → {$nuevoEstado}",
    ]);
}

// --- DELETE: Eliminar (ADMIN, solo si no tiene tickets abiertos) ---
if ($method === 'DELETE') {
    requiereAdmin();

    $input = leerJsonInput();
    $id = trim($input['id'] ?? $_GET['id'] ?? '');

    if ($id === '') {
        jsonResponse(['success' => false, 'message' => 'Se requiere el ID de la PC.'], 400);
    }

    // Verificar que no tenga tickets activos
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM tickets WHERE id_pc = ? AND estado IN ('ABIERTO', 'EN_PROGRESO')"
    );
    $stmt->execute([$id]);
    $ticketsActivos = (int)$stmt->fetchColumn();

    if ($ticketsActivos > 0) {
        jsonResponse([
            'success' => false,
            'message' => "No se puede eliminar: la PC tiene {$ticketsActivos} ticket(s) activo(s). Resolvelos primero.",
        ], 409);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('DELETE FROM computadoras WHERE id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'message' => 'PC no encontrada.'], 404);
        }

        registrarAccion(
            $pdo,
            usuarioActualId(),
            'PC_ELIMINADA',
            json_encode(['id' => $id])
        );

        $pdo->commit();
        jsonResponse(['success' => true, 'message' => 'PC eliminada correctamente.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        // Si hay FK de tickets históricos (RESUELTO/CERRADO), ofrecer soft-delete vía estado
        jsonResponse([
            'success' => false,
            'message' => 'No se puede eliminar la PC porque tiene historial de tickets. Marcala como FUERA_SERVICIO en su lugar.',
        ], 409);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Error al eliminar la PC.'], 500);
    }
}

jsonResponse(['error' => 'Método no permitido.'], 405);
