<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireAuth();

$rol = (int) ($_SESSION['rol_id'] ?? 0);

if (!in_array($rol, [1, 2], true)) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

$esOperador = $rol === 2;

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$id) {
    http_response_code(400);
    exit('Identificador inválido.');
}

// Obtener información del préstamo.
$stmt = $dbh->prepare(
    "SELECT
        p.id,
        p.estado,
        p.fecha_inicio,
        p.fecha_fin,
        u.nombre AS usuario,
        o.nombre AS operador
     FROM prestamos p
     INNER JOIN usuarios u ON u.id = p.usuario_id
     INNER JOIN usuarios o ON o.id = p.operador_id
     WHERE p.id = :id"
);

$stmt->execute([':id' => $id]);
$prestamo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prestamo) {
    http_response_code(404);
    exit('Préstamo no encontrado.');
}

// Obtener los equipos asociados.
$stmt = $dbh->prepare(
    "SELECT
        e.id AS equipo_id,
        e.codigo,
        e.nombre,
        pd.fecha_devolucion
     FROM prestamo_detalle pd
     INNER JOIN equipos e ON e.id = pd.equipo_id
     WHERE pd.prestamo_id = :id
     ORDER BY e.nombre"
);

$stmt->execute([':id' => $id]);
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titulo = 'Detalle del préstamo';
$mostrarMenu = true;

require_once __DIR__ . '/../includes/header.php';
?>

<main class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Préstamo #<?= e((string) $id) ?></h2>

        <a href="/prestamos.php" class="btn btn-outline-secondary">
            Volver
        </a>
    </div>

    <?php foreach (['prestamo_exito', 'prestamo_error'] as $clave): ?>
        <?php if (!empty($_SESSION[$clave])): ?>

            <div class="alert <?= $clave === 'prestamo_exito'
                ? 'alert-success' : 'alert-danger' ?>">

                <?= e($_SESSION[$clave]) ?>

            </div>

            <?php unset($_SESSION[$clave]); ?>

        <?php endif; ?>
    <?php endforeach; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">

            <p><strong>Solicitante:</strong>
                <?= e($prestamo['usuario']) ?>
            </p>

            <p><strong>Operador:</strong>
                <?= e($prestamo['operador']) ?>
            </p>

            <p><strong>Inicio:</strong>
                <?= e($prestamo['fecha_inicio']) ?>
            </p>

            <p><strong>Fin previsto:</strong>
                <?= e($prestamo['fecha_fin']) ?>
            </p>

            <p><strong>Estado:</strong>
                <?= e(ucfirst($prestamo['estado'])) ?>
            </p>

            <?php if ($esOperador): ?>

                <div class="d-flex flex-wrap gap-2">

                    <?php
                    $acciones = match ($prestamo['estado']) {
                        'solicitado' => [
                            'aprobar' => 'Aprobar',
                            'cancelar' => 'Cancelar'
                        ],
                        'aprobado' => [
                            'entregar' => 'Entregar',
                            'cancelar' => 'Cancelar'
                        ],
                        default => []
                    };
                    ?>

                    <?php foreach ($acciones as $accion => $etiqueta): ?>

                        <form
                            action="/actions/prestamo_estado.php"
                            method="POST"
                            onsubmit="return confirm('¿Confirmar esta acción?');">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrfToken()) ?>">

                            <input
                                type="hidden"
                                name="prestamo_id"
                                value="<?= e((string) $id) ?>">

                            <input
                                type="hidden"
                                name="accion"
                                value="<?= e($accion) ?>">

                            <button type="submit"
                                class="btn <?= $accion === 'cancelar'
                                    ? 'btn-outline-danger'
                                    : 'btn-primary' ?>">

                                <?= e($etiqueta) ?>

                            </button>

                        </form>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">

            <h5 class="fw-bold mb-3">Equipos del préstamo</h5>

            <div class="table-responsive">
                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Equipo</th>
                            <th>Fecha de devolución</th>
                            <?php if ($esOperador): ?>
                                <th>Acción</th>
                            <?php endif; ?>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($equipos as $equipo): ?>

                            <tr>
                                <td><?= e($equipo['codigo']) ?></td>
                                <td><?= e($equipo['nombre']) ?></td>

                                <td>
                                    <?= $equipo['fecha_devolucion'] !== null
                                        ? e($equipo['fecha_devolucion'])
                                        : 'Pendiente' ?>
                                </td>

                                <?php if ($esOperador): ?>
                                    <td>

                                        <?php if (
                                            $prestamo['estado'] === 'entregado' &&
                                            $equipo['fecha_devolucion'] === null
                                        ): ?>

                                            <form
                                                action="/actions/prestamo_devolver.php"
                                                method="POST"
                                                onsubmit="return confirm('¿Registrar devolución de este equipo?');">

                                                <input type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e(csrfToken()) ?>">

                                                <input type="hidden"
                                                    name="prestamo_id"
                                                    value="<?= e((string) $id) ?>">

                                                <input type="hidden"
                                                    name="equipo_id"
                                                    value="<?= e((string) $equipo['equipo_id']) ?>">

                                                <button
                                                    type="submit"
                                                    class="btn btn-outline-success btn-sm">
                                                    Devolver
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <span class="text-muted">—</span>

                                        <?php endif; ?>

                                    </td>
                                <?php endif; ?>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>
            </div>

        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>