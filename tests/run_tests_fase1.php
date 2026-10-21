<?php

declare(strict_types=1);

/**
 * Suite de pruebas automatizadas para Fase 1 — Base de Datos Relacional.
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Ejecuta database/tests/validacion.sql en un esquema temporal aislado.
 * Cobertura requerida: 18 pruebas canónicas de integridad relacional.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;

class Phase1TestSuite
{
    private int $total = 0;
    private int $passed = 0;
    private int $failed = 0;
    /** @var array<int, array{name: string, expected: string, obtained: string, status: string}> */
    private array $results = [];

    public function run(): void
    {
        echo "============================================================\n";
        echo "SUITE DE PRUEBAS AUTOMATIZADAS — FASE 1: BASE DE DATOS\n";
        echo "Escuela Oficial Rural Mixta Lo de Fuentes\n";
        echo "============================================================\n\n";

        $root = dirname(__DIR__);
        $mysqlBinary = $this->findMysqlBinary();

        if ($mysqlBinary === null) {
            echo "[FALLO] Binario de mysql.exe no encontrado en el sistema.\n";
            exit(1);
        }

        $dbHost = (string) Config::get('database.connections.mysql.host', '127.0.0.1');
        $dbPort = (int) Config::get('database.connections.mysql.port', 3306);
        $dbUser = (string) Config::get('database.connections.mysql.username', 'Administrador');
        $dbPass = (string) Config::get('database.connections.mysql.password', 'Administrador');
        $tempDb = 'escuela_fase1_standalone_test_' . uniqid();

        $schemaFile = $root . '/database/schema.sql';
        $seedFile = file_exists($root . '/database/tests/seed_fase1.sql')
            ? $root . '/database/tests/seed_fase1.sql'
            : $root . '/database/seed.sql';
        $valFile = $root . '/database/tests/validacion.sql';

        $passParam = $dbPass !== '' ? ' -p' . escapeshellarg($dbPass) : '';
        $baseCmd = sprintf(
            '"%s" -h %s -P %d -u %s%s',
            $mysqlBinary,
            escapeshellarg($dbHost),
            $dbPort,
            escapeshellarg($dbUser),
            $passParam
        );

        try {
            // 1. Crear base de datos temporal aislada
            shell_exec($baseCmd . ' -e "DROP DATABASE IF EXISTS ' . $tempDb . '; CREATE DATABASE ' . $tempDb . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;" 2>&1');

            // 2. Cargar esquema DDL
            shell_exec($baseCmd . ' ' . $tempDb . ' < "' . $schemaFile . '" 2>&1');

            // 3. Cargar datos iniciales DML
            shell_exec($baseCmd . ' ' . $tempDb . ' < "' . $seedFile . '" 2>&1');

            // 4. Ejecutar validación
            $output = (string) shell_exec($baseCmd . ' ' . $tempDb . ' < "' . $valFile . '" 2>&1');

            // 5. Parsear resultados de la tabla de salida
            $lines = explode("\n", $output);
            $inTable = false;
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, 'mysql:') || str_starts_with($line, 'Warning:')) {
                    continue;
                }
                if (str_starts_with($line, 'prueba') && str_contains($line, 'resultado')) {
                    $inTable = true;
                    continue;
                }
                if (str_starts_with($line, 'pruebas_ejecutadas')) {
                    $inTable = false;
                    continue;
                }
                if ($inTable && str_contains($line, "\t")) {
                    $parts = explode("\t", $line);
                    if (count($parts) >= 4) {
                        $pName = trim($parts[0]);
                        $pExp = trim($parts[1]);
                        $pObs = trim($parts[2]);
                        $pRes = trim($parts[3]);
                        $isOk = ($pRes === 'OK');

                        $this->total++;
                        if ($isOk) {
                            $this->passed++;
                        } else {
                            $this->failed++;
                        }

                        $this->results[] = [
                            'name' => $pName,
                            'expected' => $pExp,
                            'obtained' => $pObs,
                            'status' => $pRes,
                        ];

                        printf("[%s] T1-%02d: %s (Esperado: %s | Obtenido: %s)\n", $pRes, $this->total, $pName, $pExp, $pObs);
                    }
                }
            }

            // Si por alguna razón la tabla no fue parseada pero el total está presente
            if ($this->total === 0 && preg_match('/pruebas_ejecutadas\s+pruebas_exitosas\s+pruebas_fallidas\s+18\s+18\s+0/i', $output)) {
                $this->total = 18;
                $this->passed = 18;
                $this->failed = 0;
            }

        } finally {
            // Limpieza de la base de datos temporal
            shell_exec($baseCmd . ' -e "DROP DATABASE IF EXISTS ' . $tempDb . ';" 2>&1');
        }

        $this->printSummary();
    }

    private function findMysqlBinary(): ?string
    {
        $candidates = [
            'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe',
            'C:\xampp\mysql\bin\mysql.exe',
        ];
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        $output = @shell_exec('where mysql 2>nul');
        if (!empty($output)) {
            $firstLine = trim(explode("\n", trim($output))[0]);
            if (file_exists($firstLine)) {
                return $firstLine;
            }
        }

        return null;
    }

    private function printSummary(): void
    {
        echo "\n============================================================\n";
        echo "RESUMEN DE PRUEBAS DE FASE 1: BASE DE DATOS\n";
        echo "============================================================\n";
        printf("Total ejecutadas : %d\n", $this->total);
        printf("Exitosas (OK)    : %d\n", $this->passed);
        printf("Fallidas (FALLO) : %d\n", $this->failed);
        echo "============================================================\n";

        if ($this->failed === 0 && $this->passed === 18) {
            echo "RESULTADO: TODAS LAS PRUEBAS DE FASE 1 SUPERADAS SATISFACTORIAMENTE (100% GREEN).\n";
        } else {
            echo "RESULTADO: EXISTEN PRUEBAS FALLIDAS O COBERTURA INCOMPLETA.\n";
            exit(1);
        }
    }
}

$suite = new Phase1TestSuite();
$suite->run();
