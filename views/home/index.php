<div class="card status-card">
    <div class="card-header">
        <h2>Arquitectura Base del Sistema</h2>
        <span class="badge badge-success">Fase 2 Operativa</span>
    </div>
    <div class="card-body">
        <p class="intro-text">
            La infraestructura base de la aplicación se encuentra configurada y operativa siguiendo el patrón
            arquitectónico de separación en capas (Presentación &rarr; Controladores &rarr; Servicios &rarr; Repositorios &rarr; PDO/MySQL).
        </p>

        <div class="grid-indicators">
            <div class="indicator-box">
                <span class="indicator-title">Entorno</span>
                <span class="indicator-value"><?= e($environment) ?></span>
            </div>
            <div class="indicator-box">
                <span class="indicator-title">Versión PHP</span>
                <span class="indicator-value"><?= e($phpVersion) ?></span>
            </div>
            <div class="indicator-box">
                <span class="indicator-title">Base de Datos</span>
                <span class="indicator-value <?= $dbConnected ? 'text-success' : 'text-danger' ?>">
                    <?= $dbConnected ? '&#10004; Conectado (PDO MySQL)' : '&#10008; Sin Conexión' ?>
                </span>
            </div>
            <div class="indicator-box">
                <span class="indicator-title">Modo Depuración</span>
                <span class="indicator-value"><?= $debug ? 'Habilitado' : 'Deshabilitado' ?></span>
            </div>
        </div>

        <div class="architecture-details">
            <h3>Capas Implementadas en Fase 2</h3>
            <ul class="check-list">
                <li><strong>Punto Único de Entrada:</strong> <code>public/index.php</code> centraliza el ciclo de vida de la solicitud.</li>
                <li><strong>Carga de Configuración:</strong> Separada en <code>config/</code> y variables en <code>.env</code> sin exponer secretos.</li>
                <li><strong>Capa de Conexión:</strong> <code>App\Core\Database</code> con PDO, transacciones preparadas y manejo seguro de errores.</li>
                <li><strong>Enrutador Ligero:</strong> <code>App\Core\Router</code> con soporte estricto para métodos GET y POST, más manejo controlado de 404.</li>
                <li><strong>Plantillas y Vistas:</strong> <code>views/layouts/main.php</code> con escape obligatorio contra inyecciones XSS.</li>
            </ul>
        </div>

        <div class="notice-box">
            <strong>Aislamiento de Fase:</strong> No se han implementado módulos funcionales completos (autenticación, inscripciones, asistencia, permisos o reportes) preservando el alcance delimitado de la Fase 2.
        </div>
    </div>
</div>
