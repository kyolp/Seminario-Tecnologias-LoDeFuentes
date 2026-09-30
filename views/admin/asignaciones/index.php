<section style="max-width: 1200px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.4rem;">Gestión de Asignaciones Docentes</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Asignación de docentes a grados, secciones y aulas por ciclo escolar (RF-16)</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <a href="<?= e(url('/administracion')) ?>" style="padding: 0.5rem 1rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">&larr; Panel General</a>
            <a href="<?= e(url('/administracion/asignaciones/crear')) ?>" style="padding: 0.5rem 1rem; background: #16a34a; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">+ Nueva Asignación</a>
        </div>
    </div>

    <?php if (!empty($flash_success)): ?>
        <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.25rem; font-size: 0.9rem;">
            <?= e($flash_success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($flash_error)): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.25rem; font-size: 0.9rem;">
            <?= e($flash_error) ?>
        </div>
    <?php endif; ?>

    <!-- Filtros de búsqueda -->
    <form method="GET" action="<?= e(url('/administracion/asignaciones')) ?>" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem; margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap: 0.75rem; align-items: end;">
        <div>
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Período Escolar</label>
            <select name="periodo_id" style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                <option value="">-- Todos los Períodos --</option>
                <?php foreach ($periodos as $p): ?>
                    <option value="<?= e((string)$p['id_periodo_escolar']) ?>" <?= ((int)($filtros['periodo_id'] ?? 0) === (int)$p['id_periodo_escolar']) ? 'selected' : '' ?>>
                        <?= e($p['nombre']) ?> (<?= e($p['estado']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Docente</label>
            <select name="docente_id" style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                <option value="">-- Todos los Docentes --</option>
                <?php foreach ($docentes as $d): ?>
                    <option value="<?= e((string)$d['id_docente']) ?>" <?= ((int)($filtros['docente_id'] ?? 0) === (int)$d['id_docente']) ? 'selected' : '' ?>>
                        <?= e($d['nombre_completo']) ?> (<?= e($d['codigo_docente']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Estado</label>
            <select name="estado" style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                <option value="">-- Todos los Estados --</option>
                <option value="ACTIVA" <?= (($filtros['estado'] ?? '') === 'ACTIVA') ? 'selected' : '' ?>>ACTIVA</option>
                <option value="INACTIVA" <?= (($filtros['estado'] ?? '') === 'INACTIVA') ? 'selected' : '' ?>>INACTIVA</option>
            </select>
        </div>

        <div>
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Buscar texto</label>
            <input type="text" name="q" value="<?= e($filtros['q'] ?? '') ?>" placeholder="Docente, sección, aula..." style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
        </div>

        <div style="display: flex; gap: 0.35rem;">
            <button type="submit" style="padding: 0.45rem 1rem; background: #1e3a8a; color: #fff; border: none; border-radius: 4px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">Filtrar</button>
            <a href="<?= e(url('/administracion/asignaciones')) ?>" style="padding: 0.45rem 0.75rem; background: #e2e8f0; color: #475569; text-decoration: none; border-radius: 4px; font-size: 0.85rem;">Limpiar</a>
        </div>
    </form>

    <!-- Tabla de resultados -->
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f1f5f9; border-bottom: 2px solid #cbd5e1; text-align: left;">
                    <th style="padding: 0.75rem; color: #334155;">ID</th>
                    <th style="padding: 0.75rem; color: #334155;">Docente Asignado</th>
                    <th style="padding: 0.75rem; color: #334155;">Período / Grado</th>
                    <th style="padding: 0.75rem; color: #334155;">Sección</th>
                    <th style="padding: 0.75rem; color: #334155;">Aula</th>
                    <th style="padding: 0.75rem; color: #334155;">Vigencia</th>
                    <th style="padding: 0.75rem; color: #334155;">Estado</th>
                    <th style="padding: 0.75rem; color: #334155; text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($asignaciones)): ?>
                    <tr>
                        <td colspan="8" style="padding: 2rem; text-align: center; color: #64748b;">
                            No se encontraron asignaciones docentes registradas con los criterios seleccionados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($asignaciones as $asig): ?>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 0.75rem; font-weight: 600; color: #64748b;">#<?= e((string)$asig->id_asignacion_docente) ?></td>
                            <td style="padding: 0.75rem;">
                                <div style="font-weight: 600; color: #0f172a;"><?= e($asig->docente_nombre) ?></div>
                                <div style="font-size: 0.8rem; color: #64748b; font-family: monospace;"><?= e($asig->docente_codigo) ?></div>
                            </td>
                            <td style="padding: 0.75rem;">
                                <div style="color: #1e3a8a; font-weight: 600;"><?= e($asig->periodo_nombre) ?></div>
                                <div style="font-size: 0.8rem; color: #475569;"><?= e($asig->grado_nombre) ?></div>
                            </td>
                            <td style="padding: 0.75rem;">
                                <span style="font-weight: 700; color: #1e293b; background: #f1f5f9; padding: 0.2rem 0.5rem; border-radius: 4px; font-family: monospace;">
                                    Sec. <?= e($asig->seccion_codigo) ?>
                                </span>
                                <?php if (!empty($asig->seccion_nombre)): ?>
                                    <div style="font-size: 0.8rem; color: #64748b;"><?= e($asig->seccion_nombre) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem;">
                                <div style="font-weight: 600; color: #334155;"><?= e($asig->aula_codigo) ?></div>
                                <?php if (!empty($asig->aula_nombre)): ?>
                                    <div style="font-size: 0.8rem; color: #64748b;"><?= e($asig->aula_nombre) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem; font-size: 0.85rem; color: #475569;">
                                <div>Desde: <strong><?= e($asig->fecha_inicio) ?></strong></div>
                                <?php if (!empty($asig->fecha_fin)): ?>
                                    <div>Hasta: <strong><?= e($asig->fecha_fin) ?></strong></div>
                                <?php else: ?>
                                    <div style="color: #166534; font-size: 0.75rem;">(Vigente)</div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem;">
                                <?php if ($asig->isActiva()): ?>
                                    <span style="background: #dcfce7; color: #166534; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">ACTIVA</span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #64748b; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">INACTIVA</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem; text-align: right; white-space: nowrap;">
                                <div style="display: inline-flex; gap: 0.35rem; align-items: center;">
                                    <a href="<?= e(url('/administracion/asignaciones/ver?id=' . $asig->id_asignacion_docente)) ?>" style="padding: 0.25rem 0.5rem; background: #3b82f6; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">Ver</a>
                                    <a href="<?= e(url('/administracion/asignaciones/editar?id=' . $asig->id_asignacion_docente)) ?>" style="padding: 0.25rem 0.5rem; background: #0d9488; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">Editar</a>
                                    
                                    <?php if ($asig->isActiva()): ?>
                                        <a href="<?= e(url('/administracion/asignaciones/reemplazar?id=' . $asig->id_asignacion_docente)) ?>" style="padding: 0.25rem 0.5rem; background: #7c3aed; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.8rem; font-weight: 600;" title="Reemplazar docente de forma atómica">Reemplazar</a>
                                        
                                        <form method="POST" action="<?= e(url('/administracion/asignaciones/estado?id=' . $asig->id_asignacion_docente)) ?>" style="display: inline;" onsubmit="return confirm('¿Confirma que desea cerrar/desactivar esta asignación? El registro se conservará en el historial.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="nuevo_estado" value="INACTIVA">
                                            <button type="submit" style="padding: 0.25rem 0.5rem; background: #dc2626; color: #fff; border: none; border-radius: 4px; font-size: 0.8rem; font-weight: 600; cursor: pointer;">Cerrar</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="<?= e(url('/administracion/asignaciones/estado?id=' . $asig->id_asignacion_docente)) ?>" style="display: inline;" onsubmit="return confirm('¿Desea reactivar esta asignación para la sección?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="nuevo_estado" value="ACTIVA">
                                            <button type="submit" style="padding: 0.25rem 0.5rem; background: #16a34a; color: #fff; border: none; border-radius: 4px; font-size: 0.8rem; font-weight: 600; cursor: pointer;">Activar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
