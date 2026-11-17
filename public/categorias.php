<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireAuth();

// Solo el administrador gestiona categorías.
if ((int) ($_SESSION['rol_id'] ?? 0) !== 1) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

// Obtener categorías que no han sido eliminadas.
$stmt = $dbh->prepare(
    "SELECT
        id,
        nombre,
        dias_max_prestamo
     FROM categorias
     WHERE eliminado_en IS NULL
     ORDER BY id DESC"
);

$stmt->execute();

$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titulo = 'Gestión de categorías';
$mostrarMenu = true;

require_once __DIR__ . '/../includes/header.php';
?>

<main class="container py-4">

    <div class="mb-4">
        <h2 class="fw-bold">Gestión de categorías</h2>
        <p class="text-muted">
            Listado de categorías registradas en SIGEL.
        </p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Días máximos de préstamo</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($categorias)): ?>

                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    No hay categorías registradas.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($categorias as $categoria): ?>

                                <tr>
                                    <td><?= e((string) $categoria['id']) ?></td>

                                    <td><?= e($categoria['nombre']) ?></td>

                                    <td><?= e((string) $categoria['dias_max_prestamo']) ?></td>
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