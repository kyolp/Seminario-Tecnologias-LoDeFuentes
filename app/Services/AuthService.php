<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Core\Session;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\UserRepositoryInterface;

/**
 * Servicio centralizado de autenticación y autorización.
 * Maneja el ciclo de vida de credenciales, sesiones y verificación en tiempo real de roles y estado.
 */
class AuthService
{
    private UserRepositoryInterface $userRepository;
    private ?string $lastError = null;
    private ?User $cachedUser = null;

    public function __construct(?UserRepositoryInterface $userRepository = null)
    {
        $this->userRepository = $userRepository ?? new UserRepository();
    }

    /**
     * Intentar iniciar sesión con las credenciales provistas.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function attempt(string $username, string $password): bool
    {
        $username = trim($username);
        $this->lastError = null;

        if ($username === '' || $password === '') {
            $this->lastError = 'Credenciales inválidas.';
            return false;
        }

        $user = $this->userRepository->findByUsername($username);

        // Si el usuario no existe
        if ($user === null) {
            Logger::warning('Intento de inicio de sesión fallido: usuario no encontrado.', [
                'usuario' => $username,
            ]);
            $this->lastError = 'Credenciales inválidas.';
            return false;
        }

        // Si el usuario se encuentra inactivo o bloqueado
        if (!$user->isActive()) {
            Logger::warning('Intento de inicio de sesión rechazado: usuario inactivo.', [
                'usuario' => $username,
                'estado' => $user->estado,
            ]);
            $this->lastError = 'Credenciales inválidas.';
            return false;
        }

        // Verificación criptográfica con password_verify
        if (!password_verify($password, $user->clave_hash)) {
            Logger::warning('Intento de inicio de sesión fallido: contraseña incorrecta.', [
                'usuario' => $username,
            ]);
            $this->lastError = 'Credenciales inválidas.';
            return false;
        }

        // Credenciales correctas: regenerar ID de sesión contra Session Fixation
        Session::regenerate(true);

        // Registrar datos de autenticación en sesión
        Session::set('auth_user_id', $user->id_usuario);
        Session::set('auth_role', $user->rol_nombre);
        Session::set('auth_username', $user->nombre_usuario);
        Session::set('auth_logged_at', time());

        // Actualizar marca de último acceso
        $this->userRepository->updateLastAccess($user->id_usuario);

        $this->cachedUser = $user;

        Logger::info('Inicio de sesión exitoso.', [
            'id_usuario' => $user->id_usuario,
            'usuario' => $user->nombre_usuario,
            'rol' => $user->rol_nombre,
        ]);

        return true;
    }

    /**
     * Obtener el usuario actualmente autenticado verificando su estado y rol en base de datos.
     * Si el usuario fue desactivado o eliminado en BD, la sesión se invalida de inmediato.
     */
    public function user(): ?User
    {
        if (!Session::has('auth_user_id')) {
            $this->cachedUser = null;
            return null;
        }

        $userId = (int) Session::get('auth_user_id');

        // Verificación en tiempo real contra base de datos (Reglas 14 y 15)
        $user = $this->userRepository->findById($userId);

        if ($user === null || !$user->isActive()) {
            // Usuario inexistente o desactivado: revocar privilegios y destruir sesión
            Logger::warning('Sesión revocada en tiempo de ejecución: usuario no existe o fue desactivado.', [
                'id_usuario' => $userId,
            ]);
            $this->logout();
            return null;
        }

        // Mantener sincronizado el rol activo de base de datos
        Session::set('auth_role', $user->rol_nombre);
        $this->cachedUser = $user;

        return $user;
    }

    /**
     * Comprobar si existe un usuario autenticado y activo.
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Obtener el identificador del usuario autenticado.
     */
    public function id(): ?int
    {
        return $this->user()?->id_usuario;
    }

    /**
     * Cerrar la sesión del usuario actual y destruir todos sus datos.
     */
    public function logout(): void
    {
        $userId = Session::get('auth_user_id');
        if ($userId !== null) {
            Logger::info('Cierre de sesión.', ['id_usuario' => $userId]);
        }

        Session::destroy();
        $this->cachedUser = null;
        $this->lastError = null;
    }

    /**
     * Obtener el último mensaje de error registrado en el servicio.
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}
