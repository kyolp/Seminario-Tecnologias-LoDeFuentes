<section class="acceso-escolar" aria-labelledby="titulo-acceso">
    <div class="acceso-presentacion">
        <span class="acceso-etiqueta">ESCUELA LO DE FUENTES</span>

        <h2>Un espacio para organizar y acompañar la educación.</h2>

        <p>
            Accede a las herramientas de gestión escolar
            desde un mismo lugar.
        </p>

        <div class="acceso-servicios">
            <span>Gestión escolar</span>
            <span>Asistencia docente</span>
            <span>Solicitudes y avisos</span>
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

        <h2 id="titulo-acceso">Bienvenido de nuevo</h2>
        <p class="acceso-ayuda">
            Ingresa con tu cuenta de docente o administración.
        </p>

        <?php if (!empty($error)): ?>
            <div class="acceso-error" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= e(url('/login')) ?>" method="POST">
            <?= csrf_field() ?>

            <div class="acceso-campo">
                <label for="nombre_usuario">Usuario</label>
                <input
                    type="text"
                    id="nombre_usuario"
                    name="nombre_usuario"
                    value="<?= e($old_username ?? '') ?>"
                    autocomplete="username"
                    placeholder="Escribe tu usuario"
                    required
                >
            </div>

            <div class="acceso-campo">
                <label for="password">Contraseña</label>

                <div class="acceso-clave">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="Escribe tu contraseña"
                        required
                    >
                    <button
                        type="button"
                        id="alternar-clave"
                        aria-controls="password"
                        aria-pressed="false"
                        aria-label="Mostrar contraseña"
                    >
                        Mostrar
                    </button>
                </div>
            </div>

            <button type="submit" class="acceso-enviar">
                Iniciar sesión
                <span aria-hidden="true">→</span>
            </button>
        </form>

        <p class="acceso-inscripcion">
            <a href="<?= e(url('/recuperar-contrasena')) ?>">¿Olvidaste tu contraseña?</a>
        </p>

        <p class="acceso-inscripcion">
            ¿Deseas inscribir a un estudiante?
            <a href="<?= e(url('inscripcion')) ?>">Ir a inscripciones</a>
        </p>
    </div>
</section>

<script>
(() => {
    const boton = document.getElementById('alternar-clave');
    const campo = document.getElementById('password');

    boton.addEventListener('click', () => {
        const mostrar = campo.type === 'password';

        campo.type = mostrar ? 'text' : 'password';
        boton.textContent = mostrar ? 'Ocultar' : 'Mostrar';
        boton.setAttribute('aria-pressed', String(mostrar));
        boton.setAttribute(
            'aria-label',
            mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña'
        );
    });
})();
</script>