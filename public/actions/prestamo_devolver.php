<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/database.php';

requireAuth();

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

$prestamoRecibido = $_POST['prestamo_id'] ?? null;
$equipoRecibido = $_POST['equipo_id'] ?? null;

if (
    !is_string($prestamoRecibido) ||
    !is_string($equipoRecibido)
) {
    http_response_code(400);
    exit('Datos inválidos.');
}

$prestamoId = filter_var(
    $prestamoRecibido,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$equipoId = filter_var(
    $equipoRecibido,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($prestamoId === false || $equipoId === false) {
    http_response_code(400);
    exit('Identificadores inválidos.');
}

$urlDetalle = '/prestamos_detalle.php?id=' . $prestamoId;

try {

    $dbh->beginTransaction();

    // Bloquear el préstamo.
    $stmt = $dbh->prepare(
        "SELECT estado
         FROM prestamos
         WHERE id = :id
         FOR UPDATE"
    );

    $stmt->execute([':id' => $prestamoId]);

    $estado = $stmt->fetchColumn();

    if ($estado !== 'entregado') {
        $dbh->rollBack();

        $_SESSION['prestamo_error'] =
            'Solo se pueden devolver equipos de préstamos entregados.';

        header('Location: ' . $urlDetalle);
        exit;
    }

    // Bloquear el detalle correspondiente.
    $stmt = $dbh->prepare(
        "SELECT fecha_devolucion
         FROM prestamo_detalle
         WHERE prestamo_id = :prestamo_id
           AND equipo_id = :equipo_id
         FOR UPDATE"
    );

    $stmt->execute([
        ':prestamo_id' => $prestamoId,
        ':equipo_id' => $equipoId
    ]);

    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$detalle) {
        $dbh->rollBack();

        $_SESSION['prestamo_error'] =
            'El equipo no pertenece a este préstamo.';

        header('Location: ' . $urlDetalle);
        exit;
    }

    if ($detalle['fecha_devolucion'] !== null) {
        $dbh->rollBack();

        $_SESSION['prestamo_error'] =
            'Este equipo ya fue devuelto.';

        header('Location: ' . $urlDetalle);
        exit;
    }

    // Registrar la fecha de devolución.
    $fechaDevolucion = (new DateTimeImmutable(
        'now',
        new DateTimeZone('America/Costa_Rica')
    ))->format('Y-m-d H:i:s');

    $stmt = $dbh->prepare(
        "UPDATE prestamo_detalle
         SET fecha_devolucion = :fecha
         WHERE prestamo_id = :prestamo_id
           AND equipo_id = :equipo_id
           AND fecha_devolucion IS NULL"
    );

    $stmt->execute([
        ':fecha' => $fechaDevolucion,
        ':prestamo_id' => $prestamoId,
        ':equipo_id' => $equipoId
    ]);

    // Comprobar cuántos equipos faltan por devolver.
    $stmt = $dbh->prepare(
        "SELECT COUNT(*)
         FROM prestamo_detalle
         WHERE prestamo_id = :prestamo_id
           AND fecha_devolucion IS NULL"
    );

    $stmt->execute([':prestamo_id' => $prestamoId]);

    $pendientes = (int) $stmt->fetchColumn();

    // Completar el préstamo cuando todos fueron devueltos.
    if ($pendientes === 0) {

        $stmt = $dbh->prepare(
            "UPDATE prestamos
             SET estado = 'devuelto'
             WHERE id = :id"
        );

        $stmt->execute([':id' => $prestamoId]);
    }

    $dbh->commit();

    $_SESSION['prestamo_exito'] =
        $pendientes === 0
            ? 'Todos los equipos fueron devueltos. Préstamo finalizado.'
            : 'Devolución del equipo registrada correctamente.';

    header('Location: ' . $urlDetalle);
    exit;

} catch (Throwable $e) {

    if ($dbh->inTransaction()) {
        $dbh->rollBack();
    }

    error_log($e->getMessage());

    $_SESSION['prestamo_error'] =
        'No fue posible registrar la devolución.';

    header('Location: ' . $urlDetalle);
    exit;
}