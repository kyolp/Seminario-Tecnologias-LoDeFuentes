<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Docente;
use App\Repositories\DocenteRepository;
use App\Repositories\DocenteRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\UserRepositoryInterface;
use PDO;
use Throwable;

class DocenteService
{
    private DocenteRepositoryInterface $docenteRepo;
    private UserRepositoryInterface $userRepo;
    private PDO $db;

    public function __construct(
        ?DocenteRepositoryInterface $docenteRepo = null,
        ?UserRepositoryInterface $userRepo = null,
        ?PDO $db = null
    ) {
        $this->db = $db ?? Database::getConnection();
        $this->docenteRepo = $docenteRepo ?? new DocenteRepository($this->db);
        $this->userRepo = $userRepo ?? new UserRepository($this->db);
    }

    /**
     * @return array<int, Docente>
     */
    public function listarDocentes(?string $search = null): array
    {
        return $this->docenteRepo->findAll($search);
    }

    public function obtenerDocente(int $id): ?Docente
    {
        return $this->docenteRepo->findById($id);
    }

    /**
     * Crear un nuevo docente de forma atómica (Usuario + Registro Docente).
     *
     * @param array<string, mixed> $input
     * @return array{success: bool, id_docente?: int, message?: string, errors?: array<string, string>}
     */
    public function crearDocente(array $input, bool $simularFalloDocente = false): array
    {
        $errors = [];

        $nombres = trim((string)($input['nombres'] ?? ''));
        $apellidos = trim((string)($input['apellidos'] ?? ''));
        $nombreUsuario = trim((string)($input['nombre_usuario'] ?? ''));
        $correo = trim((string)($input['correo'] ?? ''));
        $password = (string)($input['password'] ?? '');
        $codigoDocente = !empty($input['codigo_docente']) ? trim((string)$input['codigo_docente']) : null;
        $fechaIngreso = !empty($input['fecha_ingreso']) ? trim((string)$input['fecha_ingreso']) : null;
        $estado = strtoupper(trim((string)($input['estado'] ?? 'ACTIVO')));

        // Validaciones obligatorias
        if ($nombres === '') {
            $errors['nombres'] = 'Los nombres del docente son obligatorios.';
        } elseif (mb_strlen($nombres) > 100) {
            $errors['nombres'] = 'Los nombres no deben exceder 100 caracteres.';
        }

        if ($apellidos === '') {
            $errors['apellidos'] = 'Los apellidos del docente son obligatorios.';
        } elseif (mb_strlen($apellidos) > 100) {
            $errors['apellidos'] = 'Los apellidos no deben exceder 100 caracteres.';
        }

        if ($nombreUsuario === '') {
            $errors['nombre_usuario'] = 'El nombre de usuario es obligatorio.';
        } elseif (mb_strlen($nombreUsuario) > 50) {
            $errors['nombre_usuario'] = 'El nombre de usuario no debe exceder 50 caracteres.';
        } elseif ($this->userRepo->findByUsername($nombreUsuario) !== null) {
            $errors['nombre_usuario'] = 'El nombre de usuario ya se encuentra registrado.';
        }

        if ($correo === '') {
            $errors['correo'] = 'El correo electrónico es obligatorio.';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errors['correo'] = 'El correo electrónico tiene un formato inválido.';
        } elseif ($this->existeCorreo($correo)) {
            $errors['correo'] = 'El correo electrónico ya se encuentra registrado.';
        }

        if ($password === '') {
            $errors['password'] = 'La contraseña es obligatoria.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'La contraseña debe contener al menos 8 caracteres.';
        }

        if ($codigoDocente !== null) {
            if (mb_strlen($codigoDocente) > 30) {
                $errors['codigo_docente'] = 'El código de docente no debe exceder 30 caracteres.';
            } elseif ($this->docenteRepo->findByCodigo($codigoDocente) !== null) {
                $errors['codigo_docente'] = 'El código de docente ya se encuentra registrado.';
            }
        }

        if ($fechaIngreso !== null && !$this->validarFecha($fechaIngreso)) {
            $errors['fecha_ingreso'] = 'La fecha de ingreso debe tener el formato AAAA-MM-DD.';
        }

        if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
            $estado = 'ACTIVO';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Existen errores de validación.'];
        }

