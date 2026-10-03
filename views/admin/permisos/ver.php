<?php
/**
 * Vista de Detalle y Resolución de Solicitud de Permiso — Administración (RF-27, RF-28, RNF-06).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Variables:
 * @var \App\Models\User $user
 * @var \App\Models\SolicitudPermiso $solicitud
 * @var string|null $mensajeExito
 * @var string|null $mensajeError
 */
?>

<section style="max-width: 1000px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <!-- Encabezado y Navegación -->
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="margin-bottom: 0.25rem;">
                <a href="<?= e(url('/administracion/permisos')) ?>" style="color: #64748b; text-decoration: none; font-size: 0.875rem;">&larr; Volver al Listado de Permisos</a>
            </div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.4rem;">
                Solicitud de Permiso #<?= e($solicitud->id_solicitud_permiso) ?>
            </h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">
                Docente: <strong><?= e($solicitud->nombre_docente ?? 'Docente #' . $solicitud->id_docente) ?></strong> 
                <?= !empty($solicitud->codigo_docente) ? '(' . e($solicitud->codigo_docente) . ')' : '' ?>
            </p>
        </div>
        <div>
            <?php if ($solicitud->isPendiente()): ?>
                <span style="background: #fef3c7; color: #92400e; padding: 0.4rem 0.9rem; border-radius: 9999px; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; border: 1px solid #fde68a;">
                    ESTADO: PENDIENTE
                </span>
            <?php elseif ($solicitud->isAprobado()): ?>
                <span style="background: #dcfce7; color: #166534; padding: 0.4rem 0.9rem; border-radius: 9999px; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; border: 1px solid #bbf7d0;">
                    ESTADO: APROBADO
                </span>
            <?php else: ?>
                <span style="background: #fee2e2; color: #991b1b; padding: 0.4rem 0.9rem; border-radius: 9999px; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; border: 1px solid #fecaca;">
                    ESTADO: RECHAZADO
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alertas -->
    <?php if (!empty($mensajeExito)): ?>
        <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.25rem; font-size: 0.9rem;">
            <?= e($mensajeExito) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensajeError)): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.25rem; font-size: 0.9rem;">
            <?= e($mensajeError) ?>
        </div>
    <?php endif; ?>

    <!-- Ficha de Datos del Permiso -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1.25rem; margin-bottom: 1.5rem;">
        <h3 style="margin: 0 0 1rem 0; font-size: 1rem; color: #1e293b; font-weight: 600;">Información de la Solicitud</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
            <div>
                <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Fecha Solicitada</span>
                <span style="font-size: 1.15rem; font-weight: 700; color: #1e3a8a;">
                    <?= e(date('d/m/Y', strtotime($solicitud->fecha_solicitada))) ?>
                </span>
            </div>
            <div>
                <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Motivo / Tipo</span>
                <span style="font-size: 1rem; font-weight: 600; color: #334155;">
                    <?= e($solicitud->tipo_motivo) ?>
                </span>
            </div>
            <div>
                <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Fecha de Registro</span>
                <span style="font-size: 0.9rem; color: #475569;">
                    <?= e(date('d/m/Y H:i:s', strtotime($solicitud->fecha_solicitud))) ?>
                </span>
            </div>
            <div>
                <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Última Actualización</span>
                <span style="font-size: 0.9rem; color: #475569;">
                    <?= $solicitud->fecha_actualizacion ? e(date('d/m/Y H:i:s', strtotime($solicitud->fecha_actualizacion))) : '—' ?>
                </span>
            </div>
        </div>

        <div>
            <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600; margin-bottom: 0.35rem;">Descripción y Justificación Docente</span>
            <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; padding: 0.85rem; font-size: 0.9rem; color: #334155; line-height: 1.5; white-space: pre-wrap;">
                <?= e($solicitud->descripcion) ?>
            </div>
        </div>
    </div>

    <!-- Documento de Soporte Adjunto (RF-25, DT-59) -->
    <div style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 1.25rem; margin-bottom: 1.5rem;">
        <h3 style="margin: 0 0 0.75rem 0; font-size: 1rem; color: #1e293b; font-weight: 600;">Documento de Soporte Adjunto (RF-25)</h3>
        <?php if ($solicitud->documento !== null): ?>
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 0.85rem 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.5rem;">📎</span>
                    <div>
                        <div style="font-weight: 600; color: #166534; font-size: 0.9rem;">
                            <?= e($solicitud->documento->nombre_original) ?>
                        </div>
                        <div style="color: #64748b; font-size: 0.75rem; margin-top: 0.15rem;">
                            <?= e($solicitud->documento->tipo_mime) ?> &bull; <?= number_format($solicitud->documento->tamano_bytes / 1024, 1) ?> KB &bull; Subido el <?= e(date('d/m/Y H:i', strtotime($solicitud->documento->fecha_carga))) ?>
                        </div>
                    </div>
                </div>
                <div>
                    <a href="<?= e(url('/administracion/permisos/documento?id=' . $solicitud->documento->id_documento_permiso)) ?>" style="background: #16a34a; color: #ffffff; padding: 0.45rem 0.9rem; border-radius: 4px; font-weight: 600; text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.35rem;">
                        📥 Descargar Archivo Seguro
                    </a>
                </div>
            </div>
        <?php else: ?>
            <p style="margin: 0; color: #64748b; font-size: 0.875rem; font-style: italic;">
                El docente no adjuntó archivo de soporte para esta solicitud.
            </p>
        <?php endif; ?>
    </div>

    <!-- Panel de Resolución Administrativa (RF-27, DT-56, DT-62) -->
    <?php if ($solicitud->isPendiente()): ?>
        <div style="border: 2px solid #3b82f6; border-radius: 6px; padding: 1.5rem; background: #eff6ff; margin-bottom: 1.5rem;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; color: #1e3a8a; font-weight: 700;">
                Resolución de la Solicitud (RF-27)
            </h3>
            <p style="margin: 0 0 1rem 0; color: #475569; font-size: 0.85rem;">
                Seleccione la decisión administrativa para este permiso. Esta acción actualizará el estado de forma atómica y registrará una entrada inmutable de auditoría (DT-56, DT-57).
            </p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <!-- Formulario de Aprobación -->
                <div style="background: #ffffff; border: 1px solid #86efac; border-radius: 6px; padding: 1.25rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #166534; font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 0.35rem;">
                        <span>&check;</span> Aprobar Solicitud
                    </h4>
                    <p style="margin: 0 0 0.75rem 0; color: #64748b; font-size: 0.8rem;">
                        El permiso quedará oficialmente concedido.
                    </p>
                    <form method="POST" action="<?= e(url('/administracion/permisos/aprobar')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id_solicitud_permiso" value="<?= e($solicitud->id_solicitud_permiso) ?>">
                        <div style="margin-bottom: 0.75rem;">
                            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Comentario de Aprobación (Opcional):</label>
                            <textarea name="comentario" rows="2" maxlength="500" placeholder="Observaciones o visto bueno..." style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;"></textarea>
                        </div>
                        <button type="submit" onclick="return confirm('¿Está seguro de APROBAR esta solicitud de permiso?');" style="width: 100%; padding: 0.6rem 1rem; background: #16a34a; color: #ffffff; border: none; border-radius: 4px; font-weight: 700; font-size: 0.85rem; cursor: pointer;">
                            Confirmar Aprobación
                        </button>
                    </form>
                </div>

                <!-- Formulario de Rechazo -->
                <div style="background: #ffffff; border: 1px solid #fca5a5; border-radius: 6px; padding: 1.25rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #991b1b; font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 0.35rem;">
                        <span>&times;</span> Rechazar Solicitud
                    </h4>
                    <p style="margin: 0 0 0.75rem 0; color: #64748b; font-size: 0.8rem;">
                        La solicitud quedará denegada.
                    </p>
                    <form method="POST" action="<?= e(url('/administracion/permisos/rechazar')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id_solicitud_permiso" value="<?= e($solicitud->id_solicitud_permiso) ?>">
                        <div style="margin-bottom: 0.75rem;">
                            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Motivo del Rechazo (Opcional):</label>
                            <textarea name="comentario" rows="2" maxlength="500" placeholder="Justificación del rechazo..." style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;"></textarea>
                        </div>
                        <button type="submit" onclick="return confirm('¿Está seguro de RECHAZAR esta solicitud de permiso?');" style="width: 100%; padding: 0.6rem 1rem; background: #dc2626; color: #ffffff; border: none; border-radius: 4px; font-weight: 700; font-size: 0.85rem; cursor: pointer;">
                            Confirmar Rechazo
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; padding: 1rem; margin-bottom: 1.5rem; text-align: center;">
            <p style="margin: 0; color: #475569; font-size: 0.9rem; font-weight: 600;">
                Esta solicitud se encuentra en estado terminal (<?= e($solicitud->estado) ?>) y no admite modificaciones adicionales (DT-56).
            </p>
        </div>
    <?php endif; ?>

    <!-- Historial Inmutable de Auditoría (RF-28, DT-56) -->
    <div style="border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;">
        <div style="background: #f8fafc; padding: 0.85rem 1.25rem; border-bottom: 1px solid #e2e8f0;">
            <h3 style="margin: 0; font-size: 1rem; color: #1e293b; font-weight: 600;">Historial Completo de Auditoría (RF-28, RNF-06)</h3>
        </div>
        <div style="padding: 1.25rem;">
            <?php if (empty($solicitud->historial)): ?>
                <p style="margin: 0; color: #64748b; font-size: 0.875rem;">Sin registros en el historial.</p>
            <?php else: ?>
                <div style="position: relative; padding-left: 1.5rem; border-left: 2px solid #e2e8f0;">
                    <?php foreach ($solicitud->historial as $h): ?>
                        <div style="margin-bottom: 1.25rem; position: relative;">
                            <div style="position: absolute; left: -1.95rem; top: 0.25rem; width: 14px; height: 14px; border-radius: 50%; background: #3b82f6; border: 2px solid #ffffff;"></div>

                            <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <div style="font-weight: 600; color: #1e293b; font-size: 0.95rem;">
                                    <?php if ($h->estado_anterior === null): ?>
                                        Registro inicial &rarr; <span style="color: #d97706;"><?= e($h->estado_nuevo) ?></span>
                                    <?php else: ?>
                                        Transición: <span style="text-decoration: line-through; color: #64748b;"><?= e($h->estado_anterior) ?></span> &rarr;
                                        <span style="font-weight: 700; color: <?= $h->estado_nuevo === 'APROBADO' ? '#16a34a' : ($h->estado_nuevo === 'RECHAZADO' ? '#dc2626' : '#d97706') ?>;">
                                            <?= e($h->estado_nuevo) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span style="color: #64748b; font-size: 0.8rem;">
                                    <?= e(date('d/m/Y H:i:s', strtotime($h->fecha_cambio))) ?>
                                </span>
                            </div>

                            <div style="font-size: 0.85rem; color: #475569; margin-bottom: 0.25rem;">
                                <strong>Usuario Actor:</strong> <?= e($h->nombre_actor ?? 'Usuario #' . $h->id_usuario_actor) ?>
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
</section>
