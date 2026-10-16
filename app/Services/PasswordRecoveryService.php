<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\TokenRecuperacion;
use App\Models\User;
use App\Repositories\TokenRecuperacionRepository;
use App\Repositories\TokenRecuperacionRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\UserRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use Throwable;

/**
 * Servicio de Negocio para Recuperación de Contraseña — Fase 13 (RF-03).
 *
 * Implementa el flujo seguro de solicitud, generación de token criptográfico,
 * validación de vigencia y uso único, actualización de credenciales e invalidación de tokens.
 */
class PasswordRecoveryService
{
    private PDO $db;
    private TokenRecuperacionRepositoryInterface $tokenRepo;
    private UserRepositoryInterface $userRepo;

    public function __construct(
        ?TokenRecuperacionRepositoryInterface $tokenRepo = null,
        ?UserRepositoryInterface $userRepo = null,
        ?PDO $db = null
    ) {
        $this->db = $db ?? Database::getConnection();
        $this->tokenRepo = $tokenRepo ?? new TokenRecuperacionRepository($this->db);
        $this->userRepo = $userRepo ?? new UserRepository($this->db);
    }

    /**
     * Procesar solicitud de recuperación previniendo enumeración de cuentas de usuario (RF-03).
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     errors?: array<string, string>,
     *     enlace_simulado?: string,
     *     token_plano?: string,
     *     id_usuario?: int
     * }
     */
    public function solicitarRecuperacion(string $correoOIdentificador): array
    {
        $identificador = trim($correoOIdentificador);

        if ($identificador === '') {
            return [
                'success' => false,
                'message' => 'Debe ingresar su correo electrónico o nombre de usuario.',
                'errors' => ['correo' => 'El campo es obligatorio.'],
            ];
        }

        // Buscar usuario por correo electrónico o por nombre de usuario
        $user = null;
        if (filter_var($identificador, FILTER_VALIDATE_EMAIL) !== false) {
            $user = $this->userRepo->findByEmail($identificador);
        } else {
            // Permitir búsqueda por nombre de usuario si no tiene formato email
            $user = $this->userRepo->findByUsername($identificador);
        }

        $mensajeNeutral = 'Si los datos ingresados corresponden a una cuenta activa registrada, se ha enviado un enlace para restablecer la contraseña.';

        // Mitigación estricta de User Enumeration: respuesta idéntica si no existe o está inactivo
        if ($user === null || !$user->isActive()) {
            Logger::info('Solicitud de recuperación para cuenta inexistente o inactiva.', [
                'identificador_hash' => hash('sha256', strtolower($identificador)),
            ]);

            return [
                'success' => true,
                'message' => $mensajeNeutral,
            ];
        }

        try {
            // Generación de token criptográficamente seguro (32 bytes = 64 hex chars)
            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);

            // Vigencia oficial: 60 minutos
            $fechaExpiracion = (new DateTimeImmutable())
                ->modify('+' . TokenRecuperacion::VIGENCIA_MINUTOS . ' minutes')
                ->format('Y-m-d H:i:s.u');

            $this->db->beginTransaction();

            // Revocar tokens anteriores que aún estén activos para este usuario
            $this->tokenRepo->revocarTokensActivosDeUsuario($user->id_usuario);

            // Persistir nuevo token con su hash SHA-256
            $this->tokenRepo->crear($user->id_usuario, $tokenHash, $fechaExpiracion);

            $this->db->commit();

            Logger::info('Token de recuperación generado exitosamente.', [
                'id_usuario' => $user->id_usuario,
            ]);

            $enlaceSimulado = '/restablecer-contrasena?token=' . $rawToken;

            return [
                'success' => true,
                'message' => $mensajeNeutral,
                'enlace_simulado' => $enlaceSimulado,
                'token_plano' => $rawToken,
                'id_usuario' => $user->id_usuario,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            Logger::error('Fallo al generar token de recuperación: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Ocurrió un error inesperado al procesar la solicitud. Por favor intente más tarde.',
            ];
        }
    }