        // Obtener rol DOCENTE
        $idRolDocente = $this->obtenerIdRolDocente();
        if ($idRolDocente === null) {
            return ['success' => false, 'message' => 'No se encontró el rol DOCENTE en el sistema.'];
        }

        // Transacción atómica
        $this->db->beginTransaction();
        try {
            // 1. Insertar usuario
            $claveHash = password_hash($password, PASSWORD_BCRYPT);
            $sqlUser = 'INSERT INTO usuarios (id_rol, nombre_usuario, correo, clave_hash, nombres, apellidos, estado)
                        VALUES (:id_rol, :nombre_usuario, :correo, :clave_hash, :nombres, :apellidos, :estado)';
            $stmtUser = $this->db->prepare($sqlUser);
            $stmtUser->bindValue(':id_rol', $idRolDocente, PDO::PARAM_INT);
            $stmtUser->bindValue(':nombre_usuario', $nombreUsuario, PDO::PARAM_STR);
            $stmtUser->bindValue(':correo', $correo, PDO::PARAM_STR);
            $stmtUser->bindValue(':clave_hash', $claveHash, PDO::PARAM_STR);
            $stmtUser->bindValue(':nombres', $nombres, PDO::PARAM_STR);
            $stmtUser->bindValue(':apellidos', $apellidos, PDO::PARAM_STR);
            $stmtUser->bindValue(':estado', $estado, PDO::PARAM_STR);
            $stmtUser->execute();

            $idUsuario = (int) $this->db->lastInsertId();

            if ($simularFalloDocente) {
                throw new \RuntimeException('Fallo forzado para validación de rollback transaccional.');
            }

            // 2. Insertar docente
            $idDocente = $this->docenteRepo->createDocente($idUsuario, $codigoDocente, $fechaIngreso, $estado);

            $this->db->commit();

            return [
                'success' => true,
                'id_docente' => $idDocente,
                'message' => 'Docente registrado exitosamente.',
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'message' => 'Error al registrar el docente: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, message?: string, errors?: array<string, string>}
     */
    public function actualizarDocente(int $idDocente, array $input): array
    {
        $docente = $this->docenteRepo->findById($idDocente);
        if (!$docente) {
            return ['success' => false, 'message' => 'Docente no encontrado.'];
        }

        $errors = [];

        $nombres = trim((string)($input['nombres'] ?? ''));
        $apellidos = trim((string)($input['apellidos'] ?? ''));
        $correo = trim((string)($input['correo'] ?? ''));
        $codigoDocente = !empty($input['codigo_docente']) ? trim((string)$input['codigo_docente']) : null;
        $fechaIngreso = !empty($input['fecha_ingreso']) ? trim((string)$input['fecha_ingreso']) : null;
        $estado = strtoupper(trim((string)($input['estado'] ?? $docente->estado)));
        $password = (string)($input['password'] ?? '');

        if ($nombres === '') {
            $errors['nombres'] = 'Los nombres son obligatorios.';
        } elseif (mb_strlen($nombres) > 100) {
            $errors['nombres'] = 'Los nombres no deben exceder 100 caracteres.';
        }

        if ($apellidos === '') {
            $errors['apellidos'] = 'Los apellidos son obligatorios.';
        } elseif (mb_strlen($apellidos) > 100) {
            $errors['apellidos'] = 'Los apellidos no deben exceder 100 caracteres.';
        }

        if ($correo === '') {
            $errors['correo'] = 'El correo electrónico es obligatorio.';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errors['correo'] = 'El correo electrónico tiene un formato inválido.';
        } elseif ($this->existeCorreo($correo, $docente->id_usuario)) {
            $errors['correo'] = 'El correo electrónico ya pertenece a otro usuario.';
        }

        if ($codigoDocente !== null) {
            if (mb_strlen($codigoDocente) > 30) {
                $errors['codigo_docente'] = 'El código no debe exceder 30 caracteres.';
            } else {
                $existente = $this->docenteRepo->findByCodigo($codigoDocente);
                if ($existente && $existente->id_docente !== $idDocente) {
                    $errors['codigo_docente'] = 'El código de docente ya se encuentra registrado.';
                }
            }
        }

        if ($fechaIngreso !== null && !$this->validarFecha($fechaIngreso)) {
            $errors['fecha_ingreso'] = 'La fecha de ingreso debe tener formato AAAA-MM-DD.';
        }

        if (!empty($password) && strlen($password) < 8) {
            $errors['password'] = 'La nueva contraseña debe contener al menos 8 caracteres.';
        }

        if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
            $estado = 'ACTIVO';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Existen errores de validación.'];
        }

        $this->db->beginTransaction();
        try {
            // Actualizar datos del usuario
            if (!empty($password)) {
                $claveHash = password_hash($password, PASSWORD_BCRYPT);
                $sqlU = 'UPDATE usuarios SET nombres = :nombres, apellidos = :apellidos, correo = :correo, clave_hash = :hash, estado = :estado WHERE id_usuario = :id';
                $stmtU = $this->db->prepare($sqlU);
                $stmtU->bindValue(':hash', $claveHash, PDO::PARAM_STR);
            } else {
                $sqlU = 'UPDATE usuarios SET nombres = :nombres, apellidos = :apellidos, correo = :correo, estado = :estado WHERE id_usuario = :id';
                $stmtU = $this->db->prepare($sqlU);
            }
            $stmtU->bindValue(':nombres', $nombres, PDO::PARAM_STR);
            $stmtU->bindValue(':apellidos', $apellidos, PDO::PARAM_STR);
            $stmtU->bindValue(':correo', $correo, PDO::PARAM_STR);
            $stmtU->bindValue(':estado', $estado, PDO::PARAM_STR);
            $stmtU->bindValue(':id', $docente->id_usuario, PDO::PARAM_INT);
            $stmtU->execute();

            // Actualizar datos del docente
            $this->docenteRepo->updateDocente($idDocente, $codigoDocente, $fechaIngreso, $estado);

            $this->db->commit();

            return ['success' => true, 'message' => 'Docente actualizado exitosamente.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Error al actualizar docente: ' . $e->getMessage()];
        }
    }

    public function cambiarEstado(int $idDocente, string $nuevoEstado): array
    {
        $docente = $this->docenteRepo->findById($idDocente);
        if (!$docente) {
            return ['success' => false, 'message' => 'Docente no encontrado.'];
        }

        $estado = strtoupper($nuevoEstado) === 'ACTIVO' ? 'ACTIVO' : 'INACTIVO';

        $this->db->beginTransaction();
        try {
            $this->docenteRepo->updateEstado($idDocente, $estado);

            // Sincronizar estado en usuarios para evitar login si está inactivo
            $sql = 'UPDATE usuarios SET estado = :estado WHERE id_usuario = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':estado', $estado, PDO::PARAM_STR);
            $stmt->bindValue(':id', $docente->id_usuario, PDO::PARAM_INT);
            $stmt->execute();

            $this->db->commit();

            return ['success' => true, 'message' => "Estado del docente modificado a {$estado}."];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Error al modificar estado: ' . $e->getMessage()];
        }
    }

    public function eliminarDocente(int $idDocente): array
    {
        $docente = $this->docenteRepo->findById($idDocente);
        if (!$docente) {
            return ['success' => false, 'message' => 'Docente no encontrado.'];
        }

        if ($this->docenteRepo->hasAsignaciones($idDocente)) {
            return [
                'success' => false,
                'message' => 'No es posible eliminar el docente porque cuenta con asignaciones históricas o activas. Se recomienda desactivarlo.',
            ];
        }

        $this->db->beginTransaction();
        try {
            $this->docenteRepo->delete($idDocente);

            $sql = 'DELETE FROM usuarios WHERE id_usuario = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $docente->id_usuario, PDO::PARAM_INT);
            $stmt->execute();

            $this->db->commit();

            return ['success' => true, 'message' => 'Docente eliminado correctamente.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Error de integridad referencial al eliminar docente.'];
        }
    }

    private function existeCorreo(string $correo, ?int $excluirIdUsuario = null): bool
    {
        $sql = 'SELECT 1 FROM usuarios WHERE correo = :correo';
        if ($excluirIdUsuario !== null) {
            $sql .= ' AND id_usuario != :excluirId';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':correo', trim($correo), PDO::PARAM_STR);
        if ($excluirIdUsuario !== null) {
            $stmt->bindValue(':excluirId', $excluirIdUsuario, PDO::PARAM_INT);
        }
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    private function obtenerIdRolDocente(): ?int
    {
        $sql = "SELECT id_rol FROM roles WHERE nombre = 'DOCENTE' LIMIT 1";
        $stmt = $this->db->query($sql);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    private function validarFecha(string $fecha): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
}
