<?php
/**
 * Vista de Asistencia Docente con Geolocalización (Fase 8 — RF-19 a RF-23).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Variables:
 * @var \App\Models\User $user
 * @var \App\Models\Docente $docente
 * @var \App\Models\ConfiguracionUbicacion|null $configuracion
 * @var \App\Models\MarcajeAsistencia|null $ultimoMarcaje
 * @var bool $puedeMarcarEntrada
 * @var bool $puedeMarcarSalida
 * @var array<int, \App\Models\MarcajeAsistencia> $historial
 * @var string|null $mensajeExito
 * @var string|null $mensajeError
 */
?>

<?php if ($jornadaFinalizada ?? false): ?>
    <div role="status"
         style="max-width: 950px; margin: 1rem auto; padding: 1rem;
                background: #dcfce7; color: #166534;
                border: 1px solid #86efac; border-radius: 8px;">
        <strong>Jornada laboral finalizada por hoy.</strong>
        Ya registró su salida. Podrá marcar una nueva entrada mañana.
    </div>
<?php endif; ?>

<div class="docente-asistencia-container" style="max-width: 1000px; margin: 2rem auto; padding: 0 1rem;">
    <!-- Encabezado y Navegación -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.25rem;">
                <a href="<?= e(url('/docente')) ?>" style="color: #2563eb; text-decoration: none; font-size: 0.875rem; font-weight: 600;">&larr; Volver al Portal Docente</a>
                <span style="background: #3b82f6; color: #ffffff; font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 9999px; text-transform: uppercase;">Módulo de Asistencia</span>
            </div>
            <h1 style="margin: 0; color: #0f172a; font-size: 1.75rem; font-weight: 700;">Control de Asistencia Docente</h1>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.9rem;">
                Docente: <strong><?= e($docente->getNombreCompleto()) ?></strong> (<?= e($docente->codigo_docente ?? 'Docente') ?>)
            </p>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 0.85rem; color: #64748b;">Fecha y Hora Oficial del Servidor</div>
            <div style="font-size: 1.1rem; font-weight: 700; color: #1e293b;" id="reloj-servidor"><?= date('d/m/Y H:i') ?></div>
            <div style="font-size: 0.75rem; color: #94a3b8;">Zona: America/Guatemala</div>
        </div>
    </div>

    <!-- Mensajes de Estado del Servidor -->
    <?php if (!empty($mensajeExito)): ?>
        <div style="background: #ecfdf5; border-left: 4px solid #10b981; color: #065f46; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-weight: 500;">
            <?= e($mensajeExito) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensajeError)): ?>
        <div style="background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-weight: 500;">
            <?= e($mensajeError) ?>
        </div>
    <?php endif; ?>

    <!-- Contenedor para Mensajes Dinámicos del Navegador / GPS -->
    <div id="alerta-gps" style="display: none; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-weight: 500;"></div>

    <!-- Panel de Marcaje y Geocerca -->
    <div class="card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 2rem; overflow: hidden;">
        <div style="background: #1e3a8a; color: #ffffff; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <h2 style="margin: 0; font-size: 1.2rem; font-weight: 600;">Marcaje de Entrada y Salida</h2>
                <span style="font-size: 0.85rem; color: #bfdbfe;">Validación geográfica obligatoria dentro del radio institucional</span>
            </div>
            <div>
                <?php if ($configuracion !== null): ?>
                    <span style="background: #10b981; color: #ffffff; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 9999px;">
                        Geocerca Activa: Radio <?= number_format($configuracion->radio_metros, 0) ?>m
                    </span>
                <?php else: ?>
                    <span style="background: #ef4444; color: #ffffff; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 9999px;">
                        Sin Ubicación Activa
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div style="padding: 1.5rem;">
            <?php if ($configuracion === null): ?>
                <div style="text-align: center; padding: 2rem 1rem; color: #b91c1c;">
                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin: 0 auto 1rem auto; display: block;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.15rem;">No existe una ubicación institucional activa configurada.</h3>
                    <p style="margin: 0; font-size: 0.9rem; color: #64748b;">El registro de asistencia docente se encuentra temporalmente inhabilitado hasta que la administración configure la geocerca de la escuela.</p>
                </div>
            <?php else: ?>
                <!-- Estado actual del docente -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; background: #f8fafc; padding: 1rem 1.25rem; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; font-size: 0.9rem;">
                    <div>
                        <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block;">Sede Autorizada</span>
                        <strong style="color: #1e293b;"><?= e($configuracion->descripcion) ?></strong>
                    </div>
                    <div>
                        <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block;">Radio de Cobertura</span>
                        <strong style="color: #047857;"><?= number_format($configuracion->radio_metros, 2) ?> metros</strong>
                    </div>
                    <div>
                        <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block;">Último Marcaje Registrado</span>
                        <?php if ($ultimoMarcaje !== null): ?>
                            <span style="font-weight: 700; color: <?= $ultimoMarcaje->isEntrada() ? '#15803d' : '#b45309' ?>;">
                                <?= e($ultimoMarcaje->tipo) ?>
                            </span>
                            <span style="color: #64748b; font-size: 0.8rem;">(<?= e(date('d/m/Y H:i', strtotime($ultimoMarcaje->fecha_hora))) ?>)</span>
                        <?php else: ?>
                            <span style="color: #64748b; font-style: italic;">Sin marcajes previos</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Botones de Marcaje con obtención de Geoposición -->
                <div style="display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap; padding: 1rem 0;">
                    <!-- Botón ENTRADA -->
                    <form id="form-entrada" action="<?= e(url('/docente/asistencia/entrada')) ?>" method="POST" style="margin: 0;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="latitud" id="lat-entrada">
                        <input type="hidden" name="longitud" id="lon-entrada">
                        <input type="hidden" name="precision" id="prec-entrada">

                        <button type="button" id="btn-marcar-entrada"
                                onclick="iniciarMarcaje('ENTRADA')"
                                <?= !$puedeMarcarEntrada ? 'disabled' : '' ?>
                                style="background: <?= $puedeMarcarEntrada ? '#16a34a' : '#94a3b8' ?>; color: #ffffff; border: none; border-radius: 8px; padding: 1rem 2rem; font-size: 1.1rem; font-weight: 700; cursor: <?= $puedeMarcarEntrada ? 'pointer' : 'not-allowed' ?>; display: inline-flex; align-items: center; gap: 0.75rem; box-shadow: 0 4px 6px rgba(0,0,0,0.08);">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                            Registrar Entrada
                        </button>
                    </form>

                    <!-- Botón SALIDA -->
                    <form id="form-salida" action="<?= e(url('/docente/asistencia/salida')) ?>" method="POST" style="margin: 0;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="latitud" id="lat-salida">
                        <input type="hidden" name="longitud" id="lon-salida">
                        <input type="hidden" name="precision" id="prec-salida">

                        <button type="button" id="btn-marcar-salida"
                                onclick="iniciarMarcaje('SALIDA')"
                                <?= !$puedeMarcarSalida ? 'disabled' : '' ?>
                                style="background: <?= $puedeMarcarSalida ? '#ea580c' : '#94a3b8' ?>; color: #ffffff; border: none; border-radius: 8px; padding: 1rem 2rem; font-size: 1.1rem; font-weight: 700; cursor: <?= $puedeMarcarSalida ? 'pointer' : 'not-allowed' ?>; display: inline-flex; align-items: center; gap: 0.75rem; box-shadow: 0 4px 6px rgba(0,0,0,0.08);">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Registrar Salida
                        </button>
                    </form>
                </div>

                <div id="indicador-carga" style="display: none; text-align: center; margin-top: 1rem; color: #2563eb; font-weight: 600;">
                    <span style="display: inline-block; animation: pulse 1.5s infinite;">Obteniendo coordenadas GPS de su dispositivo...</span>
                </div>

                <div style="margin-top: 1rem; font-size: 0.8rem; color: #64748b; text-align: center;">
                    * Requiere que el navegador tenga acceso autorizado a la ubicación geográfica del dispositivo. En producción se requiere conexión segura HTTPS.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Historial de Asistencia Personal (RF-23) -->
    <div class="card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden;">
        <div style="background: #f8fafc; padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; font-size: 1.15rem; color: #1e293b; font-weight: 600;">Mi Historial Reciente de Asistencia</h2>
            <span style="font-size: 0.8rem; color: #64748b;">Últimos marcajes registrados (Inmutables)</span>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left;">
                <thead>
                    <tr style="background: #f1f5f9; color: #475569; border-bottom: 1px solid #e2e8f0;">
                        <th style="padding: 0.75rem 1rem;">Fecha</th>
                        <th style="padding: 0.75rem 1rem;">Hora</th>
                        <th style="padding: 0.75rem 1rem;">Tipo</th>
                        <th style="padding: 0.75rem 1rem;">Distancia al Centro</th>
                        <th style="padding: 0.75rem 1rem;">Coordenadas Registradas</th>
                        <th style="padding: 0.75rem 1rem;">Precisión GPS</th>
                        <th style="padding: 0.75rem 1rem;">Validación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historial)): ?>
                        <tr>
                            <td colspan="7" style="padding: 2.5rem 1rem; text-align: center; color: #64748b;">
                                No tiene marcajes de asistencia registrados en su historial.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historial as $m): ?>
                            <?php
                            $ts = strtotime($m->fecha_hora);
                            $fechaFormateada = date('d/m/Y', $ts);
                            $horaFormateada = date('H:i:s', $ts);
                            ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 0.75rem 1rem; font-weight: 600; color: #1e293b;"><?= e($fechaFormateada) ?></td>
                                <td style="padding: 0.75rem 1rem; color: #334155; font-family: monospace; font-size: 0.95rem;"><?= e($horaFormateada) ?></td>
                                <td style="padding: 0.75rem 1rem;">
                                    <?php if ($m->isEntrada()): ?>
                                        <span style="background: #dcfce7; color: #15803d; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">ENTRADA</span>
                                    <?php else: ?>
                                        <span style="background: #ffedd5; color: #c2410c; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">SALIDA</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 0.75rem 1rem; font-weight: 600; color: #047857;">
                                    <?= number_format($m->distancia_metros, 1) ?> m
                                </td>
                                <td style="padding: 0.75rem 1rem; font-family: monospace; font-size: 0.8rem; color: #64748b;">
                                    <?= number_format($m->latitud_dispositivo, 6) ?>, <?= number_format($m->longitud_dispositivo, 6) ?>
                                </td>
                                <td style="padding: 0.75rem 1rem; font-size: 0.85rem; color: #64748b;">
                                    <?= $m->precision_metros !== null ? number_format($m->precision_metros, 1) . ' m' : 'N/A' ?>
                                </td>
                                <td style="padding: 0.75rem 1rem;">
                                    <span style="background: #e0e7ff; color: #3730a3; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                        Dentro del radio
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Script del Cliente para Geolocalización Segura (API Estándar W3C) -->
<script>
function iniciarMarcaje(tipo) {
    var alerta = document.getElementById('alerta-gps');
    var cargando = document.getElementById('indicador-carga');
    alerta.style.display = 'none';

    if (!navigator.geolocation) {
        mostrarErrorGPS('Su navegador o dispositivo no es compatible con la API de geolocalización. Utilice un navegador moderno.');
        return;
    }

    cargando.style.display = 'block';
    deshabilitarBotones(true);

    var opciones = {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    };

    navigator.geolocation.getCurrentPosition(
        function (posicion) {
            cargando.style.display = 'none';
            var lat = posicion.coords.latitude;
            var lon = posicion.coords.longitude;
            var prec = posicion.coords.accuracy || null;

            if (tipo === 'ENTRADA') {
                document.getElementById('lat-entrada').value = lat;
                document.getElementById('lon-entrada').value = lon;
                document.getElementById('prec-entrada').value = prec;
                document.getElementById('form-entrada').submit();
            } else {
                document.getElementById('lat-salida').value = lat;
                document.getElementById('lon-salida').value = lon;
                document.getElementById('prec-salida').value = prec;
                document.getElementById('form-salida').submit();
            }
        },
        function (error) {
            cargando.style.display = 'none';
            deshabilitarBotones(false);

            var mensaje = '';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    mensaje = 'Permiso denegado: Debe autorizar el acceso a su ubicación geográfica en el navegador para registrar asistencia.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    mensaje = 'Ubicación no disponible: No se pudo obtener la posición del dispositivo. Verifique que el GPS o servicios de localización estén activados.';
                    break;
                case error.TIMEOUT:
                    mensaje = 'Tiempo de espera agotado: El dispositivo tardó demasiado tiempo en responder. Intente de nuevo.';
                    break;
                default:
                    mensaje = 'Error desconocido al solicitar la geolocalización (' + error.message + ').';
                    break;
            }
            mostrarErrorGPS(mensaje);
        },
        opciones
    );
}

function mostrarErrorGPS(msg) {
    var alerta = document.getElementById('alerta-gps');
    alerta.style.display = 'block';
    alerta.style.background = '#fef2f2';
    alerta.style.borderLeft = '4px solid #ef4444';
    alerta.style.color = '#991b1b';
    alerta.textContent = msg;
}

function deshabilitarBotones(desactivar) {
    var bEntrada = document.getElementById('btn-marcar-entrada');
    var bSalida = document.getElementById('btn-marcar-salida');
    if (bEntrada) bEntrada.disabled = desactivar;
    if (bSalida) bSalida.disabled = desactivar;
}
</script>
