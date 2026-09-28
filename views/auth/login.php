<section class="login-card" style="max-width: 440px; margin: 2.5rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <h2 style="margin: 0; color: #1e3a8a; font-size: 1.5rem;">Control de Acceso</h2>
        <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">Escuela Oficial Rural Mixta Lo de Fuentes</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="background-color: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; padding: 0.75rem 1rem; margin-bottom: 1.25rem; border-radius: 4px; font-size: 0.9rem;" role="alert">
            <strong>Atención:</strong> <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form action="<?= e(url('/login')) ?>" method="POST" autocomplete="off">
        <?= csrf_field() ?>

        <div style="margin-bottom: 1.25rem;">
            <label for="nombre_usuario" style="display: block; font-weight: 600; margin-bottom: 0.35rem; color: #374151; font-size: 0.9rem;">Nombre de Usuario</label>
            <input type="text"
                   id="nombre_usuario"
                   name="nombre_usuario"
                   value="<?= e($old_username ?? '') ?>"
                   required
                   autofocus
                   style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.95rem; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label for="password" style="display: block; font-weight: 600; margin-bottom: 0.35rem; color: #374151; font-size: 0.9rem;">Contraseña</label>
            <input type="password"
                   id="password"
                   name="password"
                   required
                   style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.95rem; box-sizing: border-box;">
        </div>

        <div>
            <button type="submit"
                    class="btn btn-primary"
                    style="width: 100%; padding: 0.75rem; background-color: #1e3a8a; color: #ffffff; border: none; border-radius: 4px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                Ingresar al Sistema
            </button>
        </div>
    </form>

    <div style="margin-top: 1.5rem; text-align: center; border-top: 1px solid #f3f4f6; padding-top: 1rem;">
        <a href="<?= e(url('/')) ?>" style="color: #4b5563; font-size: 0.875rem; text-decoration: none;">&larr; Volver a la página principal</a>
    </div>
</section>
