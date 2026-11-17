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

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">Gestión de categorías</h2>
            <p class="text-muted mb-0">
                Listado de categorías registradas en SIGEL.
            </p>
        </div>

        <a href="/categorias_crear.php" class="btn btn-primary">
            Nueva categoría
        </a>

    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <?php if (!empty($_SESSION['categoria_exito'])): ?>

                <div class="alert alert-success" role="alert">
                    <?= e($_SESSION['categoria_exito']) ?>
                </div>

                <?php unset($_SESSION['categoria_exito']); ?>

            <?php endif; ?>

            <?php if (!empty($_SESSION['categoria_error'])): ?>

                <div class="alert alert-danger" role="alert">
                    <?= e($_SESSION['categoria_error']) ?>
                </div>

                <?php unset($_SESSION['categoria_error']); ?>

            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Días máximos de préstamo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($categorias)): ?>

                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No hay categorías registradas.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($categorias as $categoria): ?>

                                <tr>
                                    <td><?= e((string) $categoria['id']) ?></td>

                                    <td><?= e($categoria['nombre']) ?></td>

                                    <td><?= e((string) $categoria['dias_max_prestamo']) ?></td>
                                    <td>
                                        <div class="d-flex gap-2">

                                            <a href="/categorias_editar.php?id=<?= e((string) $categoria['id']) ?>"
                                                class="btn btn-outline-primary btn-sm">
                                                Editar
                                            </a>

                                            <form action="/actions/categoria_eliminar.php" method="POST" class="m-0"
                                                onsubmit="return confirm('¿Está seguro de que desea eliminar esta categoría?');">

                                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                                                <input type="hidden" name="id" value="<?= e((string) $categoria['id']) ?>">

                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    Eliminar
                                                </button>

                                            </form>

                                        </div>
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