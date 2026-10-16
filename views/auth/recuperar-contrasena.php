<section class="acceso-escolar" aria-labelledby="titulo-recuperacion">
    <div class="acceso-presentacion">
        <span class="acceso-etiqueta">ESCUELA LO DE FUENTES</span>

        <h2>Seguridad y acceso a tu cuenta escolar.</h2>

        <p>
            Recupera el acceso a tus herramientas de gestión y docencia
            mediante un enlace seguro de un solo uso.
        </p>

        <div class="acceso-servicios">
            <span>Enlace criptográfico</span>
            <span>Vigencia temporal</span>
            <span>Protección de cuenta</span>
        </div>

        <div class="acceso-jornada">
            Jornada vespertina · Mixco, Guatemala
        </div>
    </div>

    <div class="acceso-formulario">
        <img
            src="<?= e(url('img/logo-escuela.png')) ?>"
            alt="Escudo de la escuela"
            class="acceso-logo"
            width="80"
            height="92"
        >

        <h2 id="titulo-recuperacion">Recuperar contraseña</h2>
        <p class="acceso-ayuda">
            Ingresa tu correo institucional o nombre de usuario registrado.
        </p>

        <?php if (!empty($mensaje_exito)): ?>
            <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.85rem 1rem; border-radius: 6px; font-size: 0.9rem; margin-bottom: 1.25rem;" role="status">
                <strong>Solicitud procesada:</strong>
                <p style="margin: 0.35rem 0 0 0;"><?= e($mensaje_exito) ?></p>
            </div>

            <?php if (!empty($enlace_simulado)): ?>
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 0.85rem 1rem; border-radius: 6px; font-size: 0.85rem; margin-bottom: 1.25rem;">
                    <strong>Simulación de entorno local / académico:</strong>
                    <p style="margin: 0.25rem 0 0.5rem 0; color: #1e3a8a;">
                        En un servidor de producción se enviaría al buzón de correo. Para este entorno de evaluación, puedes continuar directamente en:
                    </p>
                    <a href="<?= e(url($enlace_simulado)) ?>" style="color: #2563eb; font-weight: 600; text-decoration: underline; word-break: break-all;">
                        <?= e(url($enlace_simulado)) ?>
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($mensaje_error)): ?>
            <div class="acceso-error" role="alert">
                <?= e($mensaje_error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= e(url('/recuperar-contrasena')) ?>" method="POST">
            <?= csrf_field() ?>

            <div class="acceso-campo">
                <label for="correo">Correo electrónico o usuario</label>
                <input
                    type="text"
                    id="correo"
                    name="correo"
                    value="<?= e($correo ?? '') ?>"
                    autocomplete="email"
                    placeholder="ej. docente@escuela.edu.gt"
                    required
                >
                <?php if (isset($errors['correo'])): ?>
                    <small style="color: #dc2626; font-size: 0.8rem; display: block; margin-top: 0.25rem;">
                        <?= e($errors['correo']) ?>
                    </small>
                <?php endif; ?>
            </div>

            <button type="submit" class="acceso-enviar">
                Enviar enlace de recuperación
                <span aria-hidden="true">→</span>
            </button>
        </form>

        <p class="acceso-inscripcion">
            ¿Recordaste tu contraseña?
            <a href="<?= e(url('/login')) ?>">Iniciar sesión</a>
        </p>
    </div>
</section>
