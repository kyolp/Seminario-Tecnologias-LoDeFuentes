<section class="dashboard-panel" style="max-width: 1000px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.5rem;">Panel de Administración</h2>
            <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">Fase 4 — Administración Básica e Información Institucional</p>
        </div>
        <div>
            <form action="<?= e(url('/logout')) ?>" method="POST" style="margin: 0;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary" style="padding: 0.5rem 1rem; background: #dc2626; color: #fff; border: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600; cursor: pointer;">
                    Cerrar Sesión
                </button>
            </form>
        </div>
    </div>

    <?php if (!empty($flash_success)): ?>
        <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <?= e($flash_success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($flash_error)): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <?= e($flash_error) ?>
        </div>
    <?php endif; ?>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1.25rem; margin-bottom: 2rem;">
        <h3 style="margin-top: 0; color: #0f172a; font-size: 1.1rem;">Datos de la Sesión Autorizada</h3>
        <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; color: #334155; font-size: 0.95rem;">
            <li><strong>Usuario:</strong> <?= e($user?->nombre_usuario) ?></li>
            <li><strong>Nombre completo:</strong> <?= e($user?->getFullName()) ?></li>
            <li><strong>Correo:</strong> <?= e($user?->correo) ?></li>
            <li><strong>Rol verificado:</strong> <span style="background: #dbeafe; color: #1e40af; padding: 0.15rem 0.5rem; border-radius: 4px; font-weight: 600;"><?= e($user?->rol_nombre) ?></span></li>
            <li><strong>Estado de cuenta:</strong> <span style="background: #dcfce7; color: #166534; padding: 0.15rem 0.5rem; border-radius: 4px; font-weight: 600;"><?= e($user?->estado) ?></span></li>
        </ul>
    </div>

    <h3 style="color: #1e3a8a; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem; margin-bottom: 1rem;">Módulos de Gestión Institucional</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
        <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h4 style="margin: 0 0 0.5rem 0; color: #1e3a8a; font-size: 1.15rem;">👨‍🏫 Docentes</h4>
            <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1rem;">Registro de docentes, credenciales asociadas, estado y código institucional.</p>
            <a href="<?= e(url('/administracion/docentes')) ?>" style="display: inline-block; padding: 0.4rem 0.8rem; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">Gestionar Docentes &rarr;</a>
        </div>

        <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h4 style="margin: 0 0 0.5rem 0; color: #1e3a8a; font-size: 1.15rem;">🎒 Estudiantes</h4>
            <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1rem;">Registro básico de estudiantes, código, datos personales y estado.</p>
            <a href="<?= e(url('/administracion/estudiantes')) ?>" style="display: inline-block; padding: 0.4rem 0.8rem; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">Gestionar Estudiantes &rarr;</a>
        </div>

        <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h4 style="margin: 0 0 0.5rem 0; color: #1e3a8a; font-size: 1.15rem;">📅 Períodos Escolares</h4>
            <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1rem;">Administración de ciclos escolares y control del período activo único.</p>
            <a href="<?= e(url('/administracion/periodos')) ?>" style="display: inline-block; padding: 0.4rem 0.8rem; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">Gestionar Períodos &rarr;</a>
        </div>

        <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h4 style="margin: 0 0 0.5rem 0; color: #1e3a8a; font-size: 1.15rem;">📚 Grados</h4>
            <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1rem;">Catálogo base de grados académicos y orden jerárquico.</p>
            <a href="<?= e(url('/administracion/grados')) ?>" style="display: inline-block; padding: 0.4rem 0.8rem; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">Gestionar Grados &rarr;</a>
        </div>

        <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h4 style="margin: 0 0 0.5rem 0; color: #1e3a8a; font-size: 1.15rem;">🔗 Grados por Período</h4>
            <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1rem;">Asociación y habilitación de grados disponibles para cada ciclo escolar.</p>
            <a href="<?= e(url('/administracion/grados-periodos')) ?>" style="display: inline-block; padding: 0.4rem 0.8rem; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">Configurar Grados/Ciclo &rarr;</a>
        </div>

        <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h4 style="margin: 0 0 0.5rem 0; color: #1e3a8a; font-size: 1.15rem;">🏢 Secciones y Capacidad</h4>
            <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1rem;">Administración de secciones vinculadas y asignación de capacidades estrictas.</p>
            <a href="<?= e(url('/administracion/secciones')) ?>" style="display: inline-block; padding: 0.4rem 0.8rem; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">Gestionar Secciones &rarr;</a>
        </div>

        <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h4 style="margin: 0 0 0.5rem 0; color: #1e3a8a; font-size: 1.15rem;">🚪 Aulas</h4>
            <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1rem;">Catálogo de aulas físicas del centro educativo y ubicación de referencia.</p>
            <a href="<?= e(url('/administracion/aulas')) ?>" style="display: inline-block; padding: 0.4rem 0.8rem; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">Gestionar Aulas &rarr;</a>
        </div>

        <div style="background: #ffffff; border: 2px solid #3b82f6; border-radius: 8px; padding: 1.25rem; box-shadow: 0 2px 4px rgba(59,130,246,0.1);">
            <h4 style="margin: 0 0 0.5rem 0; color: #1e3a8a; font-size: 1.15rem;">📋 Asignaciones Docentes</h4>
            <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1rem;">Asignación de docentes a grados, secciones y aulas por ciclo escolar (Fase 5 — RF-16).</p>
            <a href="<?= e(url('/administracion/asignaciones')) ?>" style="display: inline-block; padding: 0.4rem 0.8rem; background: #16a34a; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">Gestionar Asignaciones &rarr;</a>
        </div>

        <div style="background: #ffffff; border: 2px solid #10b981; border-radius: 8px; padding: 1.25rem; box-shadow: 0 2px 4px rgba(16,185,129,0.1);">
            <h4 style="margin: 0 0 0.5rem 0; color: #1e3a8a; font-size: 1.15rem;">📍 Ubicación y Geocerca</h4>
            <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1rem;">Configuración de coordenadas institucionales y radio para asistencia (Fase 8 — RF-18, RF-19).</p>
            <a href="<?= e(url('/administracion/ubicacion')) ?>" style="display: inline-block; padding: 0.4rem 0.8rem; background: #10b981; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">Configurar Geocerca &rarr;</a>
        </div>

        <div style="background: #ffffff; border: 2px solid #8b5cf6; border-radius: 8px; padding: 1.25rem; box-shadow: 0 2px 4px rgba(139,92,246,0.1);">
            <h4 style="margin: 0 0 0.5rem 0; color: #1e3a8a; font-size: 1.15rem;">📑 Permisos Docentes</h4>
            <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1rem;">Recepción, revisión y resolución de permisos laborales docentes (Fase 9 — RF-27, RNF-06).</p>
            <a href="<?= e(url('/administracion/permisos')) ?>" style="display: inline-block; padding: 0.4rem 0.8rem; background: #7c3aed; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">Gestionar Permisos &rarr;</a>
        </div>

        <div style="background: #ffffff; border: 2px solid #0284c7; border-radius: 8px; padding: 1.25rem; box-shadow: 0 2px 4px rgba(2,132,199,0.1);">
            <h4 style="margin: 0 0 0.5rem 0; color: #1e3a8a; font-size: 1.15rem;">🔔 Notificaciones</h4>
            <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1rem;">Avisos institucionales sobre nuevas solicitudes de permisos y eventos administrativos (Fase 10 — RF-26).</p>
            <a href="<?= e(url('/administracion/notificaciones')) ?>" style="display: inline-block; padding: 0.4rem 0.8rem; background: #0284c7; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">Ver Notificaciones &rarr;</a>
        </div>
    </div>
</section>
