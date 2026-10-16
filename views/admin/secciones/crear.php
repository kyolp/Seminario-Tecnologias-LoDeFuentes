<section style="max-width: 650px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.3rem;">Crear Nueva Sección</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Apertura de sección y asignación obligatoria de capacidad máxima</p>
        </div>
        <a href="<?= e(url('/administracion/secciones')) ?>" style="padding: 0.5rem 1rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">&larr; Volver</a>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div style="padding: 0.75rem 1rem; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 6px; margin-bottom: 1.25rem; font-size: 0.9rem;">
            <?= e($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('/administracion/secciones/crear')) ?>" style="display: flex; flex-direction: column; gap: 1rem;">
        <?= csrf_field() ?>

        <div>
            <label for="id_grado_periodo" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem; color: #374151;">Grado y Período Escolar *</label>
            <select id="id_grado_periodo" name="id_grado_periodo" required style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box;">
                <option value="">-- Seleccione Grado y Período --</option>
                <?php foreach ($gradosPeriodos as $gp): ?>
                    <option value="<?= e((string)$gp->id_grado_periodo) ?>" <?= ((int)($old['id_grado_periodo'] ?? 0) === (int)$gp->id_grado_periodo) ? 'selected' : '' ?>>
                        <?= e($gp->periodo_nombre ?? 'Período #' . $gp->id_periodo_escolar) ?> &raquo; <?= e($gp->grado_nombre ?? 'Grado #' . $gp->id_grado) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['id_grado_periodo'])): ?>
                <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['id_grado_periodo']) ?></span>
            <?php endif; ?>
        </div>

        <div>
            <label for="codigo" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem; color: #374151;">Código de Sección *</label>
            <input type="text" id="codigo" name="codigo" required maxlength="10" placeholder="Ej: A, B, C, SEC-A" value="<?= e($old['codigo'] ?? '') ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; font-family: monospace;">
            <?php if (!empty($errors['codigo'])): ?>
                <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['codigo']) ?></span>
            <?php endif; ?>
        </div>

        <div>
            <label for="nombre" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem; color: #374151;">Nombre Descriptivo *</label>
            <input type="text" id="nombre" name="nombre" required maxlength="50" placeholder="Ej: Sección A Matutina" value="<?= e($old['nombre'] ?? '') ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box;">
            <?php if (!empty($errors['nombre'])): ?>
                <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['nombre']) ?></span>
            <?php endif; ?>
        </div>

        <div>
            <label for="capacidad" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem; color: #374151;">Capacidad Máxima de Estudiantes (cupos) *</label>
            <input type="number" id="capacidad" name="capacidad" required min="1" step="1" placeholder="Ej: 30" value="<?= e((string)($old['capacidad'] ?? '30')) ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box;">
            <small style="color: #64748b; font-size: 0.8rem;">La capacidad debe ser obligatoria, de tipo entero y estrictamente mayor a 0.</small>
            <?php if (!empty($errors['capacidad'])): ?>
                <br><span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['capacidad']) ?></span>
            <?php endif; ?>
        </div>

        <div>
            <label for="habilitada" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem; color: #374151;">Estado de la Sección *</label>
            <select id="habilitada" name="habilitada" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box;">
                <option value="1" <?= (!isset($old['habilitada']) || (int)$old['habilitada'] === 1) ? 'selected' : '' ?>>HABILITADA</option>
                <option value="0" <?= (isset($old['habilitada']) && (int)$old['habilitada'] === 0) ? 'selected' : '' ?>>DESHABILITADA</option>
            </select>
        </div>

        <div style="margin-top: 1rem; display: flex; justify-content: flex-end; gap: 0.5rem;">
            <a href="<?= e(url('/administracion/secciones')) ?>" style="padding: 0.5rem 1rem; background: #e2e8f0; color: #334155; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 0.875rem;">Cancelar</a>
            <button type="submit" style="padding: 0.5rem 1.25rem; background: #1e3a8a; color: #ffffff; border: none; border-radius: 4px; font-weight: 600; font-size: 0.875rem; cursor: pointer;">Guardar Sección</button>
        </div>
    </form>
</section>
