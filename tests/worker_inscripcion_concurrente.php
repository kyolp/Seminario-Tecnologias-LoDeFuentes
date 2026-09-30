<?php

declare(strict_types=1);

/**
 * Worker CLI para prueba de concurrencia real del último cupo (T6-21).
 * Escuela Oficial Rural Mixta Lo de Fuentes
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

$options = getopt('', [
    'seccion:',
    'estudiante_nom:',
    'estudiante_ape:',
    'encargado_nom:',
    'encargado_ape:',
    'parentesco:',
    'ready:',
    'barrier:',
    'out:',
]);

$idSeccion = (int)($options['seccion'] ?? 0);
$estNom = (string)($options['estudiante_nom'] ?? 'Estudiante');
$estApe = (string)($options['estudiante_ape'] ?? 'Concurrente');
$encNom = (string)($options['encargado_nom'] ?? 'Encargado');
$encApe = (string)($options['encargado_ape'] ?? 'Concurrente');
$parentesco = (string)($options['parentesco'] ?? 'PADRE');
$readyFile = (string)($options['ready'] ?? '');
$barrierFile = (string)($options['barrier'] ?? '');
$outFile = (string)($options['out'] ?? '');

if (!$idSeccion || !$readyFile || !$barrierFile || !$outFile) {
    file_put_contents($outFile, json_encode([
        'success' => false,
        'message' => 'Parámetros CLI incompletos en worker de inscripción.',
    ]));
    exit(1);
}

try {
    // 1. Conexión PDO propia e independiente
    $pdo = \App\Core\Database::createConnection();
    $service = new \App\Services\InscripcionService($pdo);

    // 2. Señalizar que el proceso está listo y conectado
    file_put_contents($readyFile, 'READY');

    // 3. Esperar barrera de sincronización (máximo 6 segundos)
    $timeout = microtime(true) + 6.0;
    while (!file_exists($barrierFile)) {
        if (microtime(true) > $timeout) {
            file_put_contents($outFile, json_encode([
                'success' => false,
                'message' => 'Timeout esperando barrera de sincronización en inscripción.',
            ]));
            exit(2);
        }
        usleep(500); // 0.5 ms
    }

    // 4. Intentar realizar la inscripción simultáneamente
    $input = [
        'estudiante_nombres' => $estNom,
        'estudiante_apellidos' => $estApe,
        'encargado_nombres' => $encNom,
        'encargado_apellidos' => $encApe,
        'encargado_telefono' => '5555-9999',
        'encargado_correo' => strtolower($encNom . '.' . bin2hex(random_bytes(2)) . '@example.invalid'),
        'parentesco' => $parentesco,
        'id_seccion' => $idSeccion,
    ];

    $result = $service->procesarInscripcionPublica($input);

    // 5. Guardar resultado JSON
    file_put_contents($outFile, json_encode($result));
    exit(0);

} catch (\Throwable $e) {
    file_put_contents($outFile, json_encode([
        'success' => false,
        'message' => 'Excepción en worker: ' . $e->getMessage(),
        'sql_leaked' => str_contains($e->getMessage(), 'SQLSTATE'),
    ]));
    exit(3);
}
