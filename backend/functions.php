<?php
declare(strict_types=1);

// backend/functions.php - Funciones compartidas

// Global exception handler: garantiza que CUALQUIER error se entregue como JSON.
// Sin esto, una PDOException no atrapada imprime HTML con <br /> y rompe el fetch del cliente.
set_exception_handler(function (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    echo json_encode([
        'error'   => 'Error interno del servidor',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

/**
 * Registra una acción en la tabla de auditoría.
 *
 * @param PDO      $pdo        Conexión a la base de datos
 * @param int|null $usuarioId  ID del usuario (null para acciones del sistema)
 * @param string   $accion     Código de la acción, ej: LOGIN_EXITOSO, CAMBIO_ESTADO_PC
 * @param string|null $detalle Contexto adicional en formato libre (se guarda como texto)
 */
function registrarAccion(PDO $pdo, ?int $usuarioId, string $accion, ?string $detalle = null): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $pdo->prepare(
        'INSERT INTO log_acciones (id_usuario, accion, detalle, ip) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$usuarioId, $accion, $detalle, $ip]);
}

/**
 * Envía una respuesta JSON y termina la ejecución.
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Lee el body JSON del request y lo devuelve como array asociativo.
 * Si no hay JSON válido, retorna array vacío.
 */
function leerJsonInput(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Exige que el usuario autenticado tenga uno de los roles permitidos.
 * Si no cumple, responde 403 y termina.
 *
 * @param string[] $rolesPermitidos Ej: ['ADMIN'] o ['ADMIN', 'TECNICO']
 */
function requiereRol(array $rolesPermitidos): void
{
    $rolActual = $_SESSION['rol'] ?? null;
    if ($rolActual === null || !in_array($rolActual, $rolesPermitidos, true)) {
        jsonResponse([
            'error' => 'Acceso denegado',
            'message' => 'Tu rol no tiene permisos para realizar esta acción.',
        ], 403);
    }
}

/** Atajo para endpoints solo-admin. */
function requiereAdmin(): void
{
    requiereRol(['ADMIN']);
}

/** ID del usuario autenticado actual. */
function usuarioActualId(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

/**
 * Inserta una notificación para cada usuario en $idsDestino.
 * No dispara excepción si no hay destinatarios.
 */
function notificarUsuarios(
    PDO $pdo,
    array $idsDestino,
    string $tipo,
    string $titulo,
    string $mensaje,
    ?string $entidadTipo = null,
    ?string $entidadId = null
): int {
    if (empty($idsDestino)) {
        return 0;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO notificaciones (id_usuario_destino, tipo, titulo, mensaje, entidad_tipo, entidad_id)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insertados = 0;
    foreach ($idsDestino as $idDestino) {
        $stmt->execute([(int)$idDestino, $tipo, $titulo, $mensaje, $entidadTipo, $entidadId]);
        $insertados++;
    }
    return $insertados;
}

/**
 * Devuelve IDs de usuarios activos con uno de los roles dados, excluyendo opcionalmente a uno.
 */
function usuariosActivosPorRol(PDO $pdo, array $roles, ?int $excluirId = null): array
{
    if (empty($roles)) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $sql = "SELECT id FROM usuarios WHERE activo = 1 AND rol IN ($placeholders)";
    $params = $roles;
    if ($excluirId !== null) {
        $sql .= ' AND id != ?';
        $params[] = $excluirId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}
