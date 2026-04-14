<?php
declare(strict_types=1);

// backend/tickets.php - Gestión de tickets de soporte

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/session_check.php';

header('Content-Type: application/json');

// --- GET: Listar tickets (con filtros opcionales) ---
// Query params soportados:
//   estado       — ABIERTO | EN_PROGRESO | RESUELTO | CERRADO
//   prioridad    — BAJA | MEDIA | ALTA | CRITICA
//   id_aula      — filtra por aula de la PC
//   id_pc        — filtra por PC exacta
//   tipo         — HARDWARE | SOFTWARE | RED | PERIFERICO | OTRO
//   desde/hasta  — rango de fechas YYYY-MM-DD
//   busqueda     — búsqueda parcial en descripción
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = 'SELECT t.id, t.id_pc, t.tipo, t.prioridad, t.descripcion, t.estado,
                   t.creado_en, t.cerrado_en, t.nota_resolucion,
                   c.id_aula,
                   u.usuario AS reportado_por,
                   u.nombre_completo AS reportado_por_nombre,
                   r.usuario AS resuelto_por_usuario
            FROM tickets t
            JOIN computadoras c ON t.id_pc = c.id
            JOIN usuarios u ON t.id_usuario = u.id
            LEFT JOIN usuarios r ON t.resuelto_por = r.id';

    $where = [];
    $params = [];

    if (!empty($_GET['estado'])) {
        $where[] = 't.estado = ?';
        $params[] = strtoupper(trim($_GET['estado']));
    }

    if (!empty($_GET['prioridad'])) {
        $where[] = 't.prioridad = ?';
        $params[] = strtoupper(trim($_GET['prioridad']));
    }

    if (!empty($_GET['id_aula'])) {
        $where[] = 'c.id_aula = ?';
        $params[] = trim($_GET['id_aula']);
    }

    if (!empty($_GET['id_pc'])) {
        $where[] = 't.id_pc = ?';
        $params[] = trim($_GET['id_pc']);
    }

    if (!empty($_GET['tipo'])) {
        $where[] = 't.tipo = ?';
        $params[] = strtoupper(trim($_GET['tipo']));
    }

    if (!empty($_GET['desde']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['desde'])) {
        $where[] = 't.creado_en >= ?';
        $params[] = $_GET['desde'] . ' 00:00:00';
    }

    if (!empty($_GET['hasta']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['hasta'])) {
        $where[] = 't.creado_en <= ?';
        $params[] = $_GET['hasta'] . ' 23:59:59';
    }

    if (!empty($_GET['busqueda'])) {
        $where[] = 't.descripcion LIKE ?';
        $params[] = '%' . trim($_GET['busqueda']) . '%';
    }

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY t.creado_en DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    jsonResponse($stmt->fetchAll());
}

// --- Detecta si la descripción contiene keywords de emergencia.
//     Devuelve la palabra detectada o null.
function detectarCritico(string $descripcion): ?string
{
    // Normalizar: minúsculas y quitar tildes
    $normalizada = mb_strtolower($descripcion, 'UTF-8');
    $normalizada = strtr($normalizada, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);

    $keywords = ['humo', 'quemado', 'quemada', 'fuego', 'exploto', 'explotó', 'incendio', 'chispa', 'chispas'];

    foreach ($keywords as $kw) {
        // \b para coincidencia de palabra completa
        if (preg_match('/\b' . preg_quote($kw, '/') . '\b/u', $normalizada)) {
            return $kw;
        }
    }
    return null;
}

