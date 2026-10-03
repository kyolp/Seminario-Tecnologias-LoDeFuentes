<?php
/**
 * Vista de Mis Permisos — Portal Docente (RF-28).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Variables:
 * @var \App\Models\User $user
 * @var \App\Models\Docente $docente
 * @var array<int, \App\Models\SolicitudPermiso> $solicitudes
 * @var string|null $mensajeExito
 * @var string|null $mensajeError
 */
?>

<div class="mis-permisos-container" style="max-width: 1100px; margin: 2rem auto; padding: 0 1rem;">
    <!-- Encabezado -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                <a href="<?= e(url('/docente')) ?>" style="color: #64748b; text-decoration: none; font-size: 0.9rem;">&larr; Volver al Portal Docente</a>
            </div>
            <h1 style="margin: 0; font-size: 1.75rem; color: #1e3a8a; font-weight: 700;">Mis Permisos</h1>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.9rem;">Consulta de estado e historial de solicitudes de permiso laboral (RF-28)</p>
        </div>
        <div>
            <a href="<?= e(url('/docente/permisos/crear')) ?>" style="background: #2563eb; color: #ffffff; padding: 0.65rem 1.25rem; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Solicitar Nuevo Permiso (RF-24)
            </a>
        </div>
    </div>

    <!-- Alertas -->
    <?php if (!empty($mensajeExito)): ?>
        <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.95rem;">
            <?= e($mensajeExito) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensajeError)): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.95rem;">
            <?= e($mensajeError) ?>
        </div>
    <?php endif; ?>

    <!-- Tabla de Solicitudes -->
    <div class="card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden;">
        <div style="background: #f8fafc; padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; font-size: 1.1rem; color: #1e293b; font-weight: 600;">Historial Personal de Solicitudes</h2>
            <span style="font-size: 0.85rem; color: #64748b;">Total registradas: <?= count($solicitudes) ?></span>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left;">
                <thead>
                    <tr style="background: #f1f5f9; color: #475569; border-bottom: 1px solid #e2e8f0;">
                        <th style="padding: 0.75rem 1rem;">ID</th>
                        <th style="padding: 0.75rem 1rem;">Fecha Solicitada</th>
                        <th style="padding: 0.75rem 1rem;">Motivo</th>
                        <th style="padding: 0.75rem 1rem;">Descripción</th>
                        <th style="padding: 0.75rem 1rem; text-align: center;">Soporte</th>
                        <th style="padding: 0.75rem 1rem; text-align: center;">Estado</th>
                        <th style="padding: 0.75rem 1rem;">Fecha de Solicitud</th>
                        <th style="padding: 0.75rem 1rem; text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($solicitudes)): ?>
                        <tr>
                            <td colspan="8" style="padding: 2.5rem 1rem; text-align: center; color: #64748b;">
                                No cuenta con solicitudes de permiso registradas.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($solicitudes as $s): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 0.75rem 1rem; font-weight: 600; color: #1e293b;">#<?= e($s->id_solicitud_permiso) ?></td>
                                <td style="padding: 0.75rem 1rem; font-weight: 600; color: #2563eb;">
                                    <?= e(date('d/m/Y', strtotime($s->fecha_solicitada))) ?>
                                </td>
                                <td style="padding: 0.75rem 1rem; font-weight: 500; color: #334155;"><?= e($s->tipo_motivo) ?></td>
                                <td style="padding: 0.75rem 1rem; color: #64748b; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= e($s->descripcion) ?>
                                </td>
                                <td style="padding: 0.75rem 1rem; text-align: center;">
                                    <?php if ($s->documento !== null): ?>
                                        <span title="<?= e($s->documento->nombre_original) ?>" style="color: #047857; font-weight: 600; font-size: 0.8rem; background: #dcfce7; padding: 0.2rem 0.5rem; border-radius: 4px;">
                                            📎 Adjunto
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 0.8rem;">Sin soporte</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 0.75rem 1rem; text-align: center;">
                                    <?php if ($s->isPendiente()): ?>
                                        <span style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.6rem; border-radius: 9999px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">PENDIENTE</span>
                                    <?php elseif ($s->isAprobado()): ?>
                                        <span style="background: #dcfce7; color: #166534; padding: 0.25rem 0.6rem; border-radius: 9999px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">APROBADO</span>
                                    <?php else: ?>
                                        <span style="background: #fee2e2; color: #991b1b; padding: 0.25rem 0.6rem; border-radius: 9999px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">RECHAZADO</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 0.75rem 1rem; color: #64748b; font-size: 0.85rem;">
                                    <?= e(date('d/m/Y H:i', strtotime($s->fecha_solicitud))) ?>
                                </td>
                                <td style="padding: 0.75rem 1rem; text-align: right;">
                                    <a href="<?= e(url('/docente/permisos/ver?id=' . $s->id_solicitud_permiso)) ?>" style="color: #2563eb; text-decoration: none; font-weight: 600; font-size: 0.85rem;">
                                        Ver Detalle &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
