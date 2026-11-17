<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireAuth();

// Los roles existentes pueden consultar préstamos.
if (!in_array((int) ($_SESSION['rol_id'] ?? 0), [1, 2], true)) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

$esOperador = (int) $_SESSION['rol_id'] === 2;

$stmt = $dbh->prepare(
    "SELECT
        p.id,
        p.estado,
        p.fecha_inicio,
        p.fecha_fin,
        u.nombre AS usuario,
        o.nombre AS operador,
        COUNT(pd.equipo_id) AS cantidad_equipos
     FROM prestamos p
     INNER JOIN usuarios u ON p.usuario_id = u.id
     INNER JOIN usuarios o ON p.operador_id = o.id
     LEFT JOIN prestamo_detalle pd ON pd.prestamo_id = p.id
     GROUP BY
        p.id,
        p.estado,
        p.fecha_inicio,
        p.fecha_fin,
        u.nombre,
        o.nombre
     ORDER BY p.id DESC"
);

$stmt->execute();

$prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titulo = 'Gestión de préstamos';
$mostrarMenu = true;

require_once __DIR__ . '/../includes/header.php';
?>

<main class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">Gestión de préstamos</h2>
            <p class="text-muted mb-0">
                Solicitudes y préstamos registrados en SIGEL.
            </p>
        </div>

        <?php if ($esOperador): ?>
            <a href="/prestamos_crear.php" class="btn btn-primary">
                Nuevo préstamo
            </a>
        <?php endif; ?>

    </div>

    <?php if (!empty($_SESSION['prestamo_exito'])): ?>
        <div class="alert alert-success">
            <?= e($_SESSION['prestamo_exito']) ?>
        </div>
        <?php unset($_SESSION['prestamo_exito']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['prestamo_error'])): ?>
        <div class="alert alert-danger">
            <?= e($_SESSION['prestamo_error']) ?>
        </div>
        <?php unset($_SESSION['prestamo_error']); ?>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Operador</th>
                            <th>Equipos</th>
                            <th>Inicio</th>
                            <th>Fin previsto</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($prestamos)): ?>

                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No hay préstamos registrados.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($prestamos as $prestamo): ?>

                                <tr>
                                    <td><?= e((string) $prestamo['id']) ?></td>

                                    <td><?= e($prestamo['usuario']) ?></td>

                                    <td><?= e($prestamo['operador']) ?></td>

                                    <td>
                                        <?= e((string) $prestamo['cantidad_equipos']) ?>
                                    </td>

                                    <td><?= e($prestamo['fecha_inicio']) ?></td>

                                    <td><?= e($prestamo['fecha_fin']) ?></td>

                                    <td>
                                        <?= e(ucfirst($prestamo['estado'])) ?>
                                    </td>
                                    <td>
                                        <a href="/prestamos_detalle.php?id=<?= e((string) $prestamo['id']) ?>"
                                            class="btn btn-outline-primary btn-sm">
                                            Ver detalle
                                        </a>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>
            </div>

        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>