<section style="max-width: 700px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #7c3aed; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; color: #5b21b6; font-size: 1.3rem;">Reemplazo Atómico de Docente</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Cierre de asignación previa y alta de nuevo docente bajo transacción segura (DT-34)</p>
        </div>
        <a href="<?= e(url('/administracion/asignaciones')) ?>" style="color: #64748b; text-decoration: none; font-size: 0.875rem;">&larr; Cancelar</a>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-size: 0.9rem;">
            <?= e($errors['general']) ?>
        </div>
    <?php endif; ?>

    <!-- Contexto Actual de la Sección -->
    <div style="background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 6px; padding: 1.25rem; margin-bottom: 1.5rem;">
        <h3 style="margin-top: 0; color: #5b21b6; font-size: 1rem;">Asignación Actual a Modificar</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem; color: #334155;">
            <div>
                <strong>Sección:</strong> <?= e($asignacion->grado_nombre) ?> &mdash; Sec. <strong><?= e($asignacion->seccion_codigo) ?></strong><br>
                <strong>Período:</strong> <?= e($asignacion->periodo_nombre) ?>
            </div>
            <div>
                <strong>Docente Saliente:</strong> <?= e($asignacion->docente_nombre) ?><br>
                <strong>Código:</strong> <?= e($asignacion->docente_codigo) ?>
            </div>
        </div>
    </div>

    <form method="POST" action="<?= e(url('/administracion/asignaciones/reemplazar?id=' . $asignacion->id_asignacion_docente)) ?>">
        <?= csrf_field() ?>

        <div style="margin-bottom: 1.25rem;">
            <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem; color: #1e293b;">
                Nuevo Docente Responsable *
            </label>
            <select name="nuevo_id_docente" required style="width: 100%; padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                <option value="">-- Seleccione el nuevo docente --</option>
                <?php foreach ($docentes as $doc): ?>
                    <?php if ((int)$doc['id_docente'] !== $asignacion->id_docente): ?>
                        <option value="<?= e((string)$doc['id_docente']) ?>" <?= ((int)($old['nuevo_id_docente'] ?? 0) === (int)$doc['id_docente']) ? 'selected' : '' ?>>
                            <?= e($doc['nombre_completo']) ?> (<?= e($doc['codigo_docente']) ?>)
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem; color: #1e293b;">
                    Aula *
                </label>
                <select name="id_aula" required style="width: 100%; padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                    <?php foreach ($aulas as $au): ?>
                        <option value="<?= e((string)$au['id_aula']) ?>" <?= ((int)($old['id_aula'] ?? $asignacion->id_aula) === (int)$au['id_aula']) ? 'selected' : '' ?>>
                            <?= e($au['codigo']) ?> - <?= e($au['nombre'] ?? 'Sin descripción') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem; color: #1e293b;">
                    Fecha de Inicio de Nueva Asignación *
                </label>
                <input type="date" name="fecha_inicio" value="<?= e($old['fecha_inicio'] ?? date('Y-m-d')) ?>" required style="width: 100%; padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
            </div>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 0.75rem 1rem; margin-bottom: 1.5rem; font-size: 0.8rem; color: #64748b;">
            💡 <strong>Garantía de Integridad:</strong> La asignación del docente saliente se cerrará automáticamente registrando su fecha de finalización previa y la nueva asignación se activará simultáneamente en una sola transacción indivisible (DT-34). Si ocurre algún error, se ejecutará rollback automático sin afectar el estado anterior.
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid #e2e8f0; padding-top: 1.25rem;">
            <a href="<?= e(url('/administracion/asignaciones')) ?>" style="padding: 0.6rem 1.2rem; background: #e2e8f0; color: #334155; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 0.9rem;">Cancelar</a>
            <button type="submit" style="padding: 0.6rem 1.2rem; background: #7c3aed; color: #fff; border: none; border-radius: 4px; font-weight: 600; font-size: 0.9rem; cursor: pointer;">Ejecutar Reemplazo Atómico</button>
        </div>
    </form>
</section>
