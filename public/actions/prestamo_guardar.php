<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/database.php';

requireAuth();

// Solo operadores.
if ((int) ($_SESSION['rol_id'] ?? 0) !== 2) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

$token = $_POST['csrf_token'] ?? null;

if (!is_string($token) || !validarCsrf($token)) {
    http_response_code(403);
    exit('Solicitud no autorizada.');
}

// Comprobar los tipos recibidos.
if (
    !is_string($_POST['usuario_id'] ?? null) ||
    !is_string($_POST['fecha_inicio'] ?? null) ||
    !is_string($_POST['fecha_fin'] ?? null) ||
    !is_array($_POST['equipos'] ?? null)
) {
    http_response_code(400);
    exit('Datos inválidos.');
}

$usuarioRecibido = $_POST['usuario_id'];
$inicioRecibido = $_POST['fecha_inicio'];
$finRecibido = $_POST['fecha_fin'];
$equiposRecibidos = $_POST['equipos'];

$errores = [];

// Devolver errores al formulario mediante sesión.
function regresarPrestamo(
    array $errores,
    array $datos
): void {
    $_SESSION['prestamo_errores'] = $errores;
    $_SESSION['prestamo_anterior'] = $datos;

    header('Location: /prestamos_crear.php');
    exit;
}

// Validar usuario.
$usuarioId = filter_var(
    $usuarioRecibido,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($usuarioId === false) {
    $errores[] = 'Seleccione un usuario válido.';
}

// Validar cantidad y tipos de equipos.
if (
    count($equiposRecibidos) === 0 ||
    count($equiposRecibidos) > 100
) {
    $errores[] = 'Seleccione entre 1 y 100 equipos.';
}

$equiposIds = [];

foreach ($equiposRecibidos as $valor) {

    if (!is_string($valor)) {
        $errores[] = 'La selección de equipos es inválida.';
        break;
    }

    $equipoId = filter_var(
        $valor,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($equipoId === false) {
        $errores[] = 'La selección contiene un equipo inválido.';
        break;
    }

    $equiposIds[] = $equipoId;
}

// Evitar equipos repetidos.
if (count($equiposIds) !== count(array_unique($equiposIds))) {
    $errores[] = 'No se puede seleccionar un equipo más de una vez.';
}

// Orden fijo para bloquear filas y reducir conflictos.
$equiposIds = array_values(array_unique($equiposIds));
sort($equiposIds, SORT_NUMERIC);

// Interpretar las fechas en la zona horaria de Costa Rica.
$zonaHoraria = new DateTimeZone('America/Costa_Rica');

function leerFechaPrestamo(
    string $valor,
    DateTimeZone $zona
): ?DateTimeImmutable {

    $fecha = DateTimeImmutable::createFromFormat(
        '!Y-m-d\TH:i',
        $valor,
        $zona
    );

    if (
        $fecha === false ||
        $fecha->format('Y-m-d\TH:i') !== $valor
    ) {
        return null;
    }

    return $fecha;
}

$inicio = leerFechaPrestamo($inicioRecibido, $zonaHoraria);
$fin = leerFechaPrestamo($finRecibido, $zonaHoraria);

if ($inicio === null || $fin === null) {
    $errores[] = 'Ingrese fechas y horas válidas.';
} elseif ($fin <= $inicio) {
    $errores[] = 'La fecha final debe ser posterior a la inicial.';
}

// Datos que conservaremos si hay errores.
$datosAnteriores = [
    'usuario_id' => $usuarioRecibido,
    'equipos' => $equiposRecibidos,
    'fecha_inicio' => $inicioRecibido,
    'fecha_fin' => $finRecibido
];

if (!empty($errores)) {
    regresarPrestamo($errores, $datosAnteriores);
}

try {

    $dbh->beginTransaction();

    // Consultar la regla especial de anticipación.
    $stmt = $dbh->prepare(
        "SELECT anticipacion_min_horas
         FROM configuracion
         WHERE id = 1"
    );

    $stmt->execute();

    $anticipacion = $stmt->fetchColumn();

    if (
        $anticipacion === false ||
        filter_var(
            $anticipacion,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        ) === false
    ) {
        throw new RuntimeException(
            'La configuración de anticipación mínima no es válida.'
        );
    }

    // Hora actual del servidor en Costa Rica.
    $ahora = new DateTimeImmutable('now', $zonaHoraria);

    // Regla 7: el inicio no puede estar en el pasado
    // y debe cumplir M horas de anticipación.
    $inicioMinimo = $ahora->modify(
        '+' . (int) $anticipacion . ' hours'
    );

    if ($inicio < $ahora) {
        $errores[] = 'No se permiten préstamos con inicio en el pasado.';
    } elseif ($inicio < $inicioMinimo) {
        $errores[] =
            'La solicitud debe realizarse con al menos ' .
            (int) $anticipacion .
            ' horas de anticipación.';
    }

    if (!empty($errores)) {
        $dbh->rollBack();
        regresarPrestamo($errores, $datosAnteriores);
    }

    // Verificar que el usuario solicitante esté activo.
    $stmt = $dbh->prepare(
        "SELECT id
         FROM usuarios
         WHERE id = :id AND estado = 1"
    );

    $stmt->execute([':id' => $usuarioId]);

    if (!$stmt->fetchColumn()) {
        $dbh->rollBack();

        regresarPrestamo(
            ['El usuario seleccionado no está activo.'],
            $datosAnteriores
        );
    }

    // Construir parámetros para los equipos seleccionados.
    $marcadores = [];
    $parametrosEquipos = [];

    foreach ($equiposIds as $indice => $equipoId) {
        $clave = ':equipo_' . $indice;
        $marcadores[] = $clave;
        $parametrosEquipos[$clave] = $equipoId;
    }

    $listaMarcadores = implode(', ', $marcadores);

    // Bloquear filas de equipos antes de comprobar traslapes.
    // Todas las solicitudes de este módulo utilizan este mismo orden.
    $stmt = $dbh->prepare(
        "SELECT e.id
         FROM equipos e
         INNER JOIN categorias c ON c.id = e.categoria_id
         WHERE e.id IN ($listaMarcadores)
           AND e.eliminado_en IS NULL
           AND c.eliminado_en IS NULL
           AND e.estado = 'Disponible'
         ORDER BY e.id
         FOR UPDATE"
    );

    foreach ($parametrosEquipos as $clave => $valor) {
        $stmt->bindValue($clave, $valor, PDO::PARAM_INT);
    }

    $stmt->execute();

    $equiposValidos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($equiposValidos) !== count($equiposIds)) {
        $dbh->rollBack();

        regresarPrestamo(
            ['Uno o varios equipos no están disponibles.'],
            $datosAnteriores
        );
    }

    // Consultar préstamos activos con fechas traslapadas.
    // aqui se aplica la regla solicitada AND p.fecha_fin > :fecha_inicio 
    $sqlTraslapes =
        "SELECT
            pd.equipo_id
         FROM prestamo_detalle pd
         INNER JOIN prestamos p
             ON p.id = pd.prestamo_id
         WHERE pd.equipo_id IN ($listaMarcadores)
           AND p.estado IN ('solicitado', 'aprobado', 'entregado')
           AND p.fecha_inicio < :fecha_fin
           AND p.fecha_fin > :fecha_inicio 
         LIMIT 1 FOR UPDATE";

    $stmt = $dbh->prepare($sqlTraslapes);

    foreach ($parametrosEquipos as $clave => $valor) {
        $stmt->bindValue($clave, $valor, PDO::PARAM_INT);
    }

    $stmt->bindValue(
        ':fecha_inicio',
        $inicio->format('Y-m-d H:i:s')
    );

    $stmt->bindValue(
        ':fecha_fin',
        $fin->format('Y-m-d H:i:s')
    );

    $stmt->execute();

    if ($stmt->fetchColumn() !== false) {
        $dbh->rollBack();

        regresarPrestamo(
            ['Uno o varios equipos ya tienen un préstamo en ese horario.'],
            $datosAnteriores
        );
    }

    // Registrar la cabecera del préstamo.
    $stmt = $dbh->prepare(
        "INSERT INTO prestamos
            (usuario_id, operador_id, estado, fecha_inicio, fecha_fin)
         VALUES
            (:usuario_id, :operador_id, 'solicitado',
             :fecha_inicio, :fecha_fin)"
    );

    $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':operador_id' => (int) $_SESSION['usuario_id'],
        ':fecha_inicio' => $inicio->format('Y-m-d H:i:s'),
        ':fecha_fin' => $fin->format('Y-m-d H:i:s')
    ]);

    $prestamoId = (int) $dbh->lastInsertId();

    // Registrar todos los equipos del préstamo.
    $stmt = $dbh->prepare(
        "INSERT INTO prestamo_detalle
            (prestamo_id, equipo_id)
         VALUES
            (:prestamo_id, :equipo_id)"
    );

    foreach ($equiposIds as $equipoId) {
        $stmt->execute([
            ':prestamo_id' => $prestamoId,
            ':equipo_id' => $equipoId
        ]);
    }

    // Confirmar ambas operaciones.
    $dbh->commit();

    $_SESSION['prestamo_exito'] =
        'Solicitud de préstamo registrada correctamente.';

    header('Location: /prestamos.php');
    exit;

} catch (Throwable $e) {

    if ($dbh->inTransaction()) {
        $dbh->rollBack();
    }

    error_log($e->getMessage());

    regresarPrestamo(
        ['No fue posible registrar la solicitud.'],
        $datosAnteriores
    );
}