// --- POST: Crear ticket ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = leerJsonInput();

    $idPc        = trim($input['id_pc'] ?? '');
    $tipo        = strtoupper(trim($input['tipo'] ?? 'OTRO'));
    $prioridad   = strtoupper(trim($input['prioridad'] ?? 'MEDIA'));
    $descripcion = trim($input['descripcion'] ?? '');

    if ($idPc === '' || $descripcion === '') {
        jsonResponse(['success' => false, 'message' => 'Se requiere id_pc y descripción.'], 400);
    }

    // Validar que la PC existe
    $stmt = $pdo->prepare('SELECT id, id_aula FROM computadoras WHERE id = ?');
    $stmt->execute([$idPc]);
    $pc = $stmt->fetch();

    if (!$pc) {
        jsonResponse(['success' => false, 'message' => 'PC no encontrada.'], 404);
    }

    $tiposValidos = ['HARDWARE', 'SOFTWARE', 'RED', 'PERIFERICO', 'OTRO'];
    if (!in_array($tipo, $tiposValidos, true)) {
        $tipo = 'OTRO';
    }

    $prioridadesValidas = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];
    if (!in_array($prioridad, $prioridadesValidas, true)) {
        $prioridad = 'MEDIA';
    }

    // --- Detección de emergencia: override de prioridad ---
    $keywordDetectada = detectarCritico($descripcion);
    $escalado = false;
    if ($keywordDetectada !== null && $prioridad !== 'CRITICA') {
        $prioridad = 'CRITICA';
        $escalado = true;
        // Emergencia eléctrica/humo → forzar tipo HARDWARE
        $tipo = 'HARDWARE';
    }

    try {
        $pdo->beginTransaction();

        // Crear el ticket
        $stmt = $pdo->prepare(
            'INSERT INTO tickets (id_pc, id_usuario, tipo, prioridad, descripcion)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$idPc, $_SESSION['user_id'], $tipo, $prioridad, $descripcion]);
        $ticketId = (int)$pdo->lastInsertId();

        // Estado resultante de la PC según la prioridad:
        //   CRITICA / ALTA → FUERA_SERVICIO (rojo)
        //   MEDIA / BAJA   → MANTENIMIENTO  (amarillo)
        $nuevoEstadoPc = in_array($prioridad, ['ALTA', 'CRITICA'], true) ? 'FUERA_SERVICIO' : 'MANTENIMIENTO';

        $stmt = $pdo->prepare('SELECT estado FROM computadoras WHERE id = ?');
        $stmt->execute([$idPc]);
        $estadoAnterior = $stmt->fetchColumn();

        $stmt = $pdo->prepare('UPDATE computadoras SET estado = ? WHERE id = ?');
        $stmt->execute([$nuevoEstadoPc, $idPc]);

        // --- Auditoría ---
        $detalleTicket = [
            'ticket_id' => $ticketId,
            'id_pc'     => $idPc,
            'tipo'      => $tipo,
            'prioridad' => $prioridad,
        ];
        if ($escalado) {
            $detalleTicket['escalado_automatico'] = true;
            $detalleTicket['keyword_detectada'] = $keywordDetectada;
        }
        registrarAccion($pdo, (int)$_SESSION['user_id'], 'TICKET_CREADO', json_encode($detalleTicket));

        registrarAccion(
            $pdo,
            (int)$_SESSION['user_id'],
            'CAMBIO_ESTADO_PC',
            json_encode([
                'id_pc'           => $idPc,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo'    => $nuevoEstadoPc,
                'motivo'          => 'Ticket #' . $ticketId . ($escalado ? ' (ESCALADO AUTOMÁTICO: ' . $keywordDetectada . ')' : ''),
            ])
        );

        // --- Notificar a ADMIN + TECNICO (excluyendo al reporter) ---
        $destinatarios = usuariosActivosPorRol($pdo, ['ADMIN', 'TECNICO'], (int)$_SESSION['user_id']);
        $tipoNotif = $escalado ? 'TICKET_CRITICO' : 'TICKET_CREADO';
        $titulo    = $escalado
            ? '🚨 Ticket CRÍTICO #' . $ticketId
            : 'Nuevo ticket #' . $ticketId . ' (' . $prioridad . ')';
        $resumen = mb_substr($descripcion, 0, 140);
        if (mb_strlen($descripcion) > 140) {
            $resumen .= '…';
        }
        $mensaje = 'PC ' . $idPc . ' — ' . $resumen;
        notificarUsuarios($pdo, $destinatarios, $tipoNotif, $titulo, $mensaje, 'ticket', (string)$ticketId);

        $pdo->commit();

        $respuesta = [
            'success'   => true,
            'message'   => 'Ticket #' . $ticketId . ' creado correctamente.',
            'ticket_id' => $ticketId,
            'prioridad' => $prioridad,
            'estado_pc' => $nuevoEstadoPc,
        ];
        if ($escalado) {
            $respuesta['escalado_automatico'] = true;
            $respuesta['keyword_detectada']    = $keywordDetectada;
            $respuesta['message'] = '⚠ Ticket #' . $ticketId . ' ESCALADO A CRÍTICO por detección de "' . $keywordDetectada . '". PC marcada como FUERA DE SERVICIO.';
        }
        jsonResponse($respuesta, 201);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Error al crear el ticket.'], 500);
    }
}

