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
    !is_string($_POST['codigo'] ?? null) ||
    !is_string($_POST['nombre'] ?? null) ||
    !is_string($_POST['categoria_id'] ?? null) ||
    !is_string($_POST['estado'] ?? null)
) {
    http_response_code(400);
    exit('Datos inválidos.');
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

function regresarRegistroEquipo(
    array $errores,
    array $datos
): void {
    $_SESSION['equipo_errores']['crear'] = $errores;
    $_SESSION['equipo_anterior']['crear'] = $datos;

    header('Location: /equipos_form.php');
    exit;
}

$datos = [
    'codigo' => $codigo,
    'nombre' => $nombre,
    'categoria_id' => $categoriaRecibida,
    'estado' => $estado
];

if (!empty($errores)) {
    regresarRegistroEquipo($errores, $datos);
}

try {

    // Comprobar que la categoría está disponible.
    $stmt = $dbh->prepare(
        "SELECT id
         FROM categorias
         WHERE id = :id
           AND eliminado_en IS NULL
         LIMIT 1"
    );

    $stmt->execute([':id' => $categoriaId]);

    if (!$stmt->fetchColumn()) {
        regresarRegistroEquipo(
            ['La categoría seleccionada no está disponible.'],
            $datos
        );
    }

    $stmt = $dbh->prepare(
        "INSERT INTO equipos
            (categoria_id, codigo, nombre, estado)
         VALUES
            (:categoria_id, :codigo, :nombre, :estado)"
    );

    $stmt->execute([
        ':categoria_id' => $categoriaId,
        ':codigo' => $codigo,
        ':nombre' => $nombre,
        ':estado' => $estado
    ]);

    $_SESSION['equipo_exito'] =
        'Equipo registrado correctamente.';

    header('Location: /equipos.php');
    exit;

} catch (PDOException $e) {

    if ($e->getCode() === '23000') {
        regresarRegistroEquipo(
            ['El código del equipo ya existe o la categoría no es válida.'],
            $datos
        );
    }

    error_log($e->getMessage());

    regresarRegistroEquipo(
        ['No fue posible registrar el equipo.'],
        $datos
    );
}