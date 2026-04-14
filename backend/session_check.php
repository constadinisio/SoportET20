<?php
declare(strict_types=1);

// backend/session_check.php - Verificación de sesión activa
// Incluir este archivo al inicio de cualquier endpoint protegido.
// Ejemplo: require __DIR__ . '/session_check.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'No autorizado',
        'message' => 'Sesión no válida o expirada. Iniciá sesión nuevamente.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
