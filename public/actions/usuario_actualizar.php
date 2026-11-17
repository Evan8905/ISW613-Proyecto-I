<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/database.php';

requireAuth();

// Acceso exclusivo para administradores.
if ((int) ($_SESSION['rol_id'] ?? 0) !== 1) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

// Aceptar únicamente POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /usuarios.php');
    exit;
}

// Validación CSRF.
$token = $_POST['csrf_token'] ?? null;

if (!is_string($token) || !validarCsrf($token)) {
    http_response_code(403);
    exit('Solicitud no autorizada.');
}

// Validar tipos de entrada.
if (
    !is_string($_POST['id'] ?? null) ||
    !is_string($_POST['nombre'] ?? null) ||
    !is_string($_POST['correo'] ?? null) ||
    !is_string($_POST['rol_id'] ?? null) ||
    !is_string($_POST['estado'] ?? null)
) {
    http_response_code(400);
    exit('Datos inválidos.');
}

// Recuperar datos.
$id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
$nombre = trim($_POST['nombre']);
$correo = trim($_POST['correo']);
$rolId = filter_var($_POST['rol_id'], FILTER_VALIDATE_INT);
$estado = filter_var($_POST['estado'], FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(400);
    exit('Identificador inválido.');
}

$errores = [];

// Validar nombre.
if ($nombre === '' || mb_strlen($nombre, 'UTF-8') > 100) {
    $errores[] = 'El nombre es obligatorio y debe tener máximo 100 caracteres.';
}

// Validar correo.
if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 150) {
    $errores[] = 'Ingrese un correo electrónico válido.';
}

// Validar rol.
if (!$rolId || $rolId <= 0) {
    $errores[] = 'Seleccione un rol válido.';
}

// Validar estado.
if (!in_array($estado, [0, 1], true)) {
    $errores[] = 'Seleccione un estado válido.';
}

// Evitar desactivar o quitar el rol al usuario administrador actual.
if ((int) $id === (int) $_SESSION['usuario_id']) {

    if ((int) $estado !== 1 || (int) $rolId !== 1) {
        $errores[] =
            'No puede desactivar su propia cuenta ni quitarse el rol de Administrador.';
    }
}

// Regresar al formulario si hay errores.
function regresarEdicion(
    int $id,
    array $errores,
    string $nombre,
    string $correo,
    $rolId,
    $estado
): void {

    $_SESSION['usuario_editar_errores'] = $errores;

    $_SESSION['usuario_editar_anterior'] = [
        'nombre' => $nombre,
        'correo' => $correo,
        'rol_id' => $rolId,
        'estado' => $estado
    ];

    header('Location: /usuarios_editar.php?id=' . $id);
    exit;
}

if (!empty($errores)) {
    regresarEdicion($id, $errores, $nombre, $correo, $rolId, $estado);
}

try {

    // Verificar que el usuario exista y no esté eliminado.
    $stmt = $dbh->prepare(
        "SELECT id
         FROM usuarios
         WHERE id = :id AND estado <> 2
         LIMIT 1"
    );

    $stmt->execute([':id' => $id]);

    if (!$stmt->fetchColumn()) {
        http_response_code(404);
        exit('Usuario no encontrado.');
    }

    // Verificar que el rol exista.
    $stmt = $dbh->prepare(
        "SELECT id FROM roles WHERE id = :id LIMIT 1"
    );

    $stmt->execute([':id' => $rolId]);

    if (!$stmt->fetchColumn()) {
        regresarEdicion(
            $id,
            ['El rol seleccionado no existe.'],
            $nombre,
            $correo,
            $rolId,
            $estado
        );
    }

    // Actualizar el usuario.
    $stmt = $dbh->prepare(
        "UPDATE usuarios
         SET nombre = :nombre,
             correo = :correo,
             rol_id = :rol_id,
             estado = :estado
         WHERE id = :id AND estado <> 2"
    );

    $stmt->execute([
        ':nombre' => $nombre,
        ':correo' => $correo,
        ':rol_id' => $rolId,
        ':estado' => $estado,
        ':id' => $id
    ]);

    // Actualizar el nombre de la sesión si editamos nuestra cuenta.
    if ((int) $id === (int) $_SESSION['usuario_id']) {
        $_SESSION['usuario_nombre'] = $nombre;
    }

    $_SESSION['usuario_exito'] = 'Usuario actualizado correctamente.';

    header('Location: /usuarios.php');
    exit;

} catch (PDOException $e) {

    if ($e->getCode() === '23000') {
        regresarEdicion(
            $id,
            ['El correo ya está registrado por otro usuario.'],
            $nombre,
            $correo,
            $rolId,
            $estado
        );
    }

    error_log($e->getMessage());

    regresarEdicion(
        $id,
        ['No fue posible actualizar el usuario.'],
        $nombre,
        $correo,
        $rolId,
        $estado
    );
}