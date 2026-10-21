<div class="inicio-contenedor">
    <!-- Hero Principal de Bienvenida -->
    <section class="inicio-hero" aria-labelledby="titulo-bienvenida">
        <div class="inicio-hero-escudo">
            <img src="<?= e(url('img/logo-escuela.png')) ?>"
                 alt="Escudo oficial de la Escuela Oficial Rural Mixta Lo de Fuentes"
                 class="inicio-logo"
                 width="110"
                 height="126">
        </div>
        <div class="inicio-hero-texto">
            <span class="inicio-badge-jornada">Jornada vespertina · Mixco, Guatemala</span>
            <h1 id="titulo-bienvenida" class="inicio-hero-titulo">
                Escuela Oficial Rural Mixta Lo de Fuentes
            </h1>
            <p class="inicio-hero-descripcion">
                Plataforma institucional de gestión escolar. Un espacio seguro, accesible y confiable para organizar los procesos académicos, inscripciones y control administrativo de nuestra comunidad educativa.
            </p>
            <div class="inicio-hero-acciones">
                <a href="<?= e(url('inscripcion')) ?>" class="btn-inicio btn-inicio-primario">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                    <span>Inscripción en línea</span>
                </a>
                <a href="<?= e(url('inscripcion/consultar')) ?>" class="btn-inicio btn-inicio-secundario">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <span>Consultar inscripción</span>
                </a>
                <a href="<?= e(url('login')) ?>" class="btn-inicio btn-inicio-outline">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    <span>Iniciar sesión</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Accesos Rápidos -->
    <section class="inicio-seccion" aria-labelledby="titulo-accesos">
        <div class="inicio-seccion-encabezado">
            <h2 id="titulo-accesos" class="inicio-seccion-titulo">Accesos Rápidos</h2>
            <p class="inicio-seccion-subtitulo">Servicios disponibles para padres de familia, encargados, docentes y personal directivo.</p>
        </div>

        <div class="inicio-tarjetas-grid">
            <!-- Tarjeta 1: Inscripción en línea -->
            <article class="inicio-tarjeta">
                <div class="inicio-tarjeta-icono inicio-icono-azul" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </div>
                <div class="inicio-tarjeta-cuerpo">
                    <h3 class="inicio-tarjeta-titulo">Inscripción en línea</h3>
                    <p class="inicio-tarjeta-texto">
                        Registra una solicitud de inscripción para un estudiante.
                    </p>
                </div>
                <div class="inicio-tarjeta-pie">
                    <a href="<?= e(url('inscripcion')) ?>" class="inicio-enlace-tarjeta">
                        Comenzar inscripción <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </article>

            <!-- Tarjeta 2: Consultar inscripción -->
            <article class="inicio-tarjeta">
                <div class="inicio-tarjeta-icono inicio-icono-verde" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 11l3 3L22 4"></path>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                </div>
                <div class="inicio-tarjeta-cuerpo">
                    <h3 class="inicio-tarjeta-titulo">Consultar inscripción</h3>
                    <p class="inicio-tarjeta-texto">
                        Consulta el estado de una inscripción mediante su código.
                    </p>
                </div>
                <div class="inicio-tarjeta-pie">
                    <a href="<?= e(url('inscripcion/consultar')) ?>" class="inicio-enlace-tarjeta">
                        Consultar estado <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </article>

            <!-- Tarjeta 3: Acceso institucional -->
            <article class="inicio-tarjeta">
                <div class="inicio-tarjeta-icono inicio-icono-dorado" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <div class="inicio-tarjeta-cuerpo">
                    <h3 class="inicio-tarjeta-titulo">Acceso institucional</h3>
                    <p class="inicio-tarjeta-texto">
                        Ingreso para personal administrativo y docentes.
                    </p>
                </div>
                <div class="inicio-tarjeta-pie">
                    <a href="<?= e(url('login')) ?>" class="inicio-enlace-tarjeta">
                        Ingresar al sistema <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </article>
        </div>
    </section>

    <!-- Información Institucional -->
    <section class="inicio-seccion inicio-seccion-institucional" aria-labelledby="titulo-institucional">
        <div class="inicio-institucional-caja">
            <div class="inicio-institucional-encabezado">
                <h2 id="titulo-institucional" class="inicio-institucional-titulo">
                    Sistema Integral de Gestión Escolar
                </h2>
                <p class="inicio-institucional-descripcion">
                    El sistema web de la Escuela Oficial Rural Mixta Lo de Fuentes facilita la gestión de inscripciones, la organización académica y el control administrativo de la institución.
                </p>
            </div>

            <div class="inicio-modulos-grid">
                <div class="inicio-modulo-item">
                    <div class="inicio-modulo-punto" aria-hidden="true"></div>
                    <div>
                        <h3 class="inicio-modulo-titulo">Inscripciones y Cupos</h3>
                        <p class="inicio-modulo-texto">Registro transparente en línea con asignación y control automático de cupos por grado y sección.</p>
                    </div>
                </div>

                <div class="inicio-modulo-item">
                    <div class="inicio-modulo-punto" aria-hidden="true"></div>
                    <div>
                        <h3 class="inicio-modulo-titulo">Asignación de Secciones</h3>
                        <p class="inicio-modulo-texto">Organización de nóminas y grados conforme a la capacidad instalada y la jornada vespertina.</p>
                    </div>
                </div>

                <div class="inicio-modulo-item">
                    <div class="inicio-modulo-punto" aria-hidden="true"></div>
                    <div>
                        <h3 class="inicio-modulo-titulo">Control de Asistencia</h3>
                        <p class="inicio-modulo-texto">Registro confiable de entrada y salida docente validado por geolocalización dentro del radio escolar.</p>
                    </div>
                </div>

                <div class="inicio-modulo-item">
                    <div class="inicio-modulo-punto" aria-hidden="true"></div>
                    <div>
                        <h3 class="inicio-modulo-titulo">Gestión de Permisos</h3>
                        <p class="inicio-modulo-texto">Trámite digital de solicitudes de permiso y justificaciones con resolución administrativa.</p>
                    </div>
                </div>

                <div class="inicio-modulo-item">
                    <div class="inicio-modulo-punto" aria-hidden="true"></div>
                    <div>
                        <h3 class="inicio-modulo-titulo">Reportes Institucionales</h3>
                        <p class="inicio-modulo-texto">Consultas y resúmenes de asistencia, ocupación de cupos y emisión de comprobantes en PDF.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>