<section style="max-width: 700px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.3rem;">Editar Asignación Docente</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Actualización de aula y vigencia (DT-33: el docente y sección son inmutables para preservar histórico)</p>
        </div>
        <a href="<?= e(url('/administracion/asignaciones')) ?>" style="color: #64748b; text-decoration: none; font-size: 0.875rem;">&larr; Cancelar y volver</a>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-size: 0.9rem;">
            <?= e($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('/administracion/asignaciones/editar?id=' . $asignacion->id_asignacion_docente)) ?>">
        <?= csrf_field() ?>

        <!-- Datos Informativos Inmutables -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem; margin-bottom: 1.25rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div>
                <label style="display: block; font-weight: 600; font-size: 0.8rem; color: #64748b; margin-bottom: 0.2rem;">Docente Asignado</label>
                <div style="font-weight: 600; color: #0f172a;"><?= e($asignacion->docente_nombre) ?></div>
                <div style="font-size: 0.8rem; color: #64748b; font-family: monospace;"><?= e($asignacion->docente_codigo) ?></div>
            </div>
            <div>
                <label style="display: block; font-weight: 600; font-size: 0.8rem; color: #64748b; margin-bottom: 0.2rem;">Contexto Académico</label>
                <div style="color: #1e3a8a; font-weight: 600;"><?= e($asignacion->periodo_nombre) ?></div>
                <div style="font-size: 0.85rem; color: #334155;"><?= e($asignacion->grado_nombre) ?> &mdash; Sección <strong><?= e($asig->seccion_codigo ?? $asignacion->seccion_codigo) ?></strong></div>
            </div>
        </div>

        <div style="margin-bottom: 1.25rem;">
            <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem; color: #1e293b;">
                Aula Asignada *
            </label>
            <select name="id_aula" required style="width: 100%; padding: 0.55rem; border: 1px solid <?= !empty($errors['id_aula']) ? '#dc2626' : '#cbd5e1' ?>; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                <?php foreach ($aulas as $au): ?>
                    <option value="<?= e((string)$au['id_aula']) ?>" <?= ((int)($old['id_aula'] ?? $asignacion->id_aula) === (int)$au['id_aula']) ? 'selected' : '' ?>>
                        <?= e($au['codigo']) ?> - <?= e($au['nombre'] ?? 'Sin descripción') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['id_aula'])): ?>
                <span style="color: #dc2626; font-size: 0.8rem; display: block; margin-top: 0.25rem;"><?= e($errors['id_aula']) ?></span>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem; color: #1e293b;">
                    Fecha de Inicio *
                </label>
                <input type="date" name="fecha_inicio" value="<?= e($old['fecha_inicio'] ?? $asignacion->fecha_inicio) ?>" required style="width: 100%; padding: 0.55rem; border: 1px solid <?= !empty($errors['fecha_inicio']) ? '#dc2626' : '#cbd5e1' ?>; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                <?php if (!empty($errors['fecha_inicio'])): ?>
                    <span style="color: #dc2626; font-size: 0.8rem; display: block; margin-top: 0.25rem;"><?= e($errors['fecha_inicio']) ?></span>
                <?php endif; ?>
            </div>

            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem; color: #1e293b;">
                    Fecha de Fin (Opcional)
                </label>
                <input type="date" name="fecha_fin" value="<?= e($old['fecha_fin'] ?? ($asignacion->fecha_fin ?? '')) ?>" style="width: 100%; padding: 0.55rem; border: 1px solid <?= !empty($errors['fecha_fin']) ? '#dc2626' : '#cbd5e1' ?>; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                <?php if (!empty($errors['fecha_fin'])): ?>
                    <span style="color: #dc2626; font-size: 0.8rem; display: block; margin-top: 0.25rem;"><?= e($errors['fecha_fin']) ?></span>
                <?php endif; ?>
            </div>

            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem; color: #1e293b;">
                    Estado *
                </label>
                <select name="estado" style="width: 100%; padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                    <option value="ACTIVA" <?= (($old['estado'] ?? $asignacion->estado) === 'ACTIVA') ? 'selected' : '' ?>>ACTIVA</option>
                    <option value="INACTIVA" <?= (($old['estado'] ?? $asignacion->estado) === 'INACTIVA') ? 'selected' : '' ?>>INACTIVA</option>
                </select>
                <?php if (!empty($errors['estado'])): ?>
                    <span style="color: #dc2626; font-size: 0.8rem; display: block; margin-top: 0.25rem;"><?= e($errors['estado']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid #e2e8f0; padding-top: 1.25rem;">
            <a href="<?= e(url('/administracion/asignaciones')) ?>" style="padding: 0.6rem 1.2rem; background: #e2e8f0; color: #334155; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 0.9rem;">Cancelar</a>
            <button type="submit" style="padding: 0.6rem 1.2rem; background: #1e3a8a; color: #fff; border: none; border-radius: 4px; font-weight: 600; font-size: 0.9rem; cursor: pointer;">Guardar Cambios</button>
        </div>
    </form>
</section>
