<div class="card" style="max-width: 800px; margin: 2rem auto; padding: 2rem;">
    <div style="text-align: center; margin-bottom: 2rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 1.5rem;">
        <h2 style="color: #1e3a8a; margin-bottom: 0.5rem;">Formulario de Inscripción Escolar</h2>
        <p style="color: #64748b; font-size: 0.95rem; margin: 0;">
            Escuela Oficial Rural Mixta Lo de Fuentes — Proceso Público de Inscripción en Línea
        </p>
        <?php if ($periodo): ?>
            <div style="display: inline-block; background: #e0f2fe; color: #0369a1; padding: 0.4rem 1rem; border-radius: 9999px; font-size: 0.85rem; font-weight: 600; margin-top: 0.75rem;">
                Ciclo Escolar: <?= e($periodo->nombre) ?> (<?= e($periodo->fecha_inicio) ?> a <?= e($periodo->fecha_fin) ?>)
            </div>
        <?php else: ?>
            <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 6px; margin-top: 1rem;">
                Actualmente no hay períodos escolares habilitados para inscripción. Por favor consulte con la dirección escolar.
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div style="background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <strong>Error:</strong> <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($periodo && !empty($grados)): ?>
        <form action="<?= e(url('inscripcion')) ?>" method="POST" id="form-inscripcion">
            <?= csrf_field() ?>

            <!-- SECCIÓN 1: DATOS DEL ESTUDIANTE -->
            <fieldset style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem;">
                <legend style="font-weight: 700; color: #1e293b; padding: 0 0.5rem;">1. Datos del Estudiante</legend>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label for="estudiante_nombres" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;">Nombres *</label>
                        <input type="text" id="estudiante_nombres" name="estudiante_nombres" required maxlength="100"
                               value="<?= e($old['estudiante_nombres'] ?? '') ?>"
                               style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;">
                        <?php if (isset($errors['estudiante_nombres'])): ?>
                            <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['estudiante_nombres']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="estudiante_apellidos" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;">Apellidos *</label>
                        <input type="text" id="estudiante_apellidos" name="estudiante_apellidos" required maxlength="100"
                               value="<?= e($old['estudiante_apellidos'] ?? '') ?>"
                               style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;">
                        <?php if (isset($errors['estudiante_apellidos'])): ?>
                            <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['estudiante_apellidos']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label for="estudiante_fecha_nacimiento" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;">Fecha de Nacimiento</label>
                        <input type="date" id="estudiante_fecha_nacimiento" name="estudiante_fecha_nacimiento"
                               value="<?= e($old['estudiante_fecha_nacimiento'] ?? '') ?>"
                               style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;">
                        <?php if (isset($errors['estudiante_fecha_nacimiento'])): ?>
                            <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['estudiante_fecha_nacimiento']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="codigo_estudiante" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;">Código Personal / MINEDUC (opcional)</label>
                        <input type="text" id="codigo_estudiante" name="codigo_estudiante" maxlength="30"
                               placeholder="Ej. B452ABC (si ya posee uno)"
                               value="<?= e($old['codigo_estudiante'] ?? '') ?>"
                               style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;">
                        <?php if (isset($errors['codigo_estudiante'])): ?>
                            <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['codigo_estudiante']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </fieldset>

            <!-- SECCIÓN 2: DATOS DEL ENCARGADO -->
            <fieldset style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem;">
                <legend style="font-weight: 700; color: #1e293b; padding: 0 0.5rem;">2. Datos del Padre, Madre o Encargado</legend>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label for="encargado_nombres" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;">Nombres del Encargado *</label>
                        <input type="text" id="encargado_nombres" name="encargado_nombres" required maxlength="100"
                               value="<?= e($old['encargado_nombres'] ?? '') ?>"
                               style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;">
                        <?php if (isset($errors['encargado_nombres'])): ?>
                            <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['encargado_nombres']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="encargado_apellidos" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;">Apellidos del Encargado *</label>
                        <input type="text" id="encargado_apellidos" name="encargado_apellidos" required maxlength="100"
                               value="<?= e($old['encargado_apellidos'] ?? '') ?>"
                               style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;">
                        <?php if (isset($errors['encargado_apellidos'])): ?>
                            <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['encargado_apellidos']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div>
                        <label for="parentesco" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;">Parentesco *</label>
                        <select id="parentesco" name="parentesco" required
                                style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; background: #fff;">
                            <option value="">-- Seleccionar --</option>
                            <?php foreach (['MADRE', 'PADRE', 'TUTOR LEGAL', 'ABUELO/A', 'TIO/A', 'HERMANO/A', 'OTRO'] as $p): ?>
                                <option value="<?= e($p) ?>" <?= ($old['parentesco'] ?? '') === $p ? 'selected' : '' ?>><?= e($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['parentesco'])): ?>
                            <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['parentesco']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="encargado_telefono" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;">Teléfono de Contacto</label>
                        <input type="tel" id="encargado_telefono" name="encargado_telefono" maxlength="30" placeholder="Ej. 5555-1234"
                               value="<?= e($old['encargado_telefono'] ?? '') ?>"
                               style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;">
                    </div>
                    <div>
                        <label for="encargado_correo" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;">Correo Electrónico</label>
                        <input type="email" id="encargado_correo" name="encargado_correo" maxlength="150" placeholder="correo@ejemplo.com"
                               value="<?= e($old['encargado_correo'] ?? '') ?>"
                               style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;">
                        <?php if (isset($errors['encargado_correo'])): ?>
                            <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['encargado_correo']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </fieldset>

            <!-- SECCIÓN 3: SELECCIÓN ACADÉMICA Y CONTROL DE CUPOS -->
            <fieldset style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem;">
                <legend style="font-weight: 700; color: #1e293b; padding: 0 0.5rem;">3. Grado y Sección Solicitada</legend>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label for="select_grado_periodo" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;">Grado Escolar *</label>
                        <select id="select_grado_periodo" name="id_grado_periodo" required
                                style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; background: #fff;">
                            <option value="">-- Seleccionar Grado --</option>
                            <?php foreach ($grados as $g): ?>
                                <option value="<?= e($g['id_grado_periodo']) ?>" <?= ((int)($old['id_grado_periodo'] ?? 0) === (int)$g['id_grado_periodo']) ? 'selected' : '' ?>>
                                    <?= e($g['nombre']) ?> (<?= e($g['codigo']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="id_seccion" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;">Sección con Cupo Disponible *</label>
                        <select id="id_seccion" name="id_seccion" required
                                style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; background: #fff;">
                            <option value="">-- Primero elija un grado escolar --</option>
                        </select>
                        <?php if (isset($errors['id_seccion'])): ?>
                            <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['id_seccion']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="aviso-cupos" style="display: none; padding: 0.75rem; border-radius: 6px; font-size: 0.85rem; margin-top: 0.5rem;"></div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.75rem; border-radius: 6px; font-size: 0.8rem; color: #64748b; margin-top: 0.75rem;">
                    <strong>Nota importante sobre disponibilidad:</strong> Los cupos mostrados son informativos. La reserva y confirmación final de la plaza se realiza de forma estricta e indivisible en el servidor al momento de enviar el formulario.
                </div>
            </fieldset>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem;">
                <a href="<?= e(url('inscripcion/consultar')) ?>" style="color: #2563eb; text-decoration: none; font-size: 0.9rem; font-weight: 500;">
                    &larr; ¿Ya tienes un código? Consulta tu inscripción
                </a>
                <button type="submit" id="btn-submit"
                        style="background: #1e3a8a; color: #fff; padding: 0.8rem 2rem; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                    Confirmar Inscripción
                </button>
            </div>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var selectGrado = document.getElementById('select_grado_periodo');
            var selectSeccion = document.getElementById('id_seccion');
            var avisoCupos = document.getElementById('aviso-cupos');
            var oldSeccionId = "<?= e($old['id_seccion'] ?? '') ?>";

            function cargarSecciones(idGradoPeriodo) {
                if (!idGradoPeriodo) {
                    selectSeccion.innerHTML = '<option value="">-- Primero elija un grado escolar --</option>';
                    avisoCupos.style.display = 'none';
                    return;
                }

                selectSeccion.innerHTML = '<option value="">Cargando secciones disponibles...</option>';
                avisoCupos.style.display = 'none';

                fetch("<?= e(url('inscripcion/secciones-disponibles')) ?>?id_grado_periodo=" + idGradoPeriodo)
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        selectSeccion.innerHTML = '';

                        if (!data.success || !data.secciones || data.secciones.length === 0) {
                            selectSeccion.innerHTML = '<option value="">No hay secciones con cupos disponibles</option>';
                            avisoCupos.style.display = 'block';
                            avisoCupos.style.background = '#fef2f2';
                            avisoCupos.style.color = '#991b1b';
                            avisoCupos.innerHTML = '<strong>Aviso:</strong> No existen secciones con cupos disponibles para el grado seleccionado.';
                            return;
                        }

                        selectSeccion.innerHTML = '<option value="">-- Seleccionar Sección --</option>';
                        data.secciones.forEach(function(sec) {
                            var opt = document.createElement('option');
                            opt.value = sec.id_seccion;
                            opt.textContent = 'Sección ' + sec.codigo + (sec.nombre ? ' (' + sec.nombre + ')' : '') + ' — ' + sec.disponibles + ' cupos disponibles';
                            if (String(sec.id_seccion) === String(oldSeccionId)) {
                                opt.selected = true;
                            }
                            selectSeccion.appendChild(opt);
                        });

                        avisoCupos.style.display = 'block';
                        avisoCupos.style.background = '#f0fdf4';
                        avisoCupos.style.color = '#166534';
                        avisoCupos.innerHTML = 'Se encontraron <strong>' + data.secciones.length + '</strong> secciones habilitadas con cupos disponibles.';
                    })
                    .catch(function(err) {
                        selectSeccion.innerHTML = '<option value="">Error al cargar secciones</option>';
                    });
            }

            selectGrado.addEventListener('change', function() {
                oldSeccionId = '';
                cargarSecciones(this.value);
            });

            if (selectGrado.value) {
                cargarSecciones(selectGrado.value);
            }
        });
        </script>
    <?php endif; ?>
</div>