    /**
     * Validar la existencia, vigencia, estado y unicidad de un token de recuperación.
     *
     * @return array{
     *     valido: bool,
     *     error: ?string,
     *     token?: TokenRecuperacion,
     *     user?: User
     * }
     */
    public function validarToken(string $token): array
    {
        $tokenLimpio = trim($token);

        // El token debe ser exactamente una cadena hexadecimal de 64 caracteres
        if (!preg_match('/^[a-f0-9]{64}$/i', $tokenLimpio)) {
            return [
                'valido' => false,
                'error' => 'El enlace de recuperación es inválido o tiene un formato incorrecto.',
            ];
        }

        $tokenHash = hash('sha256', $tokenLimpio);
        $record = $this->tokenRepo->findByTokenHash($tokenHash);

        if ($record === null) {
            return [
                'valido' => false,
                'error' => 'El enlace de recuperación no es válido o no existe.',
            ];
        }

        if ($record->isUsado()) {
            return [
                'valido' => false,
                'error' => 'Este enlace de recuperación ya fue utilizado previamente. Solicite uno nuevo.',
            ];
        }

        if ($record->isRevocado()) {
            return [
                'valido' => false,
                'error' => 'Este enlace de recuperación ha sido revocado. Solicite uno nuevo.',
            ];
        }

        if ($record->isExpirado()) {
            return [
                'valido' => false,
                'error' => 'El enlace de recuperación ha expirado. Por seguridad, solicite un nuevo enlace.',
            ];
        }

        $user = $this->userRepo->findById($record->id_usuario);
        if ($user === null || !$user->isActive()) {
            return [
                'valido' => false,
                'error' => 'La cuenta de usuario asociada no existe o se encuentra inactiva.',
            ];
        }

        return [
            'valido' => true,
            'error' => null,
            'token' => $record,
            'user' => $user,
        ];
    }

    /**
     * Restablecer la contraseña de un usuario mediante su token validado (RF-03).
     *
     * Aplica transacción atómica con bloqueo pesimista, hashing seguro con BCrypt
     * e invalidación inmediata del token de un solo uso.
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     errors?: array<string, string>
     * }
     */
    public function restablecerContrasena(string $token, string $password, string $passwordConfirmation): array
    {
        $tokenLimpio = trim($token);

        if (!preg_match('/^[a-f0-9]{64}$/i', $tokenLimpio)) {
            return [
                'success' => false,
                'message' => 'El enlace de recuperación es inválido.',
                'errors' => ['token' => 'Token con formato incorrecto.'],
            ];
        }

        $errors = [];

        // Validaciones de la nueva contraseña
        if ($password === '') {
            $errors['password'] = 'La nueva contraseña es obligatoria.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'La nueva contraseña debe contener al menos 8 caracteres.';
        }

        if ($password !== $passwordConfirmation) {
            $errors['password_confirmation'] = 'La confirmación de la contraseña no coincide.';
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Existen errores de validación en la nueva contraseña.',
                'errors' => $errors,
            ];
        }

        $this->db->beginTransaction();

        try {
            $tokenHash = hash('sha256', $tokenLimpio);

            // Bloqueo pesimista de fila en BD (FOR UPDATE)
            $tokenRecord = $this->tokenRepo->findForUpdate($tokenHash);

            if ($tokenRecord === null) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'El enlace de recuperación no es válido o no existe.',
                ];
            }

            if (!$tokenRecord->isValido()) {
                $this->db->rollBack();
                $motivo = $tokenRecord->isUsado() ? 'ya fue utilizado' : ($tokenRecord->isExpirado() ? 'ha expirado' : 'ha sido revocado');
                return [
                    'success' => false,
                    'message' => "El enlace de recuperación {$motivo}. Por favor solicite uno nuevo.",
                ];
            }

            $user = $this->userRepo->findById($tokenRecord->id_usuario);
            if ($user === null || !$user->isActive()) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'La cuenta de usuario no está activa.',
                ];
            }

            // Hashing criptográfico obligatorio con BCrypt
            $nuevoHash = password_hash($password, PASSWORD_BCRYPT);

            // 1. Actualizar contraseña del usuario
            $this->userRepo->updatePassword($user->id_usuario, $nuevoHash);

            // 2. Marcar token utilizado inmediatamente como USADO (un solo uso)
            $this->tokenRepo->marcarComoUsado($tokenRecord->id_token_recuperacion);

            // 3. Revocar cualquier otro token que pudiera estar activo para este usuario
            $this->tokenRepo->revocarTokensActivosDeUsuario($user->id_usuario);

            $this->db->commit();

            Logger::info('Contraseña restablecida exitosamente vía token.', [
                'id_usuario' => $user->id_usuario,
            ]);

            return [
                'success' => true,
                'message' => 'Contraseña restablecida exitosamente. Ya puedes iniciar sesión con tu nueva contraseña.',
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            Logger::error('Error al restablecer contraseña: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Ocurrió un error inesperado al restablecer la contraseña.',
            ];
        }
    }
}
