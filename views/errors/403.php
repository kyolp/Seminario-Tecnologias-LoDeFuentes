<section class="error-panel" style="max-width: 600px; margin: 3rem auto; padding: 2rem; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
    <div style="font-size: 3rem; color: #dc2626; margin-bottom: 0.5rem; font-weight: 700;">403</div>
    <h2 style="color: #1f2937; margin-bottom: 1rem; font-size: 1.5rem;">Acceso Denegado</h2>
    <p style="color: #4b5563; font-size: 1rem; line-height: 1.6; margin-bottom: 1.5rem;">
        <?= e($message ?? 'No cuenta con los privilegios o rol institucional necesario para acceder a este recurso.') ?>
    </p>

    <div style="display: flex; justify-content: center; gap: 1rem;">
        <a href="<?= e(url('/')) ?>" style="display: inline-block; padding: 0.6rem 1.25rem; background: #1e3a8a; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 600;">
            Ir al Inicio
        </a>
        <?php if (auth()->check()): ?>
            <form action="<?= e(url('/logout')) ?>" method="POST" style="margin: 0; display: inline;">
                <?= csrf_field() ?>
                <button type="submit" style="padding: 0.6rem 1.25rem; background: #6b7280; color: #fff; border: none; border-radius: 4px; font-weight: 600; cursor: pointer;">
                    Cerrar Sesión
                </button>
            </form>
        <?php else: ?>
            <a href="<?= e(url('/login')) ?>" style="display: inline-block; padding: 0.6rem 1.25rem; background: #059669; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 600;">
                Iniciar Sesión
            </a>
        <?php endif; ?>
    </div>
</section>
