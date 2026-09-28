<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Motor ligero de renderizado de vistas con soporte de layouts y paso seguro de datos.
 */
class View
{
    private static ?string $viewsPath = null;

    /**
     * Definir ruta base de las vistas.
     */
    public static function setViewsPath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/\\');
    }

    /**
     * Obtener ruta base de vistas.
     */
    public static function getViewsPath(): string
    {
        if (self::$viewsPath === null) {
            self::$viewsPath = defined('VIEW_PATH') ? VIEW_PATH : dirname(__DIR__, 2) . '/views';
        }

        return self::$viewsPath;
    }

    /**
     * Renderizar una vista y opcionalmente embeberla en un layout común.
     *
     * @param array<string, mixed> $data
     * @throws RuntimeException
     */
    public static function render(string $viewName, array $data = [], ?string $layout = 'main'): string
    {
        $basePath = self::getViewsPath();
        $normalizedView = str_replace('.', '/', $viewName);
        $viewFile = $basePath . '/' . $normalizedView . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("La vista solicitada no fue encontrada: [{$viewName}] en ruta [{$viewFile}]");
        }

        // Renderizar el contenido de la vista
        $content = self::renderFile($viewFile, $data);

        // Si se especificó layout, embeber el contenido dentro del layout
        if ($layout !== null && $layout !== '') {
            $layoutFile = $basePath . '/layouts/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                throw new RuntimeException("El layout solicitado no fue encontrado: [{$layout}] en ruta [{$layoutFile}]");
            }

            // Inyectar $content y $data en el layout
            $layoutData = array_merge($data, ['content' => $content]);
            return self::renderFile($layoutFile, $layoutData);
        }

        return $content;
    }

    /**
     * Renderizar un archivo PHP con buffer de salida aislado.
     *
     * @param array<string, mixed> $variables
     */
    private static function renderFile(string $filePath, array $variables): string
    {
        extract($variables, EXTR_SKIP);

        ob_start();
        try {
            include $filePath;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
