<?php
/**
 * Vista de Gestión de Solicitudes de Permiso Docente — Administración (RF-27).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Variables:
 * @var \App\Models\User $user
 * @var array<int, \App\Models\SolicitudPermiso> $solicitudes
 * @var array<int, \App\Models\Docente> $docentes
 * @var array<string, mixed> $filtros
 * @var string|null $mensajeExito
 * @var string|null $mensajeError
 */
?>

<section style="max-width: 1200px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <!-- Encabezado -->
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.4rem;">Gestión de Permisos Docentes</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Recepción, revisión y resolución administrativa de solicitudes de permiso laboral (RF-27, RNF-06)</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <a href="<?= e(url('/administracion')) ?>" style="padding: 0.5rem 1rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">&larr; Panel General</a>
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

    <!-- Filtros de búsqueda -->
    <form method="GET" action="<?= e(url('/administracion/permisos')) ?>" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1.25rem; margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap: 1rem; align-items: end;">
        <div>
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Estado de Solicitud</label>
            <select name="estado" style="width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                <option value="">-- Todos los Estados --</option>
                <option value="PENDIENTE" <?= (($filtros['estado'] ?? '') === 'PENDIENTE') ? 'selected' : '' ?>>PENDIENTE</option>
                <option value="APROBADO" <?= (($filtros['estado'] ?? '') === 'APROBADO') ? 'selected' : '' ?>>APROBADO</option>
                <option value="RECHAZADO" <?= (($filtros['estado'] ?? '') === 'RECHAZADO') ? 'selected' : '' ?>>RECHAZADO</option>
            </select>
        </div>

        <div>
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Docente</label>
            <select name="id_docente" style="width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                <option value="">-- Todos los Docentes --</option>
                <?php foreach ($docentes as $d): ?>
                    <?php 
                        $docId = is_array($d) ? ($d['id_docente'] ?? 0) : $d->id_docente;
                        $docNom = is_array($d) ? ($d['nombre_completo'] ?? ($d['nombre'] . ' ' . $d['apellido'])) : ($d->nombre_completo ?? ($d->nombre . ' ' . $d->apellido));
                        $docCod = is_array($d) ? ($d['codigo_docente'] ?? '') : ($d->codigo_docente ?? '');
                    ?>
                    <option value="<?= e((string)$docId) ?>" <?= ((int)($filtros['id_docente'] ?? 0) === (int)$docId) ? 'selected' : '' ?>>
                        <?= e($docNom) ?> (<?= e($docCod) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Fecha Solicitada</label>
            <input type="date" name="fecha_solicitada" value="<?= e((string)($filtros['fecha_solicitada'] ?? '')) ?>" style="width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" style="padding: 0.45rem 1rem; background: #2563eb; color: #ffffff; border: none; border-radius: 4px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                Filtrar
            </button>
            <a href="<?= e(url('/administracion/permisos')) ?>" style="padding: 0.45rem 0.85rem; background: #e2e8f0; color: #475569; text-decoration: none; border-radius: 4px; font-size: 0.85rem; display: inline-flex; align-items: center;">
                Limpiar
            </a>
        </div>
    </form>

    <!-- Resumen de Contadores -->
    <?php
        $totalPendientes = count(array_filter($solicitudes, fn($s) => $s->isPendiente()));
        $totalAprobados = count(array_filter($solicitudes, fn($s) => $s->isAprobado()));
        $totalRechazados = count(array_filter($solicitudes, fn($s) => $s->isRechazado()));
    ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 0.75rem 1rem;">
            <div style="font-size: 0.8rem; font-weight: 600; color: #92400e;">PENDIENTES DE ACCIÓN</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #b45309;"><?= $totalPendientes ?></div>
        </div>
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 0.75rem 1rem;">
            <div style="font-size: 0.8rem; font-weight: 600; color: #166534;">APROBADOS</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #15803d;"><?= $totalAprobados ?></div>
        </div>
        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 0.75rem 1rem;">
            <div style="font-size: 0.8rem; font-weight: 600; color: #991b1b;">RECHAZADOS</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #b91c1c;"><?= $totalRechazados ?></div>
        </div>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.75rem 1rem;">
            <div style="font-size: 0.8rem; font-weight: 600; color: #475569;">TOTAL RESULTADOS</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #1e293b;"><?= count($solicitudes) ?></div>
        </div>
    </div>

    <!-- Tabla de Solicitudes -->
    <div style="border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left;">
            <thead>
                <tr style="background: #f1f5f9; color: #475569; border-bottom: 1px solid #e2e8f0;">
                    <th style="padding: 0.75rem 1rem;">ID</th>
                    <th style="padding: 0.75rem 1rem;">Docente</th>
                    <th style="padding: 0.75rem 1rem;">Fecha Solicitada</th>
                    <th style="padding: 0.75rem 1rem;">Motivo</th>
                    <th style="padding: 0.75rem 1rem; text-align: center;">Soporte</th>
                    <th style="padding: 0.75rem 1rem; text-align: center;">Estado</th>
                    <th style="padding: 0.75rem 1rem;">Fecha Solicitud</th>
                    <th style="padding: 0.75rem 1rem; text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($solicitudes)): ?>
                    <tr>
                        <td colspan="8" style="padding: 2.5rem 1rem; text-align: center; color: #64748b;">
                            No se encontraron solicitudes de permiso con los filtros especificados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($solicitudes as $s): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.75rem 1rem; font-weight: 600; color: #1e293b;">#<?= e($s->id_solicitud_permiso) ?></td>
                            <td style="padding: 0.75rem 1rem;">
                                <div style="font-weight: 600; color: #1e293b;"><?= e($s->docente?->getNombreCompleto() ?? 'Docente no disponible') ?></div>
                                <?php if (!empty($s->codigo_docente)): ?>
                                    <div style="font-size: 0.75rem; color: #64748b;"><?= e($s->codigo_docente) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem 1rem; font-weight: 600; color: #2563eb;">
                                <?= e(date('d/m/Y', strtotime($s->fecha_solicitada))) ?>
                            </td>
                            <td style="padding: 0.75rem 1rem; color: #334155;">
                                <div style="font-weight: 500;"><?= e($s->tipo_motivo) ?></div>
                                <div style="font-size: 0.8rem; color: #64748b; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= e($s->descripcion) ?>
                                </div>
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                <?php if ($s->documento !== null): ?>
                                    <span title="<?= e($s->documento->nombre_original) ?>" style="color: #047857; font-weight: 600; font-size: 0.75rem; background: #dcfce7; padding: 0.2rem 0.5rem; border-radius: 4px;">
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
                                <a href="<?= e(url('/administracion/permisos/ver?id=' . $s->id_solicitud_permiso)) ?>" style="background: #2563eb; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 0.8rem; padding: 0.35rem 0.75rem; border-radius: 4px; display: inline-block;">
                                    Gestionar &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
