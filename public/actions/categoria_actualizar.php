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

if (
    !is_string($_POST['id'] ?? null) ||
    !is_string($_POST['nombre'] ?? null) ||
    !is_string($_POST['dias_max_prestamo'] ?? null)
) {
    http_response_code(400);
    exit('Datos inválidos.');
}

$id = filter_var(
    $_POST['id'],
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($id === false) {
    http_response_code(400);
    exit('Identificador inválido.');
}

$nombre = trim($_POST['nombre']);
$diasRecibidos = trim($_POST['dias_max_prestamo']);

$dias = filter_var(
    $diasRecibidos,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 365]]
);

$errores = [];

if ($nombre === '' || mb_strlen($nombre, 'UTF-8') > 100) {
    $errores[] = 'El nombre es obligatorio y debe tener máximo 100 caracteres.';
}

if ($dias === false) {
    $errores[] = 'Los días máximos deben ser un entero entre 1 y 365.';
}

function regresarEdicionCategoria(
    int $id,
    array $errores,
    string $nombre,
    string $dias
): void {
    $_SESSION['categoria_editar_errores'] = $errores;

    $_SESSION['categoria_editar_anterior'] = [
        'nombre' => $nombre,
        'dias_max_prestamo' => $dias
    ];

    header('Location: /categorias_editar.php?id=' . $id);
    exit;
}

if (!empty($errores)) {
    regresarEdicionCategoria($id, $errores, $nombre, $diasRecibidos);
}

try {

    $stmt = $dbh->prepare(
        "UPDATE categorias
         SET nombre = :nombre,
             dias_max_prestamo = :dias
         WHERE id = :id
           AND eliminado_en IS NULL"
    );

    $stmt->execute([
        ':nombre' => $nombre,
        ':dias' => $dias,
        ':id' => $id
    ]);

    // rowCount() puede ser 0 si no se modificaron los datos.
    // Comprobamos si la categoría sigue existiendo.
    $verificar = $dbh->prepare(
        "SELECT id
         FROM categorias
         WHERE id = :id
           AND eliminado_en IS NULL"
    );

    $verificar->execute([':id' => $id]);

    if (!$verificar->fetchColumn()) {
        http_response_code(404);
        exit('Categoría no encontrada.');
    }

    $_SESSION['categoria_exito'] =
        'Categoría actualizada correctamente.';

    header('Location: /categorias.php');
    exit;

} catch (PDOException $e) {

    if ($e->getCode() === '23000') {
        regresarEdicionCategoria(
            $id,
            ['Ya existe otra categoría con ese nombre.'],
            $nombre,
            $diasRecibidos
        );
    }

    error_log($e->getMessage());

    regresarEdicionCategoria(
        $id,
        ['No fue posible actualizar la categoría.'],
        $nombre,
        $diasRecibidos
    );
}