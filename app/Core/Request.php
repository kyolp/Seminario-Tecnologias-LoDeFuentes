<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Encapsulación de la solicitud HTTP entrante.
 */
class Request
{
    private string $method;
    private string $path;
    /** @var array<string, mixed> */
    private array $query;
    /** @var array<string, mixed> */
    private array $post;
    /** @var array<string, string> */
    private array $headers;
    /** @var array<string, mixed> */
    private array $files;
    private string $ip;

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, string> $headers
     * @param array<string, mixed> $files
     */
    public function __construct(
        string $method,
        string $path,
        array $query = [],
        array $post = [],
        array $headers = [],
        array $files = [],
        ?string $ip = null
    ) {
        $this->method = strtoupper($method);
        $this->path = self::normalizePath($path);
        $this->query = $query;
        $this->post = $post;

        $normalizedHeaders = [];
        foreach ($headers as $k => $v) {
            $headerName = str_replace('_', '-', strtolower((string)$k));
            if (str_starts_with($headerName, 'http-')) {
                $headerName = substr($headerName, 5);
            }
            $normalizedHeaders[$headerName] = (string) $v;
        }
        $this->headers = $normalizedHeaders;
        $this->files = $files;

        // Determinar IP de cliente de forma segura exclusivamente desde REMOTE_ADDR (sin confiar en cabeceras manipulables)
        $this->ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    /**
     * Crear instancia a partir de superglobales de PHP.
     */
    public static function createFromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Parsear URL para separar path y query
        $parsed = parse_url($uri);
        $path = $parsed['path'] ?? '/';

        // Si se ejecuta en subdirectorio como /ProyectoFinal/public/, extraer ruta relativa
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = dirname($scriptName);
        if ($scriptDir !== '/' && $scriptDir !== '\\' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }

        // Obtener headers
        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $headerName = str_replace('_', '-', strtolower(substr($k, 5)));
                $headers[$headerName] = (string) $v;
            }
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        return new self($method, $path, $_GET, $_POST, $headers, $_FILES, $ip);
    }

    /**
     * Crear instancia simulada (ideal para pruebas).
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, string> $headers
     * @param array<string, mixed> $files
     */
    public static function create(
        string $method,
        string $uri,
        array $query = [],
        array $post = [],
        array $headers = [],
        array $files = [],
        ?string $ip = null
    ): self {
        $parsed = parse_url($uri);
        $path = $parsed['path'] ?? '/';

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $urlQuery);
            $query = array_merge($urlQuery, $query);
        }

        return new self($method, $path, $query, $post, $headers, $files, $ip);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function getQuery(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $this->getQuery($key, $default);
    }

    public function getPost(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }

        return $this->post[$key] ?? $default;
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        return $this->getPost($key, $default);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }

        if (array_key_exists($key, $this->query)) {
            return $this->query[$key];
        }

        return $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    /**
     * Obtener una cabecera de la solicitud (insensible a mayúsculas/minúsculas).
     */
    public function getHeader(string $name, ?string $default = null): ?string
    {
        $normalized = str_replace('_', '-', strtolower($name));
        return $this->headers[$normalized] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->getHeader($name, $default);
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Obtener archivo adjunto por clave.
     *
     * @return array<string, mixed>|null
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Alias de file().
     *
     * @return array<string, mixed>|null
     */
    public function getFile(string $key): ?array
    {
        return $this->file($key);
    }

    /**
     * Comprobar si se envió un archivo válido en la solicitud.
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key])
            && is_array($this->files[$key])
            && isset($this->files[$key]['error'])
            && (int) $this->files[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Obtener la dirección IP del cliente resuelta de manera segura.
     */
    public function ip(): string
    {
        return $this->ip;
    }

    /**
     * Alias de ip().
     */
    public function getClientIp(): string
    {
        return $this->ip();
    }

    private static function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '' ? '/' : $path;
    }
}
