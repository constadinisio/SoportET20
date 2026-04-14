<?php
declare(strict_types=1);

// Page-level session guard. Redirects to index.php if not authenticated.
// Optional admin-only check via $requireAdmin = true; before include.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$currentUser = [
    'id'      => (int)($_SESSION['user_id'] ?? 0),
    'usuario' => (string)($_SESSION['usuario'] ?? ''),
    'nombre'  => (string)($_SESSION['nombre'] ?? ''),
    'rol'    => (string)($_SESSION['rol'] ?? ''),
];

if (!empty($requireAdmin) && $currentUser['rol'] !== 'ADMIN') {
    header('Location: home.php');
    exit;
}
