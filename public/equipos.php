<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

requireAuth();

$esAdministrador = (int) ($_SESSION['rol_id'] ?? 0) === 1;

if (!in_array((int) ($_SESSION['rol_id'] ?? 0), [1, 2], true)) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

$stmt = $dbh->prepare(
    "SELECT
        e.id,
        e.codigo,
        e.nombre,
        e.estado,
        e.version,
        c.nombre AS categoria
     FROM equipos e
     INNER JOIN categorias c ON e.categoria_id = c.id
     WHERE e.eliminado_en IS NULL
     ORDER BY e.id DESC"
);

$stmt->execute();
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titulo = 'Equipos';
$mostrarMenu = true;

require_once __DIR__ . '/../includes/header.php';
?>

<main class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Equipos</h2>
            <p class="text-muted mb-0">
                Equipos registrados en SIGEL.
            </p>
        </div>

        <?php if ($esAdministrador): ?>
            <a href="/equipos_form.php" class="btn btn-primary">
                Nuevo equipo
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($_SESSION['equipo_exito'])): ?>
        <div class="alert alert-success">
            <?= e($_SESSION['equipo_exito']) ?>
        </div>
        <?php unset($_SESSION['equipo_exito']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['equipo_error'])): ?>
        <div class="alert alert-danger">
            <?= e($_SESSION['equipo_error']) ?>
        </div>
        <?php unset($_SESSION['equipo_error']); ?>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">

                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th>Versión</th>

                            <?php if ($esAdministrador): ?>
                                <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($equipos)): ?>
                            <tr>
                                <td
                                    colspan="<?= $esAdministrador ? 7 : 6 ?>"
                                    class="text-center text-muted py-4">
                                    No hay equipos registrados.
                                </td>
                            </tr>
                        <?php else: ?>

                            <?php foreach ($equipos as $equipo): ?>
                                <tr>
                                    <td><?= e((string) $equipo['id']) ?></td>
                                    <td><?= e($equipo['codigo']) ?></td>
                                    <td><?= e($equipo['nombre']) ?></td>
                                    <td><?= e($equipo['categoria']) ?></td>
                                    <td><?= e($equipo['estado']) ?></td>
                                    <td><?= e((string) $equipo['version']) ?></td>

                                    <?php if ($esAdministrador): ?>
                                        <td>
                                            <div class="d-flex gap-2">

                                                <a
                                                    href="/equipos_form.php?id=<?= e((string) $equipo['id']) ?>"
                                                    class="btn btn-outline-primary btn-sm">
                                                    Editar
                                                </a>

                                                <form
                                                    action="/actions/equipo_eliminar.php"
                                                    method="POST"
                                                    class="m-0"
                                                    onsubmit="return confirm('¿Desea eliminar este equipo?');">

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e(csrfToken()) ?>">

                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= e((string) $equipo['id']) ?>">

                                                    <button
                                                        type="submit"
                                                        class="btn btn-outline-danger btn-sm">
                                                        Eliminar
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    <?php endif; ?>
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