<?php
declare(strict_types=1);

// backend/auth.php - Autenticación con password_verify() y auditoría

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// --- LOGIN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = leerJsonInput();
    $usuario = trim($input['usuario'] ?? '');
    $clave   = $input['clave'] ?? '';

    if ($usuario === '' || $clave === '') {
        jsonResponse(['success' => false, 'message' => 'Usuario y clave son requeridos.'], 400);
    }

    $stmt = $pdo->prepare('SELECT id, usuario, clave, nombre_completo, rol, activo FROM usuarios WHERE usuario = ?');
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    // Usuario no existe o contraseña incorrecta
    if (!$user || !password_verify($clave, $user['clave'])) {
        // Auditar intento fallido (sin user_id porque no sabemos quién es)
        registrarAccion($pdo, null, 'LOGIN_FALLIDO', json_encode(['usuario_intentado' => $usuario]));
        jsonResponse(['success' => false, 'message' => 'Usuario o clave incorrectos.'], 401);
    }

    // Usuario desactivado
    if (!$user['activo']) {
        registrarAccion($pdo, (int)$user['id'], 'LOGIN_USUARIO_INACTIVO', null);
        jsonResponse(['success' => false, 'message' => 'Tu cuenta está desactivada. Contactá al administrador.'], 403);
    }

    // Login exitoso - regenerar session ID para prevenir fixation
    session_regenerate_id(true);
    $_SESSION['user_id']  = (int)$user['id'];
    $_SESSION['usuario']  = $user['usuario'];
    $_SESSION['nombre']   = $user['nombre_completo'];
    $_SESSION['rol']      = $user['rol'];

    // Auditar login exitoso
    registrarAccion($pdo, (int)$user['id'], 'LOGIN_EXITOSO', null);

    jsonResponse([
        'success' => true,
        'user' => [
            'id'       => (int)$user['id'],
            'usuario'  => $user['usuario'],
            'nombre'   => $user['nombre_completo'],
            'rol'      => $user['rol'],
        ]
    ]);
}

// --- LOGOUT ---
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId !== null) {
        registrarAccion($pdo, (int)$userId, 'LOGOUT', null);
    }
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Sesión cerrada.']);
}

// --- CHECK SESSION ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($_SESSION['user_id'])) {
        jsonResponse([
            'authenticated' => true,
            'user' => [
                'id'       => $_SESSION['user_id'],
                'usuario'  => $_SESSION['usuario'],
                'nombre'   => $_SESSION['nombre'],
                'rol'      => $_SESSION['rol'],
            ]
        ]);
    }
    jsonResponse(['authenticated' => false], 401);
}

jsonResponse(['error' => 'Método no permitido.'], 405);
