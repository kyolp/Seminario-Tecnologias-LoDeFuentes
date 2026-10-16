<?php
$old = $old ?? [];
$errors = $errors ?? [];

$fechaVisible = static function ($valor): string {
    $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $valor);
    return $fecha !== false ? $fecha->format('d/m/Y') : (string) $valor;
};

$camposEstudiante = [
    ['estudiante_nombres', 'Nombres', 'text', true, 100],
    ['estudiante_apellidos', 'Apellidos', 'text', true, 100],
];

$camposEncargado = [
    ['encargado_nombres', 'Nombres del encargado', 'text', true, 100],
    ['encargado_apellidos', 'Apellidos del encargado', 'text', true, 100],
];
?>

<section class="inscripcion-pagina" aria-labelledby="titulo-inscripcion">
    <header class="inscripcion-cabecera">
        <span class="inscripcion-etiqueta">ESCUELA LO DE FUENTES</span>
        <h1 id="titulo-inscripcion">Formulario de Inscripción Escolar</h1>
        <p>Completa los datos del estudiante y elige su grado y sección.</p>

        <?php if ($periodo): ?>
            <div class="inscripcion-ciclo">
                <strong><?= e($periodo->nombre) ?></strong>
                <span>
                    <?= e($fechaVisible($periodo->fecha_inicio)) ?>
                    — <?= e($fechaVisible($periodo->fecha_fin)) ?>
                </span>
            </div>
        <?php endif; ?>
    </header>

    <div class="inscripcion-contenido">
        <?php if (!$periodo): ?>
            <div class="inscripcion-alerta" role="status">
                Actualmente no hay un período habilitado para inscripciones.
                Consulta con la dirección de la escuela.
            </div>
        <?php elseif (empty($grados)): ?>
            <div class="inscripcion-alerta" role="status">
                Actualmente no hay grados habilitados para inscripción.
                Consulta con la dirección de la escuela.
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="inscripcion-alerta inscripcion-alerta-error" role="alert">
                <strong>No se pudo completar la inscripción.</strong>
                <?= e($errorMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="inscripcion-alerta inscripcion-alerta-error" role="alert">
                <strong>Revisa los siguientes datos:</strong>
                <ul>
                    <?php foreach ($errors as $mensaje): ?>
                        <li><?= e($mensaje) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($periodo && !empty($grados)): ?>
            <p class="inscripcion-obligatorios">
                Los campos con <span>*</span> son obligatorios.
            </p>

            <form action="<?= e(url('inscripcion')) ?>"
                  method="POST" id="form-inscripcion">
                <?= csrf_field() ?>

                <fieldset class="inscripcion-bloque">
                    <legend>
                        <span class="inscripcion-numero">1</span>
                        Datos del estudiante
                    </legend>

                    <p class="inscripcion-descripcion">
                        Escribe los nombres y apellidos completos.
                    </p>

                    <div class="inscripcion-grid">
                        <?php foreach ($camposEstudiante as [$nombre, $etiqueta, $tipo, $obligatorio, $maximo]): ?>
                            <div class="inscripcion-campo">
                                <label for="<?= e($nombre) ?>">
                                    <?= e($etiqueta) ?> <span>*</span>
                                </label>
                                <input
                                    type="<?= e($tipo) ?>"
                                    id="<?= e($nombre) ?>"
                                    name="<?= e($nombre) ?>"
                                    maxlength="<?= (int) $maximo ?>"
                                    value="<?= e($old[$nombre] ?? '') ?>"
                                    required
                                    <?= isset($errors[$nombre])
                                        ? 'aria-invalid="true" aria-describedby="error-' . e($nombre) . '"'
                                        : '' ?>
                                >
                                <?php if (isset($errors[$nombre])): ?>
                                    <small class="inscripcion-error"
                                           id="error-<?= e($nombre) ?>">
                                        <?= e($errors[$nombre]) ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="inscripcion-campo">
                            <label for="estudiante_fecha_nacimiento">
                                Fecha de nacimiento
                            </label>
                            <input
                                type="date"
                                id="estudiante_fecha_nacimiento"
                                name="estudiante_fecha_nacimiento"
                                value="<?= e($old['estudiante_fecha_nacimiento'] ?? '') ?>"
                            >
                            <?php if (isset($errors['estudiante_fecha_nacimiento'])): ?>
                                <small class="inscripcion-error">
                                    <?= e($errors['estudiante_fecha_nacimiento']) ?>
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="inscripcion-campo">
                            <label for="codigo_estudiante">
                                Código Personal / MINEDUC (opcional)
                            </label>
                            <input
                                type="text"
                                id="codigo_estudiante"
                                name="codigo_estudiante"
                                maxlength="30"
                                placeholder="Ej. B452ABC (si ya posee uno)"
                                value="<?= e($old['codigo_estudiante'] ?? '') ?>"
                            >
                            <?php if (isset($errors['codigo_estudiante'])): ?>
                                <small class="inscripcion-error">
                                    <?= e($errors['codigo_estudiante']) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="inscripcion-bloque">
                    <legend>
                        <span class="inscripcion-numero">2</span>
                        Datos del encargado
                    </legend>

                    <p class="inscripcion-descripcion">
                        Indica los datos del padre, madre o responsable.
                    </p>

                    <div class="inscripcion-grid">
                        <?php foreach ($camposEncargado as [$nombre, $etiqueta, $tipo, $obligatorio, $maximo]): ?>
                            <div class="inscripcion-campo">
                                <label for="<?= e($nombre) ?>">
                                    <?= e($etiqueta) ?> <span>*</span>
                                </label>
                                <input
                                    type="<?= e($tipo) ?>"
                                    id="<?= e($nombre) ?>"
                                    name="<?= e($nombre) ?>"
                                    maxlength="<?= (int) $maximo ?>"
                                    value="<?= e($old[$nombre] ?? '') ?>"
                                    required
                                    <?= isset($errors[$nombre])
                                        ? 'aria-invalid="true" aria-describedby="error-' . e($nombre) . '"'
                                        : '' ?>
                                >
                                <?php if (isset($errors[$nombre])): ?>
                                    <small class="inscripcion-error"
                                           id="error-<?= e($nombre) ?>">
                                        <?= e($errors[$nombre]) ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="inscripcion-campo">
                            <label for="parentesco">
                                Parentesco <span>*</span>
                            </label>
                            <select id="parentesco" name="parentesco" required>
                                <option value="">Selecciona el parentesco</option>
                                <?php foreach (['MADRE', 'PADRE', 'TUTOR LEGAL', 'ABUELO/A', 'TIO/A', 'HERMANO/A', 'OTRO'] as $p): ?>
                                    <option value="<?= e($p) ?>"
                                        <?= ($old['parentesco'] ?? '') === $p ? 'selected' : '' ?>>
                                        <?= e($p) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['parentesco'])): ?>
                                <small class="inscripcion-error">
                                    <?= e($errors['parentesco']) ?>
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="inscripcion-campo">
                            <label for="encargado_telefono">Teléfono de contacto</label>
                            <input type="tel"
                                   id="encargado_telefono"
                                   name="encargado_telefono"
                                   maxlength="30"
                                   placeholder="Ej. 5555-1234"
                                   value="<?= e($old['encargado_telefono'] ?? '') ?>">
                            <?php if (isset($errors['encargado_telefono'])): ?>
                                <small class="inscripcion-error">
                                    <?= e($errors['encargado_telefono']) ?>
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="inscripcion-campo inscripcion-ancho">
                            <label for="encargado_correo">Correo electrónico</label>
                            <input type="email"
                                   id="encargado_correo"
                                   name="encargado_correo"
                                   maxlength="150"
                                   placeholder="correo@ejemplo.com"
                                   value="<?= e($old['encargado_correo'] ?? '') ?>">
                            <?php if (isset($errors['encargado_correo'])): ?>
                                <small class="inscripcion-error">
                                    <?= e($errors['encargado_correo']) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="inscripcion-bloque">
                    <legend>
                        <span class="inscripcion-numero">3</span>
                        Grado y sección
                    </legend>

                    <p class="inscripcion-descripcion">
                        Selecciona un grado para consultar sus secciones disponibles.
                    </p>

                    <div class="inscripcion-grid">
                        <div class="inscripcion-campo">
                            <label for="select_grado_periodo">
                                Grado escolar <span>*</span>
                            </label>
                            <select id="select_grado_periodo"
                                    name="id_grado_periodo" required>
                                <option value="">Selecciona un grado</option>
                                <?php foreach ($grados as $g): ?>
                                    <option
                                        value="<?= e($g['id_grado_periodo']) ?>"
                                        <?= (int) ($old['id_grado_periodo'] ?? 0) === (int) $g['id_grado_periodo'] ? 'selected' : '' ?>>
                                        <?= e($g['nombre']) ?> (<?= e($g['codigo']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['id_grado_periodo'])): ?>
                                <small class="inscripcion-error">
                                    <?= e($errors['id_grado_periodo']) ?>
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="inscripcion-campo">
                            <label for="id_seccion">
                                Sección disponible <span>*</span>
                            </label>
                            <select id="id_seccion" name="id_seccion"
                                    required disabled>
                                <option value="">Primero selecciona un grado</option>
                            </select>
                            <?php if (isset($errors['id_seccion'])): ?>
                                <small class="inscripcion-error">
                                    <?= e($errors['id_seccion']) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="aviso-cupos" class="inscripcion-aviso"
                         role="status" aria-live="polite" hidden></div>

                    <p class="inscripcion-nota">
                        El cupo se confirma al completar la inscripción,
                        según disponibilidad.
                    </p>
                </fieldset>

                <div class="inscripcion-final">
                    <div>
                        <strong>Revisa los datos antes de confirmar.</strong>
                        <p>Al finalizar podrás descargar el comprobante.</p>
                    </div>

                    <button type="submit" id="btn-submit"
                            class="inscripcion-confirmar" disabled>
                        Confirmar inscripción
                        <span aria-hidden="true">→</span>
                    </button>
                </div>

                <p class="inscripcion-consulta">
                    ¿Ya realizaste la inscripción?
                    <a href="<?= e(url('inscripcion/consultar')) ?>">
                        Consulta tu comprobante
                    </a>
                </p>
            </form>

            <script>
            document.addEventListener('DOMContentLoaded', () => {
                const grado = document.getElementById('select_grado_periodo');
                const seccion = document.getElementById('id_seccion');
                const aviso = document.getElementById('aviso-cupos');
                const boton = document.getElementById('btn-submit');

                const endpoint = <?= json_encode(
                    url('inscripcion/secciones-disponibles'),
                    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                ) ?>;

                let seccionAnterior = <?= json_encode(
                    (string) ($old['id_seccion'] ?? ''),
                    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                ) ?>;

                let solicitudActual = 0;

                function mostrarAviso(texto, error = false) {
                    aviso.textContent = texto;
                    aviso.hidden = false;
                    aviso.classList.toggle('inscripcion-aviso-error', error);
                }

                function actualizarBoton() {
                    boton.disabled = seccion.disabled || !seccion.value;
                }

                async function cargarSecciones(idGrado) {
                    const numeroSolicitud = ++solicitudActual;

                    seccion.disabled = true;
                    boton.disabled = true;
                    aviso.hidden = true;

                    seccion.replaceChildren(new Option(
                        idGrado
                            ? 'Cargando secciones…'
                            : 'Primero selecciona un grado',
                        ''
                    ));

                    if (!idGrado) return;

                    try {
                        const respuesta = await fetch(
                            endpoint + '?id_grado_periodo='
                            + encodeURIComponent(idGrado)
                        );

                        if (!respuesta.ok) {
                            throw new Error('No se pudo consultar la disponibilidad.');
                        }

                        const datos = await respuesta.json();

                        // Ignorar respuestas de un grado seleccionado anteriormente.
                        if (numeroSolicitud !== solicitudActual) return;

                        if (!datos.success || !Array.isArray(datos.secciones)) {
                            throw new Error('Respuesta de disponibilidad inválida.');
                        }

                        const disponibles = datos.secciones.filter(
                            item => Number(item.disponibles) > 0
                        );

                        if (disponibles.length === 0) {
                            seccion.replaceChildren(new Option(
                                'Sin secciones disponibles', ''
                            ));
                            mostrarAviso(
                                'Este grado no tiene cupos disponibles por el momento.',
                                true
                            );
                            return;
                        }

                        seccion.replaceChildren(new Option(
                            'Selecciona una sección', ''
                        ));

                        disponibles.forEach(item => {
                            const texto = 'Sección ' + item.codigo
                                + (item.nombre ? ' · ' + item.nombre : '')
                                + ' — ' + item.disponibles + ' cupos';

                            const opcion = new Option(
                                texto, String(item.id_seccion)
                            );

                            opcion.selected =
                                String(item.id_seccion) === seccionAnterior;

                            seccion.add(opcion);
                        });

                        seccion.disabled = false;
                        seccionAnterior = '';
                        actualizarBoton();

                        mostrarAviso(
                            'Selecciona una sección con cupo disponible.'
                        );
                    } catch (error) {
                        if (numeroSolicitud !== solicitudActual) return;

                        seccion.replaceChildren(new Option(
                            'No se pudieron cargar las secciones', ''
                        ));

                        mostrarAviso(
                            'No pudimos consultar los cupos. Vuelve a seleccionar el grado para intentarlo otra vez.',
                            true
                        );
                    }
                }

                grado.addEventListener('change', () => {
                    seccionAnterior = '';
                    cargarSecciones(grado.value);
                });

                seccion.addEventListener('change', actualizarBoton);

                if (grado.value) {
                    cargarSecciones(grado.value);
                }
            });
            </script>
        <?php endif; ?>
    </div>
</section>