// --- PATCH: Actualizar estado del ticket ---
if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $input = leerJsonInput();

    $ticketId       = (int)($input['id'] ?? 0);
    $nuevoEstado    = strtoupper(trim($input['estado'] ?? ''));
    $notaResolucion = trim($input['nota_resolucion'] ?? '');

    $estadosValidos = ['ABIERTO', 'EN_PROGRESO', 'RESUELTO', 'CERRADO'];

    if ($ticketId <= 0 || !in_array($nuevoEstado, $estadosValidos, true)) {
        jsonResponse([
            'success' => false,
            'message' => 'Se requiere id del ticket y estado válido.'
        ], 400);
    }

    // Verificar que el ticket existe
    $stmt = $pdo->prepare('SELECT id, estado, id_pc FROM tickets WHERE id = ?');
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        jsonResponse(['success' => false, 'message' => 'Ticket no encontrado.'], 404);
    }

    try {
        $pdo->beginTransaction();

        $campos = ['estado = ?'];
        $params = [$nuevoEstado];

        // Si se resuelve o cierra, registrar quién y cuándo
        if (in_array($nuevoEstado, ['RESUELTO', 'CERRADO'], true)) {
            $campos[] = 'resuelto_por = ?';
            $params[] = $_SESSION['user_id'];
            $campos[] = 'cerrado_en = NOW()';

            if ($notaResolucion !== '') {
                $campos[] = 'nota_resolucion = ?';
                $params[] = $notaResolucion;
            }

            // Restaurar PC a OPERATIVA cuando se resuelve el ticket
            $stmtPc = $pdo->prepare('UPDATE computadoras SET estado = ? WHERE id = ?');
            $stmtPc->execute(['OPERATIVA', $ticket['id_pc']]);

            registrarAccion(
                $pdo,
                (int)$_SESSION['user_id'],
                'CAMBIO_ESTADO_PC',
                json_encode([
                    'id_pc'           => $ticket['id_pc'],
                    'estado_anterior' => 'FUERA_SERVICIO',
                    'estado_nuevo'    => 'OPERATIVA',
                    'motivo'          => 'Resolución de Ticket #' . $ticketId,
                ])
            );
        }

        $params[] = $ticketId;
        $sql = 'UPDATE tickets SET ' . implode(', ', $campos) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        registrarAccion(
            $pdo,
            (int)$_SESSION['user_id'],
            'TICKET_ACTUALIZADO',
            json_encode([
                'ticket_id'      => $ticketId,
                'estado_anterior' => $ticket['estado'],
                'estado_nuevo'    => $nuevoEstado,
            ])
        );

        // --- Notificar al profesor que reportó, si se cerró/resolvió ---
        if (in_array($nuevoEstado, ['RESUELTO', 'CERRADO'], true)) {
            $stmtReporter = $pdo->prepare('SELECT id_usuario FROM tickets WHERE id = ?');
            $stmtReporter->execute([$ticketId]);
            $reporterId = (int)$stmtReporter->fetchColumn();

            if ($reporterId > 0 && $reporterId !== (int)$_SESSION['user_id']) {
                $titulo  = '✅ Ticket #' . $ticketId . ' ' . ($nuevoEstado === 'RESUELTO' ? 'resuelto' : 'cerrado');
                $mensaje = 'El ticket que reportaste sobre la PC ' . $ticket['id_pc'] . ' fue ' . mb_strtolower($nuevoEstado) . '.';
                if ($notaResolucion !== '') {
                    $mensaje .= ' Nota: ' . mb_substr($notaResolucion, 0, 200);
                }
                notificarUsuarios($pdo, [$reporterId], 'TICKET_RESUELTO', $titulo, $mensaje, 'ticket', (string)$ticketId);
            }
        }

        $pdo->commit();

        jsonResponse([
            'success' => true,
            'message' => "Ticket #{$ticketId}: {$ticket['estado']} → {$nuevoEstado}"
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Error al actualizar el ticket.'], 500);
    }
}

jsonResponse(['error' => 'Método no permitido.'], 405);
