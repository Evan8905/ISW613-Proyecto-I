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

// Obtener y validar el texto de búsqueda.
$busqueda = $_GET['buscar'] ?? '';

if (!is_string($busqueda)) {
    http_response_code(400);
    exit('Parámetro de búsqueda inválido.');
}

$busqueda = trim($busqueda);

// Limitar el tamaño del texto recibido.
if (strlen($busqueda) > 150) {
    http_response_code(400);
    exit('La búsqueda es demasiado larga.');
}

// Consultar usuarios y sus roles.
$sql = "SELECT
            u.id,
            u.nombre,
            u.correo,
            u.estado,
            r.nombre AS rol
        FROM usuarios u
        INNER JOIN roles r ON u.rol_id = r.id
        WHERE u.estado <> 2";

$parametros = [];

// Aplicar búsqueda únicamente cuando se escribe un valor.
if ($busqueda !== '') {

    $sql .= " AND (
        u.nombre LIKE :buscar_nombre
        OR u.correo LIKE :buscar_correo
    )";

    $parametros[':buscar_nombre'] = '%' . $busqueda . '%';
    $parametros[':buscar_correo'] = '%' . $busqueda . '%';
}

$sql .= " ORDER BY u.id DESC";

$stmt = $dbh->prepare($sql);
$stmt->execute($parametros);

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
                <form action="/usuarios.php" method="GET" class="mb-4">

                    <div class="row g-2">

                        <div class="col-md-9">

                            <input type="search" name="buscar" class="form-control"
                                placeholder="Buscar por nombre o correo electrónico" maxlength="150"
                                value="<?= e($busqueda) ?>">

                        </div>

                        <div class="col-md-3 d-flex gap-2">

                            <button type="submit" class="btn btn-primary flex-fill">
                                Buscar
                            </button>

                            <a href="/usuarios.php" class="btn btn-outline-secondary">
                                Limpiar
                            </a>

                        </div>

                    </div>

                </form>

                <?php if (!empty($_SESSION['usuario_exito'])): ?>

                    <div class="alert alert-success" role="alert">
                        <?= e($_SESSION['usuario_exito']) ?>
                    </div>

                    <?php unset($_SESSION['usuario_exito']); ?>

                <?php endif; ?>

                <?php if (!empty($_SESSION['usuario_error'])): ?>

                    <div class="alert alert-danger" role="alert">
                        <?= e($_SESSION['usuario_error']) ?>
                    </div>

                    <?php unset($_SESSION['usuario_error']); ?>

                <?php endif; ?>

                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Correo electrónico</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($usuarios)): ?>

                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">

                                    <?php if ($busqueda !== ''): ?>
                                        No se encontraron usuarios con ese criterio de búsqueda.
                                    <?php else: ?>
                                        No hay usuarios registrados.
                                    <?php endif; ?>

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
                                    <td>
                                        <div class="d-flex gap-2">

                                            <a href="/usuarios_editar.php?id=<?= e((string) $usuario['id']) ?>"
                                                class="btn btn-outline-primary btn-sm">
                                                Editar
                                            </a>

                                            <?php if ((int) $usuario['id'] !== (int) $_SESSION['usuario_id']): ?>

                                                <form action="/actions/usuario_eliminar.php" method="POST" class="m-0"
                                                    onsubmit="return confirm('¿Está seguro de que desea eliminar este usuario?');">

                                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                                                    <input type="hidden" name="id" value="<?= e((string) $usuario['id']) ?>">

                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        Eliminar
                                                    </button>

                                                </form>

                                            <?php endif; ?>

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

<?php
require_once __DIR__ . '/../includes/footer.php';
?>