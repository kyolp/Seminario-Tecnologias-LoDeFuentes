<?php
/**
 * Vista de administración: Ubicación Institucional y Geocerca (Fase 8 — RF-18).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Variables:
 * @var \App\Models\User $user
 * @var \App\Models\ConfiguracionUbicacion|null $configuracionActiva
 * @var array<int, \App\Models\ConfiguracionUbicacion> $historial
 * @var string|null $mensajeExito
 */
?>

<div class="admin-ubicacion-container" style="max-width: 1000px; margin: 2rem auto; padding: 0 1rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="margin: 0; color: #0f172a; font-size: 1.75rem; font-weight: 700;">Ubicación Institucional y Geocerca</h1>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.9rem;">Configuración del radio geográfico autorizado para el marcaje de asistencia docente (RF-18).</p>
        </div>
        <div>
            <a href="<?= e(url('/administracion/ubicacion/configurar')) ?>"
               style="background: #2563eb; color: #ffffff; padding: 0.6rem 1.2rem; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                Nueva Configuración
            </a>
            <a href="<?= e(url('/administracion')) ?>"
               style="background: #f1f5f9; color: #475569; padding: 0.6rem 1rem; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.875rem; margin-left: 0.5rem; border: 1px solid #cbd5e1;">
                Volver
            </a>
        </div>
    </div>

    <?php if (!empty($mensajeExito)): ?>
        <div style="background: #ecfdf5; border-left: 4px solid #10b981; color: #065f46; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <?= e($mensajeExito) ?>
        </div>
    <?php endif; ?>

    <!-- Tarjeta de Configuración Activa -->
    <div class="card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 2rem; overflow: hidden;">
        <div style="background: #1e3a8a; color: #ffffff; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; font-size: 1.15rem; font-weight: 600;">Configuración Activa Vigente</h2>
            <span style="background: #10b981; color: #ffffff; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 9999px; text-transform: uppercase;">Activa</span>
        </div>

        <div style="padding: 1.5rem;">
            <?php if ($configuracionActiva === null): ?>
                <div style="text-align: center; padding: 2rem; color: #64748b;">
                    <p style="margin: 0 0 1rem 0; font-size: 1rem;">No existe actualmente una configuración de ubicación activa en el sistema.</p>
                    <a href="<?= e(url('/administracion/ubicacion/configurar')) ?>" style="color: #2563eb; font-weight: 600; text-decoration: none;">Crear la primera configuración institucional &rarr;</a>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div>
                        <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block;">Descripción / Sede</span>
                        <strong style="color: #1e293b; font-size: 1rem;"><?= e($configuracionActiva->descripcion) ?></strong>
                    </div>
                    <div>
                        <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block;">Coordenadas (Lat, Lon)</span>
                        <code style="background: #f1f5f9; padding: 0.2rem 0.4rem; border-radius: 4px; color: #0f172a; font-size: 0.95rem; font-family: monospace;">
                            <?= number_format($configuracionActiva->latitud, 7) ?>, <?= number_format($configuracionActiva->longitud, 7) ?>
                        </code>
                    </div>
                    <div>
                        <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block;">Radio Permitido</span>
                        <strong style="color: #047857; font-size: 1.1rem;"><?= number_format($configuracionActiva->radio_metros, 2) ?> metros</strong>
                    </div>
                    <div>
                        <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block;">Fecha de Activación</span>
                        <span style="color: #1e293b;"><?= e($configuracionActiva->fecha_inicio) ?></span>
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.75rem 1rem; font-size: 0.85rem; color: #475569;">
                    Configurada por: <strong><?= e($configuracionActiva->usuario_creador_nombre ?? 'Administrador') ?></strong> el <?= e($configuracionActiva->fecha_creacion) ?>.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Historial de Configuraciones -->
    <div class="card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden;">
        <div style="background: #f8fafc; padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0;">
            <h2 style="margin: 0; font-size: 1.15rem; color: #1e293b; font-weight: 600;">Historial de Configuraciones Geográficas</h2>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left;">
                <thead>
                    <tr style="background: #f1f5f9; color: #475569; border-bottom: 1px solid #e2e8f0;">
                        <th style="padding: 0.75rem 1rem;">ID</th>
                        <th style="padding: 0.75rem 1rem;">Descripción</th>
                        <th style="padding: 0.75rem 1rem;">Latitud / Longitud</th>
                        <th style="padding: 0.75rem 1rem;">Radio</th>
                        <th style="padding: 0.75rem 1rem;">Período de Vigencia</th>
                        <th style="padding: 0.75rem 1rem;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historial)): ?>
                        <tr>
                            <td colspan="6" style="padding: 2rem; text-align: center; color: #64748b;">No hay registros históricos de configuración.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historial as $h): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 0.75rem 1rem; color: #64748b; font-family: monospace;">#<?= e((string)$h->id_configuracion_ubicacion) ?></td>
                                <td style="padding: 0.75rem 1rem; font-weight: 600; color: #1e293b;"><?= e($h->descripcion) ?></td>
                                <td style="padding: 0.75rem 1rem; font-family: monospace; font-size: 0.85rem;">
                                    <?= number_format($h->latitud, 7) ?>, <?= number_format($h->longitud, 7) ?>
                                </td>
                                <td style="padding: 0.75rem 1rem; color: #047857; font-weight: 600;"><?= number_format($h->radio_metros, 2) ?> m</td>
                                <td style="padding: 0.75rem 1rem; font-size: 0.8rem; color: #64748b;">
                                    <?= e($h->fecha_inicio) ?> &rarr; <?= e($h->fecha_fin ?? 'Presente') ?>
                                </td>
                                <td style="padding: 0.75rem 1rem;">
                                    <?php if ($h->isActiva()): ?>
                                        <span style="background: #dcfce7; color: #166534; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">ACTIVA</span>
                                    <?php else: ?>
                                        <span style="background: #f1f5f9; color: #64748b; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.75rem;">INACTIVA</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
