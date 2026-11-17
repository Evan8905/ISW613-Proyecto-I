<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireAuth();

// Solo administradores.
if ((int) ($_SESSION['rol_id'] ?? 0) !== 1) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

// Validar identificador.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(400);
    exit('Identificador de usuario inválido.');
}

// Consultar usuario.
$stmt = $dbh->prepare(
    "SELECT id, nombre, correo, rol_id, estado
     FROM usuarios
     WHERE id = :id AND estado <> 2
     LIMIT 1"
);

$stmt->execute([':id' => $id]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    http_response_code(404);
    exit('Usuario no encontrado.');
}

// Consultar roles disponibles.
$stmt = $dbh->query(
    "SELECT id, nombre FROM roles ORDER BY nombre"
);

$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recuperar errores de validación.
$errores = $_SESSION['usuario_editar_errores'] ?? [];
$anterior = $_SESSION['usuario_editar_anterior'] ?? [];

unset(
    $_SESSION['usuario_editar_errores'],
    $_SESSION['usuario_editar_anterior']
);

// Recuperar los valores enviados si hubo errores.
$usuario = array_merge($usuario, $anterior);

$titulo = 'Editar usuario';
$mostrarMenu = true;

require_once __DIR__ . '/../includes/header.php';
?>

<main class="container py-4">

    <div class="mb-4">
        <h2 class="fw-bold">Editar usuario</h2>
        <p class="text-muted">
            Modifique la información del usuario seleccionado.
        </p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">

            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="/actions/usuario_actualizar.php" method="POST">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= e(csrfToken()) ?>">

                <input type="hidden"
                       name="id"
                       value="<?= e((string) $id) ?>">

                <div class="mb-3">
                    <label for="nombre" class="form-label">
                        Nombre completo
                    </label>

                    <input type="text"
                           class="form-control"
                           id="nombre"
                           name="nombre"
                           maxlength="100"
                           value="<?= e($usuario['nombre']) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label for="correo" class="form-label">
                        Correo electrónico
                    </label>

                    <input type="email"
                           class="form-control"
                           id="correo"
                           name="correo"
                           maxlength="150"
                           value="<?= e($usuario['correo']) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label for="rol_id" class="form-label">
                        Rol
                    </label>

                    <select class="form-select"
                            id="rol_id"
                            name="rol_id"
                            required>

                        <?php foreach ($roles as $rol): ?>

                            <option
                                value="<?= e((string) $rol['id']) ?>"
                                <?= (int) $usuario['rol_id'] === (int) $rol['id']
                                    ? 'selected'
                                    : '' ?>>

                                <?= e($rol['nombre']) ?>

                            </option>

                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="estado" class="form-label">
                        Estado
                    </label>

                    <select class="form-select"
                            id="estado"
                            name="estado"
                            required>

                        <option value="1"
                            <?= (int) $usuario['estado'] === 1 ? 'selected' : '' ?>>
                            Activo
                        </option>

                        <option value="0"
                            <?= (int) $usuario['estado'] === 0 ? 'selected' : '' ?>>
                            Inactivo
                        </option>

                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">

                    <a href="/usuarios.php"
                       class="btn btn-outline-secondary">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Guardar cambios
                    </button>

                </div>

            </form>

        </div>
    </div>

</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>