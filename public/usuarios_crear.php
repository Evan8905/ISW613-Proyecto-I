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

// Obtener los roles disponibles.
$stmt = $dbh->query(
    "SELECT id, nombre FROM roles ORDER BY nombre"
);

$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recuperar errores y datos anteriores.
$errores = $_SESSION['usuario_errores'] ?? [];
$anterior = $_SESSION['usuario_anterior'] ?? [];

unset(
    $_SESSION['usuario_errores'],
    $_SESSION['usuario_anterior']
);

$titulo = 'Registrar usuario';
$mostrarMenu = true;

require_once __DIR__ . '/../includes/header.php';
?>

<main class="container py-4">

    <div class="mb-4">
        <h2 class="fw-bold">Registrar usuario</h2>
        <p class="text-muted">
            Complete los datos del nuevo usuario.
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

            <form action="/actions/usuario_guardar.php" method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>">

                <div class="mb-3">
                    <label for="nombre" class="form-label">
                        Nombre completo
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="nombre"
                        name="nombre"
                        maxlength="100"
                        value="<?= e($anterior['nombre'] ?? '') ?>"
                        required>
                </div>

                <div class="mb-3">
                    <label for="correo" class="form-label">
                        Correo electrónico
                    </label>

                    <input
                        type="email"
                        class="form-control"
                        id="correo"
                        name="correo"
                        maxlength="150"
                        value="<?= e($anterior['correo'] ?? '') ?>"
                        required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        Contraseña
                    </label>

                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        required>
                </div>

                <div class="mb-3">
                    <label for="confirmar_password" class="form-label">
                        Confirmar contraseña
                    </label>

                    <input
                        type="password"
                        class="form-control"
                        id="confirmar_password"
                        name="confirmar_password"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        required>
                </div>

                <div class="mb-4">
                    <label for="rol_id" class="form-label">
                        Rol
                    </label>

                    <select
                        class="form-select"
                        id="rol_id"
                        name="rol_id"
                        required>

                        <option value="">Seleccione un rol</option>

                        <?php foreach ($roles as $rol): ?>
                            <option
                                value="<?= e((string) $rol['id']) ?>"
                                <?= (string) ($anterior['rol_id'] ?? '') ===
                                    (string) $rol['id'] ? 'selected' : '' ?>>

                                <?= e($rol['nombre']) ?>

                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">

                    <a href="/usuarios.php"
                       class="btn btn-outline-secondary">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Guardar usuario
                    </button>

                </div>

            </form>

        </div>
    </div>

</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>