<section class="acceso-escolar" aria-labelledby="titulo-restablecer">
    <div class="acceso-presentacion">
        <span class="acceso-etiqueta">ESCUELA LO DE FUENTES</span>

        <h2>Establece tu nueva contraseña segura.</h2>

        <p>
            Crea una clave que cumpla con los estándares institucionales
            para proteger tu cuenta.
        </p>

        <div class="acceso-servicios">
            <span>Mínimo 8 caracteres</span>
            <span>Hashing BCrypt</span>
            <span>Acceso protegido</span>
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

        <h2 id="titulo-restablecer">Restablecer contraseña</h2>

        <?php if (!$token_valido || !empty($error_token)): ?>
            <div class="acceso-error" role="alert" style="margin-top: 1rem;">
                <strong>Enlace no disponible</strong>
                <p style="margin: 0.35rem 0 0 0;">
                    <?= e($error_token ?? 'El enlace de recuperación es inválido, ha expirado o ya fue utilizado.') ?>
                </p>
            </div>

            <p class="acceso-ayuda" style="margin-top: 1.5rem;">
                Por motivos de seguridad, los enlaces de recuperación expiran tras 60 minutos y solo pueden ser utilizados una única vez.
            </p>

            <a href="<?= e(url('/recuperar-contrasena')) ?>" class="acceso-enviar" style="text-align: center; text-decoration: none; display: block; margin-top: 1rem;">
                Solicitar nuevo enlace <span aria-hidden="true">→</span>
            </a>
        <?php else: ?>
            <p class="acceso-ayuda">
                Ingresa tu nueva clave de acceso de al menos 8 caracteres.
            </p>

            <?php if (!empty($mensaje_error)): ?>
                <div class="acceso-error" role="alert">
                    <?= e($mensaje_error) ?>
                </div>
            <?php endif; ?>

            <form action="<?= e(url('/restablecer-contrasena')) ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <div class="acceso-campo">
                    <label for="password">Nueva contraseña</label>
                    <div class="acceso-clave">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="new-password"
                            placeholder="Mínimo 8 caracteres"
                            required
                        >
                        <button
                            type="button"
                            id="alternar-clave-1"
                            aria-controls="password"
                            aria-pressed="false"
                            aria-label="Mostrar contraseña"
                        >
                            Mostrar
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <small style="color: #dc2626; font-size: 0.8rem; display: block; margin-top: 0.25rem;">
                            <?= e($errors['password']) ?>
                        </small>
                    <?php endif; ?>
                </div>

                <div class="acceso-campo">
                    <label for="password_confirmation">Confirmar nueva contraseña</label>
                    <div class="acceso-clave">
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            autocomplete="new-password"
                            placeholder="Repite la nueva contraseña"
                            required
                        >
                        <button
                            type="button"
                            id="alternar-clave-2"
                            aria-controls="password_confirmation"
                            aria-pressed="false"
                            aria-label="Mostrar confirmación"
                        >
                            Mostrar
                        </button>
                    </div>
                    <?php if (isset($errors['password_confirmation'])): ?>
                        <small style="color: #dc2626; font-size: 0.8rem; display: block; margin-top: 0.25rem;">
                            <?= e($errors['password_confirmation']) ?>
                        </small>
                    <?php endif; ?>
                </div>

                <button type="submit" class="acceso-enviar">
                    Guardar nueva contraseña
                    <span aria-hidden="true">→</span>
                </button>
            </form>
        <?php endif; ?>

        <p class="acceso-inscripcion">
            ¿Deseas volver al inicio de sesión?
            <a href="<?= e(url('/login')) ?>">Iniciar sesión</a>
        </p>
    </div>
</section>

<script>
(() => {
    const configBtn = (btnId, inputId) => {
        const btn = document.getElementById(btnId);
        const input = document.getElementById(inputId);
        if (!btn || !input) return;
        btn.addEventListener('click', () => {
            const esPassword = input.type === 'password';
            input.type = esPassword ? 'text' : 'password';
            btn.textContent = esPassword ? 'Ocultar' : 'Mostrar';
            btn.setAttribute('aria-pressed', esPassword ? 'true' : 'false');
        });
    };
    configBtn('alternar-clave-1', 'password');
    configBtn('alternar-clave-2', 'password_confirmation');
})();
</script>
