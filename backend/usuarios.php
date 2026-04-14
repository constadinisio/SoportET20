<?php
declare(strict_types=1);

// backend/usuarios.php - CRUD de usuarios (solo ADMIN)

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/session_check.php';

header('Content-Type: application/json');

// Toda operación sobre usuarios requiere ADMIN
requiereAdmin();

$method = $_SERVER['REQUEST_METHOD'];
const ROLES_VALIDOS = ['ADMIN', 'TECNICO', 'PROFESOR'];

// --- GET: Listar (sin hash de clave) ---
if ($method === 'GET') {
    $incluirInactivos = isset($_GET['incluir_inactivos']) && $_GET['incluir_inactivos'] === '1';

    $sql = 'SELECT id, usuario, nombre_completo, rol, activo, creado_en, actualizado_en FROM usuarios';
    if (!$incluirInactivos) {
        $sql .= ' WHERE activo = 1';
    }
    $sql .= ' ORDER BY usuario';

    $stmt = $pdo->query($sql);
    jsonResponse($stmt->fetchAll());
}

// --- POST: Crear ---
if ($method === 'POST') {
    $input = leerJsonInput();

    $usuario        = trim($input['usuario'] ?? '');
    $clave          = $input['clave'] ?? '';
    $nombreCompleto = trim($input['nombre_completo'] ?? '');
    $rol            = strtoupper(trim($input['rol'] ?? 'PROFESOR'));

    if ($usuario === '' || $clave === '' || $nombreCompleto === '') {
        jsonResponse(['success' => false, 'message' => 'usuario, clave y nombre_completo son obligatorios.'], 400);
    }

    if (!preg_match('/^[a-zA-Z0-9_.\-]{3,50}$/', $usuario)) {
        jsonResponse(['success' => false, 'message' => 'El usuario debe tener 3-50 caracteres (letras, números, _ . -).'], 400);
    }

    if (strlen($clave) < 6) {
        jsonResponse(['success' => false, 'message' => 'La clave debe tener al menos 6 caracteres.'], 400);
    }

    if (!in_array($rol, ROLES_VALIDOS, true)) {
        jsonResponse(['success' => false, 'message' => 'Rol inválido. Usá: ' . implode(', ', ROLES_VALIDOS)], 400);
    }

    // Verificar unicidad del usuario
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
    $stmt->execute([$usuario]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Ya existe un usuario con ese nombre.'], 409);
    }

    $hash = password_hash($clave, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (usuario, clave, nombre_completo, rol) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$usuario, $hash, $nombreCompleto, $rol]);
        $nuevoId = (int)$pdo->lastInsertId();

        registrarAccion(
            $pdo,
            usuarioActualId(),
            'USUARIO_CREADO',
            json_encode([
                'id'              => $nuevoId,
                'usuario'         => $usuario,
                'rol'             => $rol,
                'nombre_completo' => $nombreCompleto,
            ])
        );

        $pdo->commit();
        jsonResponse([
            'success' => true,
            'message' => 'Usuario creado correctamente.',
            'usuario' => [
                'id'              => $nuevoId,
                'usuario'         => $usuario,
                'nombre_completo' => $nombreCompleto,
                'rol'             => $rol,
                'activo'          => 1,
            ],
        ], 201);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Error al crear el usuario.'], 500);
    }
}

// --- PUT: Actualizar ---
if ($method === 'PUT') {
    $input = leerJsonInput();
    $id = (int)($input['id'] ?? 0);

    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Se requiere el ID del usuario.'], 400);
    }

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $usuarioActual = $stmt->fetch();
    if (!$usuarioActual) {
        jsonResponse(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
    }

    // No permitir que un admin se auto-desactive ni se auto-cambie el rol
    $esSelf = ($id === usuarioActualId());

    $campos = [];
    $params = [];
    $cambiosLog = [];

    if (array_key_exists('nombre_completo', $input)) {
        $nombre = trim($input['nombre_completo']);
        if ($nombre === '') {
            jsonResponse(['success' => false, 'message' => 'El nombre no puede estar vacío.'], 400);
        }
        $campos[] = 'nombre_completo = ?';
        $params[] = $nombre;
        $cambiosLog['nombre_completo'] = $nombre;
    }

    if (array_key_exists('rol', $input)) {
        $rol = strtoupper(trim($input['rol']));
        if (!in_array($rol, ROLES_VALIDOS, true)) {
            jsonResponse(['success' => false, 'message' => 'Rol inválido.'], 400);
        }
        if ($esSelf && $rol !== 'ADMIN') {
            jsonResponse(['success' => false, 'message' => 'No podés cambiarte tu propio rol de ADMIN.'], 409);
        }
        $campos[] = 'rol = ?';
        $params[] = $rol;
        $cambiosLog['rol'] = $rol;
    }

    if (array_key_exists('activo', $input)) {
        $activo = (int)(bool)$input['activo'];
        if ($esSelf && $activo === 0) {
            jsonResponse(['success' => false, 'message' => 'No podés desactivarte a vos mismo.'], 409);
        }
        $campos[] = 'activo = ?';
        $params[] = $activo;
        $cambiosLog['activo'] = $activo;
    }

    if (array_key_exists('clave', $input) && $input['clave'] !== '') {
        $clave = $input['clave'];
        if (strlen($clave) < 6) {
            jsonResponse(['success' => false, 'message' => 'La clave debe tener al menos 6 caracteres.'], 400);
        }
        $campos[] = 'clave = ?';
        $params[] = password_hash($clave, PASSWORD_DEFAULT);
        $cambiosLog['clave'] = '(modificada)';
    }

    if (empty($campos)) {
        jsonResponse(['success' => false, 'message' => 'No hay campos para actualizar.'], 400);
    }

    $params[] = $id;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('UPDATE usuarios SET ' . implode(', ', $campos) . ' WHERE id = ?');
        $stmt->execute($params);

        registrarAccion(
            $pdo,
            usuarioActualId(),
            'USUARIO_ACTUALIZADO',
            json_encode(['id' => $id, 'cambios' => $cambiosLog])
        );

        $pdo->commit();
        jsonResponse(['success' => true, 'message' => 'Usuario actualizado correctamente.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Error al actualizar el usuario.'], 500);
    }
}

// --- DELETE: Soft-delete ---
if ($method === 'DELETE') {
    $input = leerJsonInput();
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);

    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Se requiere el ID del usuario.'], 400);
    }

    if ($id === usuarioActualId()) {
        jsonResponse(['success' => false, 'message' => 'No podés eliminarte a vos mismo.'], 409);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('UPDATE usuarios SET activo = 0 WHERE id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        registrarAccion(
            $pdo,
            usuarioActualId(),
            'USUARIO_DESACTIVADO',
            json_encode(['id' => $id])
        );

        $pdo->commit();
        jsonResponse(['success' => true, 'message' => 'Usuario desactivado correctamente.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Error al desactivar el usuario.'], 500);
    }
}

jsonResponse(['error' => 'Método no permitido.'], 405);
