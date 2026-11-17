<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/database.php';

requireAuth();

// Solo el operador gestiona préstamos.
if ((int) ($_SESSION['rol_id'] ?? 0) !== 2) {
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

$idRecibido = $_POST['prestamo_id'] ?? null;
$accion = $_POST['accion'] ?? null;

if (!is_string($idRecibido) || !is_string($accion)) {
    http_response_code(400);
    exit('Datos inválidos.');
}

$prestamoId = filter_var(
    $idRecibido,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($prestamoId === false) {
    http_response_code(400);
    exit('Identificador inválido.');
}

// Transiciones permitidas.
$transiciones = [
    'solicitado' => [
        'aprobar' => 'aprobado',
        'cancelar' => 'cancelado'
    ],
    'aprobado' => [
        'entregar' => 'entregado',
        'cancelar' => 'cancelado'
    ]
];

try {

    $dbh->beginTransaction();

    // Bloquear el préstamo para evitar cambios simultáneos.
    $stmt = $dbh->prepare(
        "SELECT estado
         FROM prestamos
         WHERE id = :id
         FOR UPDATE"
    );

    $stmt->execute([':id' => $prestamoId]);

    $estadoActual = $stmt->fetchColumn();

    if ($estadoActual === false) {
        $dbh->rollBack();

        $_SESSION['prestamo_error'] = 'Préstamo no encontrado.';

        header('Location: /prestamos.php');
        exit;
    }

    $nuevoEstado = $transiciones[$estadoActual][$accion] ?? null;

    if ($nuevoEstado === null) {
        $dbh->rollBack();

        $_SESSION['prestamo_error'] =
            'La transición de estado solicitada no está permitida.';

        header('Location: /prestamos.php');
        exit;
    }

    $stmt = $dbh->prepare(
        "UPDATE prestamos
         SET estado = :estado
         WHERE id = :id"
    );

    $stmt->execute([
        ':estado' => $nuevoEstado,
        ':id' => $prestamoId
    ]);

    $dbh->commit();

    $_SESSION['prestamo_exito'] =
        'Estado del préstamo actualizado correctamente.';

    header('Location: /prestamos.php');
    exit;

} catch (Throwable $e) {

    if ($dbh->inTransaction()) {
        $dbh->rollBack();
    }

    error_log($e->getMessage());

    $_SESSION['prestamo_error'] =
        'No fue posible actualizar el préstamo.';

    header('Location: /prestamos.php');
    exit;
}