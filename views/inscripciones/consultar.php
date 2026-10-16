<?php
$resultado = $resultado ?? null;

$estado = strtoupper(trim((string) ($resultado['estado'] ?? '')));
$confirmada = $estado === 'CONFIRMADA';

$fechaRegistro = (string) ($resultado['fecha_inscripcion'] ?? '');
$fecha = \DateTimeImmutable::createFromFormat(
    '!Y-m-d',
    substr($fechaRegistro, 0, 10)
);

$fechaVisible = $fecha !== false
    ? $fecha->format('d/m/Y')
    : $fechaRegistro;
?>

<section class="consulta-pagina" aria-labelledby="titulo-consulta">
    <header class="consulta-cabecera">
        <span class="consulta-etiqueta">ESCUELA LO DE FUENTES</span>

        <h1 id="titulo-consulta">Consulta tu inscripción</h1>

        <p>
            Revisa los datos de la inscripción y descarga tu comprobante.
        </p>
    </header>

    <div class="consulta-contenido">
        <form action="<?= e(url('inscripcion/consultar')) ?>"
              method="POST" class="consulta-formulario">
            <?= csrf_field() ?>

            <label for="codigo-consulta">Código de confirmación</label>

            <p id="ayuda-codigo" class="consulta-ayuda">
                Utiliza el código que recibiste al completar la inscripción.
                Es diferente al carné del estudiante.
            </p>

            <div class="consulta-buscador">
                <input
                    type="text"
                    id="codigo-consulta"
                    name="codigo"
                    required
                    maxlength="64"
                    placeholder="Ej. INS-2026-A1B2C3D4"
                    value="<?= e($codigo ?? '') ?>"
                    aria-describedby="ayuda-codigo"
                    autocomplete="off"
                    autocapitalize="characters"
                    spellcheck="false"
                >

                <button type="submit" class="consulta-boton">
                    <svg width="18" height="18" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round"
                         aria-hidden="true">
                        <circle cx="10.5" cy="10.5" r="6.5"/>
                        <path d="m16 16 5 5"/>
                    </svg>
                    Consultar
                </button>
            </div>
        </form>

        <?php if (!empty($error)): ?>
            <div class="consulta-error" role="alert">
                <strong>No se pudo consultar la inscripción.</strong>
                <p><?= e($error) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($resultado): ?>
            <section class="consulta-resultado"
                     aria-labelledby="titulo-resultado">
                <header class="consulta-resumen">
                    <div>
                        <h2 id="titulo-resultado">Datos de la inscripción</h2>
                        <span class="consulta-codigo-etiqueta">
                            Código de confirmación
                        </span>
                        <strong class="consulta-codigo">
                            <?= e($resultado['codigo_confirmacion']) ?>
                        </strong>
                    </div>

                    <span class="consulta-estado <?= $confirmada
                        ? 'consulta-estado-confirmada'
                        : 'consulta-estado-neutral' ?>">
                        <?= e($resultado['estado']) ?>
                    </span>
                </header>

                <dl class="consulta-datos">
                    <div class="consulta-estudiante">
                        <dt>Estudiante</dt>
                        <dd><?= e($resultado['estudiante_nombre']) ?></dd>
                    </div>

                    <?php if (!empty($resultado['codigo_estudiante'])): ?>
                        <div>
                            <dt>Carné del estudiante</dt>
                            <dd class="consulta-carne">
                                <?= e($resultado['codigo_estudiante']) ?>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <div>
                        <dt>Grado escolar</dt>
                        <dd><?= e($resultado['grado']) ?></dd>
                    </div>

                    <div>
                        <dt>Sección</dt>
                        <dd><?= e($resultado['seccion']) ?></dd>
                    </div>

                    <div>
                        <dt>Ciclo escolar</dt>
                        <dd><?= e($resultado['periodo']) ?></dd>
                    </div>

                    <div>
                        <dt>Padre, madre o encargado</dt>
                        <dd>
                            <?= e($resultado['encargado_nombre']) ?>
                            <span class="consulta-parentesco">
                                <?= e($resultado['parentesco']) ?>
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt>Fecha de registro</dt>
                        <dd><?= e($fechaVisible) ?></dd>
                    </div>
                </dl>

                <?php if ($confirmada): ?>
                    <div class="consulta-descarga">
                        <div>
                            <strong>Tu comprobante está disponible</strong>
                            <p>Descárgalo y conserva una copia.</p>
                        </div>

                        <a
                            href="<?= e(url(
                                'inscripcion/comprobante?codigo='
                                . urlencode((string) $resultado['codigo_confirmacion'])
                            )) ?>"
                            class="consulta-boton"
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round"
                                 stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5"/>
                            </svg>
                            Descargar PDF
                        </a>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <div class="consulta-pie">
            <a href="<?= e(url('inscripcion')) ?>">
                <span aria-hidden="true">←</span>
                Volver al formulario de inscripción
            </a>
        </div>
    </div>
</section>