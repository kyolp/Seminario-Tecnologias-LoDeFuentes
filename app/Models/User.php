<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entidad de Usuario del sistema.
 * Mapea las columnas de la tabla `usuarios` y la relación con `roles`.
 */
class User
{
    public function __construct(
        public readonly int $id_usuario,
        public readonly int $id_rol,
        public readonly string $nombre_usuario,
        public readonly string $correo,
        public readonly string $clave_hash,
        public readonly string $nombres,
        public readonly string $apellidos,
        public readonly string $estado,
        public readonly ?string $ultimo_acceso = null,
        public readonly string $rol_nombre = ''
    ) {
    }

    /**
     * Construir instancia a partir de arreglo relacional obtenido por PDO.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id_usuario: (int) ($data['id_usuario'] ?? 0),
            id_rol: (int) ($data['id_rol'] ?? 0),
            nombre_usuario: (string) ($data['nombre_usuario'] ?? ''),
            correo: (string) ($data['correo'] ?? ''),
            clave_hash: (string) ($data['clave_hash'] ?? ''),
            nombres: (string) ($data['nombres'] ?? ''),
            apellidos: (string) ($data['apellidos'] ?? ''),
            estado: (string) ($data['estado'] ?? 'INACTIVO'),
            ultimo_acceso: isset($data['ultimo_acceso']) ? (string) $data['ultimo_acceso'] : null,
            rol_nombre: (string) ($data['rol_nombre'] ?? $data['rol'] ?? '')
        );
    }

    /**
     * Comprobar si el usuario se encuentra en estado ACTIVO.
     */
    public function isActive(): bool
    {
        return strtoupper($this->estado) === 'ACTIVO';
    }

    /**
     * Verificar si el usuario posee un rol específico.
     * Soporta los sinónimos institucionales 'ADMINISTRADOR' y 'ADMINISTRACION'.
     */
    public function hasRole(string $role): bool
    {
        $currentRole = strtoupper(trim($this->rol_nombre));
        $targetRole = strtoupper(trim($role));

        if ($targetRole === 'ADMINISTRADOR' || $targetRole === 'ADMINISTRACION') {
            return in_array($currentRole, ['ADMINISTRADOR', 'ADMINISTRACION'], true);
        }

        return $currentRole === $targetRole;
    }

    /**
     * Verificar si el usuario es ADMINISTRADOR.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('ADMINISTRADOR');
    }

    /**
     * Verificar si el usuario es DOCENTE.
     */
    public function isDocente(): bool
    {
        return $this->hasRole('DOCENTE');
    }

    /**
     * Obtener el nombre completo del usuario.
     */
    public function getFullName(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    /**
     * Convertir a arreglo seguro para vistas o respuestas (omite estrictamente clave_hash).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_usuario' => $this->id_usuario,
            'id_rol' => $this->id_rol,
            'nombre_usuario' => $this->nombre_usuario,
            'correo' => $this->correo,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'nombre_completo' => $this->getFullName(),
            'estado' => $this->estado,
            'ultimo_acceso' => $this->ultimo_acceso,
            'rol' => $this->rol_nombre,
        ];
    }
}
