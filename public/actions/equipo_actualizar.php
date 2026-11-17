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

foreach (['id', 'codigo', 'nombre', 'categoria_id', 'estado'] as $campo) {
    if (!is_string($_POST[$campo] ?? null)) {
        http_response_code(400);
        exit('Datos inválidos.');
    }
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

$codigo = trim($_POST['codigo']);
$nombre = trim($_POST['nombre']);
$categoriaRecibida = $_POST['categoria_id'];
$estado = trim($_POST['estado']);

$categoriaId = filter_var(
    $categoriaRecibida,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$datos = [
    'codigo' => $codigo,
    'nombre' => $nombre,
    'categoria_id' => $categoriaRecibida,
    'estado' => $estado
];

function regresarEdicionEquipo(
    int $id,
    array $errores,
    array $datos
): void {
    $clave = 'editar_' . $id;

    $_SESSION['equipo_errores'][$clave] = $errores;
    $_SESSION['equipo_anterior'][$clave] = $datos;

    header('Location: /equipos_form.php?id=' . $id);
    exit;
}

$errores = [];

if ($codigo === '' || mb_strlen($codigo, 'UTF-8') > 50) {
    $errores[] = 'El código es obligatorio y admite máximo 50 caracteres.';
}

if ($nombre === '' || mb_strlen($nombre, 'UTF-8') > 150) {
    $errores[] = 'El nombre es obligatorio y admite máximo 150 caracteres.';
}

if ($estado === '' || mb_strlen($estado, 'UTF-8') > 30) {
    $errores[] = 'El estado es obligatorio y admite máximo 30 caracteres.';
}

if ($categoriaId === false) {
    $errores[] = 'Seleccione una categoría válida.';
}

if (!empty($errores)) {
    regresarEdicionEquipo($id, $errores, $datos);
}

try {

    // Comprobar que el equipo existe.
    $stmt = $dbh->prepare(
        "SELECT id
         FROM equipos
         WHERE id = :id
           AND eliminado_en IS NULL"
    );

    $stmt->execute([':id' => $id]);

    if (!$stmt->fetchColumn()) {
        http_response_code(404);
        exit('Equipo no encontrado.');
    }

    // Comprobar que la categoría existe y no está eliminada.
    $stmt = $dbh->prepare(
        "SELECT id
         FROM categorias
         WHERE id = :id
           AND eliminado_en IS NULL"
    );

    $stmt->execute([':id' => $categoriaId]);

    if (!$stmt->fetchColumn()) {
        regresarEdicionEquipo(
            $id,
            ['La categoría seleccionada no está disponible.'],
            $datos
        );
    }

    $stmt = $dbh->prepare(
        "UPDATE equipos
         SET categoria_id = :categoria_id,
             codigo = :codigo,
             nombre = :nombre,
             estado = :estado
         WHERE id = :id
           AND eliminado_en IS NULL"
    );

    $stmt->execute([
        ':categoria_id' => $categoriaId,
        ':codigo' => $codigo,
        ':nombre' => $nombre,
        ':estado' => $estado,
        ':id' => $id
    ]);

    $_SESSION['equipo_exito'] =
        'Equipo actualizado correctamente.';

    header('Location: /equipos.php');
    exit;

} catch (PDOException $e) {

    if ($e->getCode() === '23000') {
        regresarEdicionEquipo(
            $id,
            ['El código ya existe o la categoría no es válida.'],
            $datos
        );
    }

    error_log($e->getMessage());

    regresarEdicionEquipo(
        $id,
        ['No fue posible actualizar el equipo.'],
        $datos
    );
}