<section style="max-width: 650px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.3rem;">Editar Sección</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Actualización de código, nombre y capacidad de la sección</p>
        </div>
        <a href="<?= e(url('/administracion/secciones')) ?>" style="padding: 0.5rem 1rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">&larr; Volver</a>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div style="padding: 0.75rem 1rem; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 6px; margin-bottom: 1.25rem; font-size: 0.9rem;">
            <?= e($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('/administracion/secciones/editar?id=' . $seccion->id_seccion)) ?>" style="display: flex; flex-direction: column; gap: 1rem;">
        <?= csrf_field() ?>

        <div style="background: #f8fafc; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 0.875rem;">
            <strong>Grado y Período Asociado:</strong><br>
            <span style="color: #1e3a8a; font-weight: 600;"><?= e($seccion->nombre_grado ?? 'Grado #' . $seccion->id_grado_periodo) ?></span> &mdash;
            <span style="color: #64748b;"><?= e($seccion->nombre_periodo ?? '') ?></span>
        </div>

        <div>
            <label for="codigo" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem; color: #374151;">Código de Sección *</label>
            <input type="text" id="codigo" name="codigo" required maxlength="10" value="<?= e($old['codigo'] ?? $seccion->codigo) ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; font-family: monospace;">
            <?php if (!empty($errors['codigo'])): ?>
                <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['codigo']) ?></span>
            <?php endif; ?>
        </div>

        <div>
            <label for="nombre" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem; color: #374151;">Nombre Descriptivo *</label>
            <input type="text" id="nombre" name="nombre" required maxlength="50" value="<?= e($old['nombre'] ?? $seccion->nombre) ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box;">
            <?php if (!empty($errors['nombre'])): ?>
                <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['nombre']) ?></span>
            <?php endif; ?>
        </div>

        <div>
            <label for="capacidad" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem; color: #374151;">Capacidad Máxima (cupos) *</label>
            <input type="number" id="capacidad" name="capacidad" required min="1" step="1" value="<?= e((string)($old['capacidad'] ?? $seccion->capacidad)) ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box;">
            <small style="color: #64748b; font-size: 0.8rem;">La capacidad debe ser obligatoria, de tipo entero y estrictamente mayor a 0.</small>
            <?php if (!empty($errors['capacidad'])): ?>
                <br><span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['capacidad']) ?></span>
            <?php endif; ?>
        </div>

        <div>
            <label for="habilitada" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem; color: #374151;">Estado de la Sección *</label>
            <select id="habilitada" name="habilitada" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box;">
                <option value="1" <?= ((int)($old['habilitada'] ?? $seccion->habilitada) === 1) ? 'selected' : '' ?>>HABILITADA</option>
                <option value="0" <?= ((int)($old['habilitada'] ?? $seccion->habilitada) === 0) ? 'selected' : '' ?>>DESHABILITADA</option>
            </select>
        </div>

        <div style="margin-top: 1rem; display: flex; justify-content: flex-end; gap: 0.5rem;">
            <a href="<?= e(url('/administracion/secciones')) ?>" style="padding: 0.5rem 1rem; background: #e2e8f0; color: #334155; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 0.875rem;">Cancelar</a>
            <button type="submit" style="padding: 0.5rem 1.25rem; background: #1e3a8a; color: #ffffff; border: none; border-radius: 4px; font-weight: 600; font-size: 0.875rem; cursor: pointer;">Actualizar Sección</button>
        </div>
    </form>
</section>
