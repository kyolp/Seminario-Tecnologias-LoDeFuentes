<?php
/**
 * Vista de Detalle de Solicitud de Permiso — Portal Docente (RF-28).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Variables:
 * @var \App\Models\User $user
 * @var \App\Models\Docente $docente
 * @var \App\Models\SolicitudPermiso $solicitud
 */
?>

<div class="permiso-detalle-container" style="max-width: 900px; margin: 2rem auto; padding: 0 1rem;">
    <!-- Encabezado y Navegación -->
    <div style="margin-bottom: 1.5rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
            <a href="<?= e(url('/docente/permisos')) ?>" style="color: #64748b; text-decoration: none; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                &larr; Volver a Mis Permisos
            </a>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="margin: 0; font-size: 1.75rem; color: #1e3a8a; font-weight: 700;">
                    Solicitud de Permiso #<?= e($solicitud->id_solicitud_permiso) ?>
                </h1>
                <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.9rem;">
                    Registrada el <?= e(date('d/m/Y \a \l\a\s H:i', strtotime($solicitud->fecha_solicitud))) ?>
                </p>
            </div>
            <div>
                <?php if ($solicitud->isPendiente()): ?>
                    <span style="background: #fef3c7; color: #92400e; padding: 0.4rem 0.9rem; border-radius: 9999px; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; border: 1px solid #fde68a;">
                        PENDIENTE DE REVISIÓN
                    </span>
                <?php elseif ($solicitud->isAprobado()): ?>
                    <span style="background: #dcfce7; color: #166534; padding: 0.4rem 0.9rem; border-radius: 9999px; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; border: 1px solid #bbf7d0;">
                        APROBADO
                    </span>
                <?php else: ?>
                    <span style="background: #fee2e2; color: #991b1b; padding: 0.4rem 0.9rem; border-radius: 9999px; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; border: 1px solid #fecaca;">
                        RECHAZADO
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tarjeta Principal con Información -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 1.5rem; overflow: hidden;">
        <div style="background: #f8fafc; padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0;">
            <h2 style="margin: 0; font-size: 1.1rem; color: #1e293b; font-weight: 600;">Detalles de la Solicitud</h2>
        </div>
        <div style="padding: 1.5rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                <div>
                    <span style="display: block; font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 600; margin-bottom: 0.25rem;">Fecha Solicitada</span>
                    <span style="font-size: 1.1rem; font-weight: 700; color: #1e3a8a;">
                        <?= e(date('d/m/Y', strtotime($solicitud->fecha_solicitada))) ?>
                    </span>
                </div>
                <div>
                    <span style="display: block; font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 600; margin-bottom: 0.25rem;">Motivo / Causal</span>
                    <span style="font-size: 1rem; font-weight: 600; color: #334155;">
                        <?= e($solicitud->tipo_motivo) ?>
                    </span>
                </div>
                <div>
                    <span style="display: block; font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 600; margin-bottom: 0.25rem;">Última Actualización</span>
                    <span style="font-size: 0.95rem; color: #64748b;">
                        <?= $solicitud->fecha_actualizacion ? e(date('d/m/Y H:i', strtotime($solicitud->fecha_actualizacion))) : '—' ?>
                    </span>
                </div>
            </div>

            <div>
                <span style="display: block; font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 600; margin-bottom: 0.5rem;">Descripción Justificativa</span>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem; color: #334155; font-size: 0.95rem; line-height: 1.5; white-space: pre-wrap;">
                    <?= e($solicitud->descripcion) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Documento de Soporte Adjunto (RF-25) -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 1.5rem; overflow: hidden;">
        <div style="background: #f8fafc; padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0;">
            <h2 style="margin: 0; font-size: 1.1rem; color: #1e293b; font-weight: 600;">Documento de Soporte (RF-25)</h2>
        </div>
        <div style="padding: 1.5rem;">
            <?php if ($solicitud->documento !== null): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 1rem 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 1.75rem;">📄</span>
                        <div>
                            <div style="font-weight: 600; color: #166534; font-size: 0.95rem;">
                                <?= e($solicitud->documento->nombre_original) ?>
                            </div>
                            <div style="color: #64748b; font-size: 0.8rem; margin-top: 0.15rem;">
                                <?= e($solicitud->documento->tipo_mime) ?> &bull; <?= number_format($solicitud->documento->tamano_bytes / 1024, 1) ?> KB &bull; Subido el <?= e(date('d/m/Y H:i', strtotime($solicitud->documento->fecha_carga))) ?>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="<?= e(url('/docente/permisos/documento?id=' . $solicitud->documento->id_documento_permiso)) ?>" style="background: #16a34a; color: #ffffff; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                            📥 Descargar Soporte
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <p style="margin: 0; color: #64748b; font-size: 0.9rem; font-style: italic;">
                    No se adjuntó documento de soporte para esta solicitud.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Historial Inmutable de Auditoría (RF-28, DT-56) -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden;">
        <div style="background: #f8fafc; padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0;">
            <h2 style="margin: 0; font-size: 1.1rem; color: #1e293b; font-weight: 600;">Historial de Cambios y Resoluciones (RF-28)</h2>
        </div>
        <div style="padding: 1.5rem;">
            <?php if (empty($solicitud->historial)): ?>
                <p style="margin: 0; color: #64748b; font-size: 0.9rem;">Sin registros en el historial.</p>
            <?php else: ?>
                <div style="position: relative; padding-left: 1.5rem; border-left: 2px solid #e2e8f0;">
                    <?php foreach ($solicitud->historial as $h): ?>
                        <div style="margin-bottom: 1.5rem; position: relative;">
                            <!-- Marcador de línea de tiempo -->
                            <div style="position: absolute; left: -1.95rem; top: 0.25rem; width: 14px; height: 14px; border-radius: 50%; background: #3b82f6; border: 2px solid #ffffff;"></div>

                            <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <div style="font-weight: 600; color: #1e293b; font-size: 0.95rem;">
                                    <?php if ($h->estado_anterior === null): ?>
                                        Creación de Solicitud &rarr; <span style="color: #d97706;"><?= e($h->estado_nuevo) ?></span>
                                    <?php else: ?>
                                        Cambio de Estado: <span style="text-decoration: line-through; color: #64748b;"><?= e($h->estado_anterior) ?></span> &rarr;
                                        <span style="font-weight: 700; color: <?= $h->estado_nuevo === 'APROBADO' ? '#16a34a' : ($h->estado_nuevo === 'RECHAZADO' ? '#dc2626' : '#d97706') ?>;">
                                            <?= e($h->estado_nuevo) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span style="color: #64748b; font-size: 0.8rem;">
                                    <?= e(date('d/m/Y H:i:s', strtotime($h->fecha_cambio))) ?>
                                </span>
                            </div>

                            <div style="font-size: 0.85rem; color: #475569; margin-bottom: 0.35rem;">
                                <strong>Actor:</strong> <?= e($h->nombre_actor ?? 'Usuario #' . $h->id_usuario_actor) ?>
                                <?php if (!empty($h->rol_actor)): ?>
                                    <span style="background: #e2e8f0; color: #334155; font-size: 0.75rem; padding: 0.1rem 0.4rem; border-radius: 4px; margin-left: 0.25rem;">
                                        <?= e($h->rol_actor) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($h->comentario)): ?>
                                <div style="background: #f1f5f9; border-left: 3px solid #94a3b8; padding: 0.5rem 0.75rem; border-radius: 0 4px 4px 0; font-size: 0.85rem; color: #334155; margin-top: 0.35rem; font-style: italic;">
                                    &ldquo;<?= e($h->comentario) ?>&rdquo;
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
