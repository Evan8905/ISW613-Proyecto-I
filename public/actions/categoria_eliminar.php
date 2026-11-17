<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/database.php';

requireAuth();

if ((int) ($_SESSION['rol_id'] ?? 0) !== 1) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

$token = $_POST['csrf_token'] ?? null;

if (!is_string($token) || !validarCsrf($token)) {
    http_response_code(403);
    exit('Solicitud no autorizada.');
}

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

try {

    $stmt = $dbh->prepare(
        "UPDATE categorias
         SET eliminado_en = NOW()
         WHERE id = :id
           AND eliminado_en IS NULL"
    );

    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() === 0) {
        $_SESSION['categoria_error'] =
            'La categoría no existe o ya fue eliminada.';
    } else {
        $_SESSION['categoria_exito'] =
            'Categoría eliminada correctamente.';
    }

    header('Location: /categorias.php');
    exit;

} catch (PDOException $e) {

    error_log($e->getMessage());

    $_SESSION['categoria_error'] =
        'No fue posible eliminar la categoría.';

    header('Location: /categorias.php');
    exit;
}