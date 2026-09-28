# Enrutador HTTP — Fase 2

## Propósito

Gestionar la correspondencia entre métodos HTTP (`GET`, `POST`), URIs solicitadas por el cliente y las acciones de los controladores o funciones ejecutables.

## Arquitectura de la clase `App\Core\Router`

El enrutador almacena rutas indexadas por método y ruta normalizada:

```text
$routes = [
    'GET' => [
        '/'          => ['handler' => [HomeController::class, 'index'], 'middleware' => []],
        '/status'    => ['handler' => [HomeController::class, 'status'], 'middleware' => []],
        '/test/get'  => ['handler' => [TestController::class, 'testGet'], 'middleware' => []]
    ],
    'POST' => [
        '/test/post' => ['handler' => [TestController::class, 'testPost'], 'middleware' => []]
    ]
];
```

## Registro de rutas

Se proporcionan métodos explícitos para cada verbo HTTP admitido en esta fase:

```php
// Registro de ruta GET
$router->get('/', [HomeController::class, 'index']);

// Registro de ruta POST
$router->post('/test/post', [TestController::class, 'testPost']);
```

## Proceso de despacho (`dispatch`)

1. **Normalización:** La ruta entrante se estandariza eliminando barras duplicadas o finales (ej: `/test/` pasa a `/test`).
2. **Búsqueda:** Se consulta la tabla interna bajo la clave del método y ruta.
3. **Ejecución:**
   - Si el handler es un arreglo `[ClaseControlador, 'metodo']`, se instancia la clase y se ejecuta la acción pasando el objeto `Request`.
   - Si el handler es una función anónima `Closure`, se ejecuta directamente con el objeto `Request`.
4. **Manejo de 404:** Si la combinación de método y ruta no existe, se activa el método `handleNotFound`, el cual renderiza `views/errors/404.php` y emite el código de estado HTTP 404.
5. **Normalización de respuesta:** Si el controlador retorna una cadena, arreglo o instancia de `Response`, el enrutador garantiza que la salida sea un objeto `Response` tipado.

## Preparación para Fase 3 (Autenticación y Roles)

Cada definición de ruta incluye un arreglo opcional de middlewares:
```php
public function get(string $path, mixed $handler, array $middleware = []): self
```
Esto permitirá que en la Fase 3 se puedan asociar middlewares como `AuthMiddleware` o `RoleMiddleware` sin requerir modificaciones estructurales en el enrutador.
