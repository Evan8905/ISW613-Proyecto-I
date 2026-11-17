<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/database.php';

requireAuth();

// Solo los administradores pueden eliminar usuarios.
if ((int) ($_SESSION['rol_id'] ?? 0) !== 1) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

// La eliminación solo se permite mediante POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

// Validar el token CSRF.
$token = $_POST['csrf_token'] ?? null;

if (!is_string($token) || !validarCsrf($token)) {
    http_response_code(403);
    exit('Solicitud no autorizada.');
}

// Validar el identificador del usuario.
$idRecibido = $_POST['id'] ?? null;

if (!is_string($idRecibido)) {
    http_response_code(400);
    exit('Identificador inválido.');
}

$id = filter_var(
    $idRecibido,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($id === false) {
    http_response_code(400);
    exit('Identificador inválido.');
}

// Evitar que el administrador elimine su propia cuenta.
if ($id === (int) ($_SESSION['usuario_id'] ?? 0)) {
    $_SESSION['usuario_error'] =
        'No puede eliminar su propia cuenta.';

    header('Location: /usuarios.php');
    exit;
}

try {

    // Eliminar lógicamente sin borrar el registro.
    $stmt = $dbh->prepare(
        "UPDATE usuarios
         SET estado = 2
         WHERE id = :id
           AND estado <> 2"
    );

    $stmt->execute([
        ':id' => $id
    ]);

    if ($stmt->rowCount() === 0) {
        $_SESSION['usuario_error'] =
            'El usuario no existe o ya fue eliminado.';
    } else {
        $_SESSION['usuario_exito'] =
            'Usuario eliminado correctamente.';
    }

    header('Location: /usuarios.php');
    exit;

} catch (PDOException $e) {

    error_log($e->getMessage());

    $_SESSION['usuario_error'] =
        'No fue posible eliminar el usuario.';

    header('Location: /usuarios.php');
    exit;
}