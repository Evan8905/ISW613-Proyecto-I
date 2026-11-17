<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

// Validar que el usuario tenga una sesión activa.
requireAuth();

// Solo el Administrador puede gestionar usuarios.

if ((int) ($_SESSION['rol_id'] ?? 0) !== 1) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

// Consultar usuarios y sus roles.
$stmt = $dbh->prepare(
    "SELECT
        u.id,
        u.nombre,
        u.correo,
        u.estado,
        r.nombre AS rol
     FROM usuarios u
     INNER JOIN roles r ON u.rol_id = r.id
     WHERE u.estado <> 2
     ORDER BY u.id DESC"
);

$stmt->execute();

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titulo = 'Gestión de usuarios';
$mostrarMenu = true;

require_once __DIR__ . '/../includes/header.php';
?>

<main class="container py-4">

    <div class="mb-4">
        <h2 class="fw-bold">Gestión de usuarios</h2>
        <a href="/usuarios_crear.php" class="btn btn-primary">
            Nuevo usuario
        </a>

        <p class="text-muted">
            Listado de usuarios registrados en SIGEL.
        </p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">

            <div class="table-responsive">
                <?php if (!empty($_SESSION['usuario_exito'])): ?>

                    <div class="alert alert-success" role="alert">
                        <?= e($_SESSION['usuario_exito']) ?>
                    </div>

                    <?php unset($_SESSION['usuario_exito']); ?>

                <?php endif; ?>
                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Correo electrónico</th>
                            <th>Rol</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($usuarios)): ?>

                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No hay usuarios registrados.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($usuarios as $usuario): ?>

                                <tr>
                                    <td><?= e((string) $usuario['id']) ?></td>

                                    <td><?= e($usuario['nombre']) ?></td>

                                    <td><?= e($usuario['correo']) ?></td>

                                    <td><?= e($usuario['rol']) ?></td>

                                    <td>
                                        <?php if ((int) $usuario['estado'] === 1): ?>

                                            <span class="badge text-bg-success">
                                                Activo
                                            </span>

                                        <?php else: ?>

                                            <span class="badge text-bg-secondary">
                                                Inactivo
                                            </span>

                                        <?php endif; ?>
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

<?php
require_once __DIR__ . '/../includes/footer.php';
?>