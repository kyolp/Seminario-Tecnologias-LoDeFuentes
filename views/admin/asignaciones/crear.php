<section style="max-width: 750px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.3rem;">Registrar Nueva Asignación Docente</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Vincular un docente activo a una sección y aula escolar (RF-16)</p>
        </div>
        <a href="<?= e(url('/administracion/asignaciones')) ?>" style="color: #64748b; text-decoration: none; font-size: 0.875rem;">&larr; Cancelar y volver</a>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-size: 0.9rem;">
            <?= e($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('/administracion/asignaciones/crear')) ?>">
        <?= csrf_field() ?>

        <!-- Sección de Selección Académica -->
        <div style="margin-bottom: 1.25rem;">
            <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem; color: #1e293b;">
                Sección Escolar *
            </label>
            <select name="id_seccion" required style="width: 100%; padding: 0.55rem; border: 1px solid <?= !empty($errors['id_seccion']) ? '#dc2626' : '#cbd5e1' ?>; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                <option value="">-- Seleccione la sección a asignar --</option>
                <?php foreach ($secciones as $sec): ?>
                    <?php 
                        $selected = ((int)($old['id_seccion'] ?? 0) === (int)$sec['id_seccion']) ? 'selected' : '';
                        $disabled = ($sec['tiene_docente_activo'] === 1) ? 'disabled style="color: #94a3b8;"' : '';
                        $aviso = ($sec['tiene_docente_activo'] === 1) ? ' [Ya tiene docente: ' . $sec['docente_actual'] . ']' : ' (Disponible)';
                    ?>
                    <option value="<?= e((string)$sec['id_seccion']) ?>" <?= $selected ?> <?= $disabled ?>>
                        <?= e($sec['periodo_nombre']) ?> &rarr; <?= e($sec['grado_nombre']) ?> &rarr; Sección <?= e($sec['codigo']) ?> (Cap: <?= e((string)$sec['capacidad']) ?>)<?= e($aviso) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span style="display: block; color: #64748b; font-size: 0.75rem; margin-top: 0.25rem;">
                Nota: Las secciones con docente activo actual aparecen deshabilitadas para prevenir duplicados (DT-32).
            </span>
            <?php if (!empty($errors['id_seccion'])): ?>
                <span style="color: #dc2626; font-size: 0.8rem; display: block; margin-top: 0.25rem; font-weight: 600;"><?= e($errors['id_seccion']) ?></span>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            <!-- Docente -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem; color: #1e293b;">
                    Docente Responsable *
                </label>
                <select name="id_docente" required style="width: 100%; padding: 0.55rem; border: 1px solid <?= !empty($errors['id_docente']) ? '#dc2626' : '#cbd5e1' ?>; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                    <option value="">-- Seleccione un docente --</option>
                    <?php foreach ($docentes as $doc): ?>
                        <option value="<?= e((string)$doc['id_docente']) ?>" <?= ((int)($old['id_docente'] ?? 0) === (int)$doc['id_docente']) ? 'selected' : '' ?>>
                            <?= e($doc['nombre_completo']) ?> (<?= e($doc['codigo_docente']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['id_docente'])): ?>
                    <span style="color: #dc2626; font-size: 0.8rem; display: block; margin-top: 0.25rem;"><?= e($errors['id_docente']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Aula -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem; color: #1e293b;">
                    Aula Asignada *
                </label>
                <select name="id_aula" required style="width: 100%; padding: 0.55rem; border: 1px solid <?= !empty($errors['id_aula']) ? '#dc2626' : '#cbd5e1' ?>; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                    <option value="">-- Seleccione un aula --</option>
                    <?php foreach ($aulas as $au): ?>
                        <option value="<?= e((string)$au['id_aula']) ?>" <?= ((int)($old['id_aula'] ?? 0) === (int)$au['id_aula']) ? 'selected' : '' ?>>
                            <?= e($au['codigo']) ?> - <?= e($au['nombre'] ?? 'Sin descripción') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['id_aula'])): ?>
                    <span style="color: #dc2626; font-size: 0.8rem; display: block; margin-top: 0.25rem;"><?= e($errors['id_aula']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <!-- Fecha Inicio -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem; color: #1e293b;">
                    Fecha de Inicio *
                </label>
                <input type="date" name="fecha_inicio" value="<?= e($old['fecha_inicio'] ?? date('Y-m-d')) ?>" required style="width: 100%; padding: 0.55rem; border: 1px solid <?= !empty($errors['fecha_inicio']) ? '#dc2626' : '#cbd5e1' ?>; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                <?php if (!empty($errors['fecha_inicio'])): ?>
                    <span style="color: #dc2626; font-size: 0.8rem; display: block; margin-top: 0.25rem;"><?= e($errors['fecha_inicio']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Fecha Fin -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem; color: #1e293b;">
                    Fecha de Fin (Opcional)
                </label>
                <input type="date" name="fecha_fin" value="<?= e($old['fecha_fin'] ?? '') ?>" style="width: 100%; padding: 0.55rem; border: 1px solid <?= !empty($errors['fecha_fin']) ? '#dc2626' : '#cbd5e1' ?>; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                <?php if (!empty($errors['fecha_fin'])): ?>
                    <span style="color: #dc2626; font-size: 0.8rem; display: block; margin-top: 0.25rem;"><?= e($errors['fecha_fin']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Estado Inicial -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem; color: #1e293b;">
                    Estado Inicial *
                </label>
                <select name="estado" style="width: 100%; padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                    <option value="ACTIVA" <?= (($old['estado'] ?? 'ACTIVA') === 'ACTIVA') ? 'selected' : '' ?>>ACTIVA</option>
                    <option value="INACTIVA" <?= (($old['estado'] ?? '') === 'INACTIVA') ? 'selected' : '' ?>>INACTIVA</option>
                </select>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid #e2e8f0; padding-top: 1.25rem;">
            <a href="<?= e(url('/administracion/asignaciones')) ?>" style="padding: 0.6rem 1.2rem; background: #e2e8f0; color: #334155; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 0.9rem;">Cancelar</a>
            <button type="submit" style="padding: 0.6rem 1.2rem; background: #1e3a8a; color: #fff; border: none; border-radius: 4px; font-weight: 600; font-size: 0.9rem; cursor: pointer;">Guardar Asignación</button>
        </div>
    </form>
</section>
