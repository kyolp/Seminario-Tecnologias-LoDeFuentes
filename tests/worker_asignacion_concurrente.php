<?php
declare(strict_types=1);

/**
 * Worker CLI para prueba de concurrencia real (T5-26).
 *
 * Escuela Oficial Rural Mixta Lo de Fuentes
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

// Parse CLI options
$options = getopt('', [
    'docente:',
    'seccion:',
    'aula:',
    'usuario:',
    'fecha_inicio:',
    'ready:',
    'barrier:',
    'out:',
]);

$idDocente = (int)($options['docente'] ?? 0);
$idSeccion = (int)($options['seccion'] ?? 0);
$idAula = (int)($options['aula'] ?? 0);
$idUsuario = (int)($options['usuario'] ?? 1);
$fechaInicio = (string)($options['fecha_inicio'] ?? '2026-03-01');
$readyFile = (string)($options['ready'] ?? '');
$barrierFile = (string)($options['barrier'] ?? '');
$outFile = (string)($options['out'] ?? '');

if (!$idDocente || !$idSeccion || !$idAula || !$readyFile || !$barrierFile || !$outFile) {
    file_put_contents($outFile, json_encode([
        'success' => false,
        'message' => 'Parámetros CLI incompletos en worker.',
    ]));
    exit(1);
}

try {
    // 1. Conexión PDO propia e independiente
    $pdo = \App\Core\Database::createConnection();

    $asignacionRepo = new \App\Repositories\AsignacionRepository($pdo);
    $service = new \App\Services\AsignacionService($pdo, $asignacionRepo);

    // 2. Señalizar que el proceso está listo y conectado
    file_put_contents($readyFile, 'READY');

    // 3. Esperar barrera de sincronización (máximo 5 segundos)
    $timeout = microtime(true) + 5.0;
    while (!file_exists($barrierFile)) {
        if (microtime(true) > $timeout) {
            file_put_contents($outFile, json_encode([
                'success' => false,
                'message' => 'Timeout esperando barrera de sincronización.',
            ]));
            exit(2);
        }
        usleep(500); // 0.5 ms
    }

    // 4. Intentar crear la asignación simultáneamente
    $input = [
        'id_docente' => $idDocente,
        'id_seccion' => $idSeccion,
        'id_aula' => $idAula,
        'fecha_inicio' => $fechaInicio,
        'estado' => 'ACTIVA',
    ];

    $result = $service->crearAsignacion($input, $idUsuario);

    // 5. Guardar resultado
    file_put_contents($outFile, json_encode($result));
    exit(0);

} catch (\Throwable $e) {
    file_put_contents($outFile, json_encode([
        'success' => false,
        'message' => 'Excepción no capturada en worker: ' . $e->getMessage(),
        'exception' => get_class($e),
        'sql_leaked' => str_contains($e->getMessage(), 'SQLSTATE'),
    ]));
    exit(3);
}
