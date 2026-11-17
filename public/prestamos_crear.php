<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireAuth();

// El operador registra las solicitudes.
if ((int) ($_SESSION['rol_id'] ?? 0) !== 2) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

// Usuarios activos.
$stmt = $dbh->query(
    "SELECT id, nombre, correo
     FROM usuarios
     WHERE estado = 1
     ORDER BY nombre"
);

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Equipos registrados y habilitados.
$stmt = $dbh->query(
    "SELECT
        e.id,
        e.codigo,
        e.nombre,
        c.nombre AS categoria
     FROM equipos e
     INNER JOIN categorias c ON e.categoria_id = c.id
     WHERE e.eliminado_en IS NULL
       AND c.eliminado_en IS NULL
       AND e.estado = 'Disponible'
     ORDER BY e.nombre"
);

$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configuración de anticipación mínima.
$stmt = $dbh->prepare(
    "SELECT anticipacion_min_horas
     FROM configuracion
     WHERE id = 1"
);

$stmt->execute();

$anticipacion = $stmt->fetchColumn();

if ($anticipacion === false) {
    http_response_code(500);
    exit('No se ha configurado la anticipación mínima.');
}

$errores = $_SESSION['prestamo_errores'] ?? [];
$anterior = $_SESSION['prestamo_anterior'] ?? [];

unset(
    $_SESSION['prestamo_errores'],
    $_SESSION['prestamo_anterior']
);

$equiposSeleccionados = array_map(
    'strval',
    is_array($anterior['equipos'] ?? null)
        ? $anterior['equipos']
        : []
);

$titulo = 'Nueva solicitud de préstamo';
$mostrarMenu = true;

require_once __DIR__ . '/../includes/header.php';
?>

<main class="container py-4">

    <div class="mb-4">
        <h2 class="fw-bold">Nueva solicitud de préstamo</h2>
        <p class="text-muted">
            Complete los datos de la solicitud.
        </p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">

            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="alert alert-info">
                Las solicitudes deben realizarse con al menos
                <strong>
                    <?= e((string) $anticipacion) ?> horas
                </strong>
                de anticipación respecto a la fecha de inicio.
            </div>

            <form action="/actions/prestamo_guardar.php" method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>">

                <div class="mb-3">
                    <label for="usuario_id" class="form-label">
                        Usuario solicitante
                    </label>

                    <select
                        id="usuario_id"
                        name="usuario_id"
                        class="form-select"
                        required>

                        <option value="">Seleccione un usuario</option>

                        <?php foreach ($usuarios as $usuario): ?>
                            <option
                                value="<?= e((string) $usuario['id']) ?>"
                                <?= (string) ($anterior['usuario_id'] ?? '') ===
                                    (string) $usuario['id'] ? 'selected' : '' ?>>

                                <?= e($usuario['nombre']) ?>
                                (<?= e($usuario['correo']) ?>)

                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="mb-3">

                    <label for="equipos" class="form-label">
                        Equipos solicitados
                    </label>

                    <select
                        id="equipos"
                        name="equipos[]"
                        class="form-select"
                        multiple
                        size="8"
                        required>

                        <?php foreach ($equipos as $equipo): ?>

                            <option
                                value="<?= e((string) $equipo['id']) ?>"
                                <?= in_array(
                                    (string) $equipo['id'],
                                    $equiposSeleccionados,
                                    true
                                ) ? 'selected' : '' ?>>

                                <?= e($equipo['codigo']) ?>
                                —
                                <?= e($equipo['nombre']) ?>
                                (<?= e($equipo['categoria']) ?>)

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <div class="form-text">
                        Mantenga presionada la tecla Ctrl para seleccionar
                        varios equipos.
                    </div>

                </div>

                <div class="row g-3 mb-4">

                    <div class="col-md-6">
                        <label for="fecha_inicio" class="form-label">
                            Fecha y hora de inicio
                        </label>

                        <input
                            type="datetime-local"
                            class="form-control"
                            id="fecha_inicio"
                            name="fecha_inicio"
                            value="<?= e($anterior['fecha_inicio'] ?? '') ?>"
                            required>
                    </div>

                    <div class="col-md-6">
                        <label for="fecha_fin" class="form-label">
                            Fecha y hora de fin prevista
                        </label>

                        <input
                            type="datetime-local"
                            class="form-control"
                            id="fecha_fin"
                            name="fecha_fin"
                            value="<?= e($anterior['fecha_fin'] ?? '') ?>"
                            required>
                    </div>

                </div>

                <div class="d-flex justify-content-end gap-2">

                    <a href="/prestamos.php" class="btn btn-outline-secondary">
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        <?= empty($equipos) || empty($usuarios) ? 'disabled' : '' ?>>
                        Registrar solicitud
                    </button>

                </div>

            </form>

        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>