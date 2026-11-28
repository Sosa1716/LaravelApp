<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Restaurante - Cocina & Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            yellow: '#EBD140',
                            red: '#C24840',
                            blue: '#2879A8',
                            dark: '#2E3C43',
                            darker: '#222d33',
                            light: '#fdfbf2',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        :root {
            --brand-yellow: #EBD140;
            --brand-red: #C24840;
            --brand-blue: #2879A8;
            --brand-dark: #2E3C43;
            --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
            --ease-ui: cubic-bezier(0.16, 1, 0.3, 1);
            --ease-spring: cubic-bezier(0.2, 0, 0, 1);
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition-duration: 0.01ms !important; animation-duration: 0.01ms !important; }
        }

        /* ── Touch & Pointer Ergonomics (Apple Design §1, §10) ── */
        * { -webkit-tap-highlight-color: transparent; }
        button, a, input, select { touch-action: manipulation; }

        /* ── Optical Typography Tracking (Apple Design §15) ── */
        h1, h2, h3, .heading-apple { letter-spacing: -0.024em; }

        /* ── Apple Translucent Materials (Apple Design §12) con paleta #2E3C43 ── */
        .apple-glass-nav {
            background: rgba(46, 60, 67, 0.90);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(235, 209, 64, 0.18);
        }
        .apple-glass-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
        }

        /* ── Tabs de administración con acento #EBD140 ── */
        .admin-tab-btn {
            border-bottom: 2px solid transparent;
            transition: color 150ms var(--ease-ui), border-bottom-color 150ms var(--ease-ui);
        }
        .admin-tab-btn.tab-active {
            color: #EBD140 !important;
            border-bottom-color: #EBD140 !important;
        }

        /* ── Cards de cocina con acento lateral de marca ── */
        .card-pendiente {
            border-left: 4px solid #EBD140;
            background: linear-gradient(to right, rgba(235, 209, 64, 0.16) 0%, transparent 110px);
        }
        .card-preparando {
            border-left: 4px solid #2879A8;
            background: linear-gradient(to right, rgba(40, 121, 168, 0.14) 0%, transparent 110px);
        }
        .card-listo {
            border-left: 4px solid #10b981;
            background: linear-gradient(to right, rgba(16, 185, 129, 0.14) 0%, transparent 110px);
        }

        /* ── Tarjetas métricas estilo widget ── */
        .metric-card {
            transition: box-shadow 200ms var(--ease-ui), transform 150ms var(--ease-ui);
        }
        .metric-card:hover {
            box-shadow: 0 10px 25px -5px rgba(46, 60, 67, 0.12);
            transform: translateY(-2px);
        }

        /* ── Botones con feedback táctil inmediato (§1 Kill Latency) ── */
        .btn-action {
            transition: background-color 150ms var(--ease-ui), transform 120ms var(--ease-spring), box-shadow 150ms var(--ease-ui);
        }
        .btn-action:active { transform: scale(0.965); }

        /* ── Filas de tabla con hover suave ── */
        .tr-hover { transition: background-color 120ms var(--ease-ui); }
        .tr-hover:hover { background-color: rgb(248 250 252); }

        /* ── Filtros de cocina estilo segmented control ── */
        .filter-btn {
            transition: background-color 150ms var(--ease-ui), color 150ms var(--ease-ui), transform 120ms var(--ease-spring), box-shadow 150ms var(--ease-ui);
        }
        .filter-btn:active { transform: scale(0.965); }

        /* ── Foco visible Apple-style ── */
        :focus-visible { outline: 2px solid #2879A8; outline-offset: 2px; border-radius: 8px; }

        /* ── Separador de header admin ── */
        .tab-bar-separator { border-top: 1px solid rgba(255, 255, 255, 0.08); }
    </style>
</head>
<body class="bg-slate-100 text-[#2E3C43] min-h-screen antialiased selection:bg-[#EBD140]/40 selection:text-[#2E3C43]">

    <!-- ================= VISTA DE LOGIN ================= -->
    <div id="view-login" class="min-h-screen flex items-center justify-center p-4 sm:p-6 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-[#EBD140]/30 via-slate-100 to-[#2E3C43]/10 relative overflow-hidden">
        <!-- Luces de fondo decorativas sutiles -->
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-[#EBD140]/30 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-[#2879A8]/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="apple-glass-card max-w-[430px] w-full rounded-[32px] p-8 sm:p-10 border border-white/80 shadow-[0_25px_60px_-15px_rgba(46,60,67,0.18),0_0_0_1px_rgba(46,60,67,0.06)] relative z-10 transition-all duration-300">
            <div class="text-center mb-7">
                <!-- LOGO OFICIAL ANTOJERÍA PINGÜIN -->
                <div class="relative inline-block mb-3.5">
                    <img src="/logo.png" alt="Antojería Pingüin Logo"
                         class="w-28 h-28 sm:w-32 sm:h-32 object-cover rounded-full shadow-[0_12px_28px_-6px_rgba(46,60,67,0.35),0_0_0_4px_#EBD140] ring-4 ring-white mx-auto transform transition duration-300 hover:scale-105"
                         onerror="this.src='/logo.jpg';">
                </div>
                <h1 class="text-2xl font-black text-[#2E3C43] tracking-tight leading-tight">Antojería Pingüin</h1>
                <p class="text-xs font-semibold text-[#2879A8] uppercase tracking-wider mt-1">Buen Provecho · Cocina & Admin</p>
            </div>

            <form id="form-login" class="space-y-4" onsubmit="handleLogin(event)">
                <div id="login-error" class="hidden p-3 bg-red-50 text-[#C24840] text-xs font-bold rounded-xl border border-red-200"></div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[#2E3C43]/80 mb-1.5">Correo Electrónico</label>
                    <input type="email" id="login-email" required placeholder="cocina@restaurante.com"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-50/90 border border-slate-200 focus:bg-white focus:border-[#2879A8] focus:ring-4 focus:ring-[#2879A8]/15 outline-none transition text-sm text-[#2E3C43] font-medium">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[#2E3C43]/80 mb-1.5">Contraseña</label>
                    <input type="password" id="login-password" required placeholder="••••••••"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-50/90 border border-slate-200 focus:bg-white focus:border-[#2879A8] focus:ring-4 focus:ring-[#2879A8]/15 outline-none transition text-sm text-[#2E3C43] font-medium">
                </div>

                <div class="pt-2">
                    <button type="submit" id="btn-login-submit"
                        class="btn-action w-full py-3.5 px-4 bg-[#2E3C43] hover:bg-[#222d33] active:scale-[0.97] text-white font-bold rounded-2xl shadow-[0_6px_20px_rgba(46,60,67,0.25)] flex items-center justify-center gap-2 text-sm border-t border-white/15">
                        <span>Ingresar al Sistema</span>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-100 flex items-center justify-center gap-2 text-[11px] text-slate-400 font-medium">
                <span class="w-2 h-2 rounded-full bg-[#EBD140]"></span>
                <span>Acceso seguro para personal autorizado</span>
            </div>
        </div>
    </div>


    <!-- ================= VISTA DE COCINA ================= -->
    <div id="view-cocina" class="hidden min-h-screen flex flex-col bg-slate-100">
        <!-- Barra Superior Cocina (Apple Glass Nav) -->
        <header class="apple-glass-nav text-white sticky top-0 z-30 shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <img src="/logo.png" alt="Antojería Pingüin"
                         class="w-10 h-10 rounded-full object-cover shadow-md ring-2 ring-[#EBD140] shrink-0"
                         onerror="this.src='/logo.jpg';">
                    <div>
                        <h1 class="text-base font-extrabold leading-tight text-white tracking-tight flex items-center gap-2">
                            Módulo de Cocina
                            <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-[#EBD140] text-[#2E3C43] shadow-sm uppercase tracking-wider">En vivo</span>
                        </h1>
                        <p class="text-[11px] text-slate-300 leading-none mt-0.5 font-medium">Comandas en tiempo real · <span id="cocina-sync-text" class="text-[#EBD140] font-semibold">Sincronizado</span></p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="hidden sm:flex items-center gap-1.5 text-[11px] text-slate-300 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        API Web
                    </span>

                    <button onclick="fetchOrdenes()" title="Actualizar órdenes"
                        class="btn-action px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-xl border border-white/15 flex items-center gap-1.5 backdrop-blur-sm">
                        <span id="sync-icon">🔄</span> Actualizar
                    </button>

                    <div class="h-5 w-px bg-white/20 hidden sm:block"></div>

                    <button id="btn-return-admin" onclick="showView('admin')"
                        class="hidden btn-action px-3 py-1.5 bg-[#2879A8] hover:bg-[#20638a] text-white text-xs font-bold rounded-xl shadow-sm">
                        ⚙️ Admin
                    </button>

                    <span class="text-xs text-white/90 font-medium px-2.5 py-1 bg-white/10 rounded-xl border border-white/10" id="user-display-cocina">
                        Cocina
                    </span>

                    <button onclick="handleLogout()"
                        class="btn-action px-3 py-1.5 bg-[#C24840] hover:bg-[#a83c35] text-white text-xs font-bold rounded-xl shadow-sm">
                        Salir
                    </button>
                </div>
            </div>
        </header>

        <!-- Contenido Cocina -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 py-5 flex-1 w-full">

            <!-- Aviso de modo cocina compacto con colores de marca -->
            <div class="mb-5 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#EBD140]/20 border border-[#EBD140]/40 rounded-full text-[11px] font-bold text-[#2E3C43]">
                    🍳 Modo Cocina — solo preparación
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-200/80 border border-slate-300/80 rounded-full text-[11px] font-semibold text-slate-600">
                    🔒 Administración restringida
                </span>
            </div>

            <!-- Filtros Cocina estilo Segmented Control -->
            <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-1" id="cocina-filters">
                <button onclick="setFilter('todas')" data-filter="todas"
                    class="filter-btn active-filter px-4 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 bg-[#2E3C43] text-white shadow-sm whitespace-nowrap">
                    <span>📋 Todas</span>
                    <span id="badge-count-todas" class="px-1.5 py-0.5 rounded-full text-[10px] bg-white/20 font-bold">0</span>
                </button>
                <button onclick="setFilter('pendiente')" data-filter="pendiente"
                    class="filter-btn px-4 py-2.5 rounded-xl text-xs font-semibold flex items-center gap-2 bg-white text-[#2E3C43] hover:bg-slate-50 border border-slate-200 whitespace-nowrap">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#EBD140] shrink-0 shadow-sm"></span>
                    <span>Pendientes</span>
                    <span id="badge-count-pendiente" class="px-1.5 py-0.5 rounded-full text-[10px] bg-[#EBD140]/30 text-[#685305] font-bold">0</span>
                </button>
                <button onclick="setFilter('preparando')" data-filter="preparando"
                    class="filter-btn px-4 py-2.5 rounded-xl text-xs font-semibold flex items-center gap-2 bg-white text-[#2E3C43] hover:bg-slate-50 border border-slate-200 whitespace-nowrap">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#2879A8] shrink-0 shadow-sm"></span>
                    <span>En Preparación</span>
                    <span id="badge-count-preparando" class="px-1.5 py-0.5 rounded-full text-[10px] bg-[#2879A8]/20 text-[#2879A8] font-bold">0</span>
                </button>
                <button onclick="setFilter('listo')" data-filter="listo"
                    class="filter-btn px-4 py-2.5 rounded-xl text-xs font-semibold flex items-center gap-2 bg-white text-[#2E3C43] hover:bg-slate-50 border border-slate-200 whitespace-nowrap">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0 shadow-sm"></span>
                    <span>Listas ✓</span>
                    <span id="badge-count-listo" class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 font-bold">0</span>
                </button>
                <button onclick="setFilter('entregado')" data-filter="entregado"
                    class="filter-btn px-4 py-2.5 rounded-xl text-xs font-semibold flex items-center gap-2 bg-white text-[#2E3C43] hover:bg-slate-50 border border-slate-200 whitespace-nowrap">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-500 shrink-0 shadow-sm"></span>
                    <span>Entregadas</span>
                    <span id="badge-count-entregado" class="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-200 text-slate-700 font-bold">0</span>
                </button>
            </div>

            <!-- Grilla de Órdenes Cocina -->
            <div id="ordenes-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"></div>

            <div id="ordenes-empty" class="hidden text-center py-20 bg-white rounded-2xl border border-dashed border-slate-200 p-8">
                <span class="text-5xl block mb-3">✨</span>
                <h3 class="text-lg font-bold text-[#2E3C43]">¡Todo al día en cocina!</h3>
                <p class="text-sm text-slate-500 mt-1 max-w-xs mx-auto">No hay comandas activas en este momento.</p>
            </div>
        </main>
    </div>


    <!-- ================= VISTA DE ADMINISTRACIÓN ================= -->
    <div id="view-admin" class="hidden min-h-screen flex flex-col bg-slate-100">
        <!-- Barra Superior Admin (Apple Glass Nav) -->
        <header class="apple-glass-nav text-white sticky top-0 z-30 shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <img src="/logo.png" alt="Antojería Pingüin"
                         class="w-10 h-10 rounded-full object-cover shadow-md ring-2 ring-[#2879A8] shrink-0"
                         onerror="this.src='/logo.jpg';">
                    <div>
                        <h1 class="text-base font-extrabold leading-tight text-white tracking-tight flex items-center gap-2">
                            Panel de Administración
                            <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-[#2879A8] text-white shadow-sm uppercase tracking-wider">Admin</span>
                        </h1>
                        <p class="text-[11px] text-slate-300 leading-none mt-0.5 font-medium">Antojería Pingüin · Menú · Mesas · Finanzas</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button onclick="showView('cocina'); fetchOrdenes();"
                        class="btn-action px-3 py-1.5 bg-[#EBD140] hover:bg-[#dfc635] text-[#2E3C43] text-xs font-bold rounded-xl shadow-sm flex items-center gap-1.5">
                        🍳 Cocina
                    </button>
                    <button onclick="loadAdminData()"
                        class="btn-action px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-xl border border-white/15 backdrop-blur-sm">
                        🔄 Recargar
                    </button>
                    <span class="text-xs text-white/90 font-medium px-2.5 py-1 bg-white/10 rounded-xl border border-white/10" id="user-display-admin">
                        Administrador
                    </span>
                    <button onclick="handleLogout()"
                        class="btn-action px-3 py-1.5 bg-[#C24840] hover:bg-[#a83c35] text-white text-xs font-bold rounded-xl shadow-sm">
                        Salir
                    </button>
                </div>
            </div>

            <!-- Menú de Pestañas de Administración -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 flex overflow-x-auto tab-bar-separator gap-0 text-xs font-semibold">
                <button onclick="switchAdminTab('resumen')" id="tab-btn-resumen"
                    class="admin-tab-btn px-4 py-2.5 text-slate-400 hover:text-white transition-colors duration-150 whitespace-nowrap">
                    📊 Resumen
                </button>
                <button onclick="switchAdminTab('platillos')" id="tab-btn-platillos"
                    class="admin-tab-btn px-4 py-2.5 text-slate-400 hover:text-white transition-colors duration-150 whitespace-nowrap">
                    🍲 Platillos
                </button>
                <button onclick="switchAdminTab('categorias')" id="tab-btn-categorias"
                    class="admin-tab-btn px-4 py-2.5 text-slate-400 hover:text-white transition-colors duration-150 whitespace-nowrap">
                    📑 Categorías
                </button>
                <button onclick="switchAdminTab('mesas')" id="tab-btn-mesas"
                    class="admin-tab-btn px-4 py-2.5 text-slate-400 hover:text-white transition-colors duration-150 whitespace-nowrap">
                    🪑 Mesas
                </button>
                <button onclick="switchAdminTab('ordenes')" id="tab-btn-ordenes"
                    class="admin-tab-btn px-4 py-2.5 text-slate-400 hover:text-white transition-colors duration-150 whitespace-nowrap">
                    📋 Órdenes
                </button>
                <button onclick="switchAdminTab('pagos')" id="tab-btn-pagos"
                    class="admin-tab-btn px-4 py-2.5 text-slate-400 hover:text-white transition-colors duration-150 whitespace-nowrap">
                    💳 Pagos
                </button>
                <button onclick="switchAdminTab('usuarios')" id="tab-btn-usuarios"
                    class="admin-tab-btn px-4 py-2.5 text-slate-400 hover:text-white transition-colors duration-150 whitespace-nowrap flex items-center gap-1.5">
                    👥 Usuarios & Meseros
                </button>
                <button onclick="switchAdminTab('reportes')" id="tab-btn-reportes"
                    class="admin-tab-btn px-4 py-2.5 text-slate-400 hover:text-white transition-colors duration-150 whitespace-nowrap flex items-center gap-1.5">
                    📈 Reportes
                    <span class="text-[9px] bg-[#EBD140]/25 text-[#EBD140] px-1.5 py-0.5 rounded border border-[#EBD140]/40 font-bold">Pronto</span>
                </button>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 flex-1 w-full">

            <!-- 1. TAB: RESUMEN GENERAL -->
            <div id="tab-content-resumen" class="admin-tab-content space-y-6">
                <!-- Tarjetas Métricas Clave con los 4 colores de la paleta -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- 1. Total Recaudado / Ventas: #EBD140 (Dorado) -->
                    <div class="metric-card col-span-2 lg:col-span-1 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm border-t-4 border-t-[#EBD140]">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Recaudado</span>
                            <span class="p-2 rounded-xl bg-[#EBD140]/20 text-[#685305] text-base font-bold">💰</span>
                        </div>
                        <div class="text-3xl font-black text-[#2E3C43] leading-none" id="metric-ventas">$0.00</div>
                        <p class="text-xs text-slate-500 mt-2" id="metric-pagos-count">0 transacciones</p>
                    </div>

                    <!-- 2. Órdenes: #2879A8 (Azul Océano) -->
                    <div class="metric-card bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm border-t-4 border-t-[#2879A8]">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Órdenes</span>
                            <span class="p-2 rounded-xl bg-[#2879A8]/15 text-[#2879A8] text-base">📋</span>
                        </div>
                        <div class="text-2xl font-black text-[#2E3C43] leading-none" id="metric-ordenes">0</div>
                        <p class="text-xs text-slate-500 mt-2" id="metric-ordenes-sub">0 activas / 0 cerradas</p>
                    </div>

                    <!-- 3. Mesas: #2E3C43 (Pizarra Oscuro) -->
                    <div class="metric-card bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm border-t-4 border-t-[#2E3C43]">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Mesas</span>
                            <span class="p-2 rounded-xl bg-[#2E3C43]/10 text-[#2E3C43] text-base">🪑</span>
                        </div>
                        <div class="text-2xl font-black text-[#2E3C43] leading-none" id="metric-mesas">0</div>
                        <p class="text-xs text-slate-500 mt-2" id="metric-mesas-sub">0 disponibles</p>
                    </div>

                    <!-- 4. Platillos: #C24840 (Rojo Coral) -->
                    <div class="metric-card bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm border-t-4 border-t-[#C24840]">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Platillos</span>
                            <span class="p-2 rounded-xl bg-[#C24840]/15 text-[#C24840] text-base">🍲</span>
                        </div>
                        <div class="text-2xl font-black text-[#2E3C43] leading-none" id="metric-platillos">0</div>
                        <p class="text-xs text-slate-500 mt-2" id="metric-categorias">en 0 categorías</p>
                    </div>
                </div>

                <!-- Grilla rápida de actividad -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <!-- Últimas Órdenes -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                <span>📋</span> Últimas Órdenes
                            </h3>
                            <button onclick="switchAdminTab('ordenes')" class="text-xs text-blue-600 hover:text-blue-700 font-semibold transition-colors">Ver todas →</button>
                        </div>
                        <div id="resumen-ultimas-ordenes" class="space-y-2"></div>
                    </div>

                    <!-- Últimos Pagos -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                <span>💳</span> Últimos Pagos
                            </h3>
                            <button onclick="switchAdminTab('pagos')" class="text-xs text-blue-600 hover:text-blue-700 font-semibold transition-colors">Ver todos →</button>
                        </div>
                        <div id="resumen-ultimos-pagos" class="space-y-2"></div>
                    </div>
                </div>
            </div>

            <!-- 2. TAB: PLATILLOS -->
            <div id="tab-content-platillos" class="admin-tab-content hidden space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Catálogo de Platillos</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Administra nombres, precios, fotos, categorías y disponibilidad en cocina</p>
                    </div>
                    <button onclick="openModalPlatillo()" class="btn-action px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl shadow flex items-center gap-1.5">
                        <span>+</span> Nuevo Platillo
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200 text-[11px]">
                                <tr>
                                    <th class="p-3.5">Platillo</th>
                                    <th class="p-3.5">Categoría</th>
                                    <th class="p-3.5">Precio</th>
                                    <th class="p-3.5">Estado</th>
                                    <th class="p-3.5 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-platillos" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 3. TAB: CATEGORÍAS -->
            <div id="tab-content-categorias" class="admin-tab-content hidden space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Categorías del Menú</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Agrupaciones del menú gastronómico</p>
                    </div>
                    <button onclick="openModalCategoria()" class="btn-action px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl shadow flex items-center gap-1.5">
                        <span>+</span> Nueva Categoría
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200 text-[11px]">
                                <tr>
                                    <th class="p-3.5">ID</th>
                                    <th class="p-3.5">Nombre</th>
                                    <th class="p-3.5">Descripción</th>
                                    <th class="p-3.5">Platillos Asociados</th>
                                    <th class="p-3.5">Estado</th>
                                    <th class="p-3.5 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-categorias" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 4. TAB: MESAS -->
            <div id="tab-content-mesas" class="admin-tab-content hidden space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Gestión de Mesas</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Configura la numeración y el estado operativo de las mesas</p>
                    </div>
                    <button onclick="openModalMesa()" class="btn-action px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl shadow flex items-center gap-1.5">
                        <span>+</span> Nueva Mesa
                    </button>
                </div>

                <div id="grid-mesas" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4"></div>
            </div>

            <!-- 5. TAB: HISTORIAL DE ÓRDENES -->
            <div id="tab-content-ordenes" class="admin-tab-content hidden space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Historial de Órdenes</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Registro histórico de comandas, mesas y montos</p>
                    </div>
                    <div class="flex items-center gap-1 text-xs">
                        <select id="filtro-historial-estado" onchange="renderAdminOrdenes()" class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-700 outline-none font-medium">
                            <option value="todos">Todos los estados</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="preparando">Preparando</option>
                            <option value="listo">Listo</option>
                            <option value="entregado">Entregado</option>
                            <option value="cerrada">Cerrada</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200 text-[11px]">
                                <tr>
                                    <th class="p-3.5"># Orden</th>
                                    <th class="p-3.5">Mesa</th>
                                    <th class="p-3.5">Mesero</th>
                                    <th class="p-3.5">Total</th>
                                    <th class="p-3.5">Estado</th>
                                    <th class="p-3.5">Fecha / Hora</th>
                                    <th class="p-3.5 text-right">Detalle</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-admin-ordenes" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 6. TAB: PAGOS REGISTRADOS -->
            <div id="tab-content-pagos" class="admin-tab-content hidden space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Registro de Pagos</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Historial financiero de transacciones confirmadas</p>
                    </div>
                    <div class="px-3 py-1.5 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-xl font-bold text-xs" id="pagos-total-banner">
                        Total: $0.00
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200 text-[11px]">
                                <tr>
                                    <th class="p-3.5">ID Pago</th>
                                    <th class="p-3.5"># Orden</th>
                                    <th class="p-3.5">Mesa</th>
                                    <th class="p-3.5">Método</th>
                                    <th class="p-3.5">Monto</th>
                                    <th class="p-3.5">Estado</th>
                                    <th class="p-3.5">Fecha</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-pagos" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 7. TAB: USUARIOS & MESEROS -->
            <div id="tab-content-usuarios" class="admin-tab-content hidden space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div>
                        <h2 class="text-base font-extrabold text-[#2E3C43] tracking-tight">Gestión de Personal & Meseros</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Crea meseros y empleados para que puedan iniciar sesión en la app y registrar comandas</p>
                    </div>
                    <button onclick="openModalUsuario()"
                        class="btn-action px-4 py-2.5 bg-[#2E3C43] hover:bg-[#202c32] text-white font-bold rounded-xl text-xs flex items-center gap-2 shadow-sm">
                        <span>👤 + Nuevo Empleado / Mesero</span>
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-[#2E3C43] font-bold uppercase tracking-wider border-b border-slate-200 text-[11px]">
                                <tr>
                                    <th class="p-3.5">ID</th>
                                    <th class="p-3.5">Usuario / Empleado</th>
                                    <th class="p-3.5">Correo Electrónico</th>
                                    <th class="p-3.5">Rol en Restaurante</th>
                                    <th class="p-3.5">Acceso Móvil</th>
                                    <th class="p-3.5">Fecha Registro</th>
                                    <th class="p-3.5 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-usuarios" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 8. TAB: REPORTES (PENDIENTE DE BACKEND) -->
            <div id="tab-content-reportes" class="admin-tab-content hidden space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl shrink-0">
                            📈
                        </div>
                        <div>
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300 mb-2">
                                Módulo Pendiente de Backend
                            </span>
                            <h2 class="text-lg font-bold text-slate-900">Reportes Financieros y Métricas de Rendimiento</h2>
                            <p class="text-sm text-slate-600 mt-1 leading-relaxed max-w-2xl">
                                Este módulo forma parte del alcance funcional original del sistema y se activará en cuanto se programen los endpoints de estadísticas y reportes agrupados (<code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded text-slate-800">/api/reportes</code>).
                            </p>
                            <p class="text-xs text-slate-500 mt-2">
                                💡 Mientras tanto, puedes consultar la información financiera en tiempo real desde la pestaña <strong>📊 Resumen</strong> y <strong>💳 Pagos</strong>.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>


    <!-- ================= MODALES DE ADMINISTRACIÓN ================= -->

    <!-- Modal Platillo -->
    <div id="modal-platillo" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                <h3 id="modal-platillo-titulo" class="font-bold text-slate-900 text-sm">Nuevo Platillo</h3>
                <button onclick="closeModalPlatillo()" class="text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg p-1.5 transition-colors duration-150 text-lg leading-none">&times;</button>
            </div>
            <form id="form-platillo" onsubmit="savePlatillo(event)" class="p-5 space-y-4 text-xs">
                <input type="hidden" id="platillo-id">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nombre del Platillo *</label>
                    <input type="text" id="platillo-nombre" required class="w-full px-3 py-2 border rounded-xl outline-none focus:border-amber-500 text-xs">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Categoría *</label>
                        <select id="platillo-categoria" required class="w-full px-3 py-2 border rounded-xl outline-none focus:border-amber-500 text-xs"></select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Precio ($) *</label>
                        <input type="number" id="platillo-precio" step="0.01" min="0" required class="w-full px-3 py-2 border rounded-xl outline-none focus:border-amber-500 text-xs">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Descripción</label>
                    <textarea id="platillo-descripcion" rows="2" class="w-full px-3 py-2 border rounded-xl outline-none focus:border-amber-500 text-xs"></textarea>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">URL de Imagen (opcional)</label>
                    <input type="url" id="platillo-imagen" placeholder="https://..." class="w-full px-3 py-2 border rounded-xl outline-none focus:border-amber-500 text-xs">
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="platillo-disponible" class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500">
                    <label for="platillo-disponible" class="font-semibold text-slate-700">Disponible para ordenar</label>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModalPlatillo()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl font-semibold text-slate-700 transition">Cancelar</button>
                    <button type="submit" class="btn-action px-4 py-2 bg-[#2E3C43] hover:bg-[#202c32] text-white rounded-xl font-semibold shadow">Guardar Platillo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Categoría -->
    <div id="modal-categoria" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                <h3 id="modal-categoria-titulo" class="font-bold text-slate-900 text-sm">Nueva Categoría</h3>
                <button onclick="closeModalCategoria()" class="text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg p-1.5 transition-colors duration-150 text-lg leading-none">&times;</button>
            </div>
            <form id="form-categoria" onsubmit="saveCategoria(event)" class="p-5 space-y-4 text-xs">
                <input type="hidden" id="categoria-id">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nombre de la Categoría *</label>
                    <input type="text" id="categoria-nombre" required class="w-full px-3 py-2 border rounded-xl outline-none focus:border-[#2879A8] text-xs">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Descripción</label>
                    <textarea id="categoria-descripcion" rows="2" class="w-full px-3 py-2 border rounded-xl outline-none focus:border-[#2879A8] text-xs"></textarea>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="categoria-activo" checked class="w-4 h-4 rounded text-[#2879A8] focus:ring-[#2879A8]">
                    <label for="categoria-activo" class="font-semibold text-slate-700">Categoría Activa</label>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModalCategoria()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl font-semibold text-slate-700 transition">Cancelar</button>
                    <button type="submit" class="btn-action px-4 py-2 bg-[#2E3C43] hover:bg-[#202c32] text-white rounded-xl font-semibold shadow">Guardar Categoría</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Mesa -->
    <div id="modal-mesa" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                <h3 id="modal-mesa-titulo" class="font-bold text-slate-900 text-sm">Registrar Mesa</h3>
                <button onclick="closeModalMesa()" class="text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg p-1.5 transition-colors duration-150 text-lg leading-none">&times;</button>
            </div>
            <form id="form-mesa" onsubmit="saveMesa(event)" class="p-5 space-y-4 text-xs">
                <input type="hidden" id="mesa-id">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Número de Mesa *</label>
                    <input type="number" id="mesa-numero" min="1" required class="w-full px-3 py-2 border rounded-xl outline-none focus:border-[#2879A8] text-xs">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Estado Inicial *</label>
                    <select id="mesa-estado" required class="w-full px-3 py-2 border rounded-xl outline-none focus:border-[#2879A8] text-xs">
                        <option value="disponible">Disponible (Libre)</option>
                        <option value="ocupada">Ocupada</option>
                        <option value="reservada">Reservada</option>
                        <option value="fuera_servicio">Fuera de Servicio</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModalMesa()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl font-semibold text-slate-700 transition">Cancelar</button>
                    <button type="submit" class="btn-action px-4 py-2 bg-[#2E3C43] hover:bg-[#202c32] text-white rounded-xl font-semibold shadow">Guardar Mesa</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Detalle de Orden -->
    <div id="modal-orden-detalle" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                <h3 id="modal-orden-titulo" class="font-bold text-slate-900 text-sm">Detalle de Comanda</h3>
                <button onclick="closeModalOrdenDetalle()" class="text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg p-1.5 transition-colors duration-150 text-lg leading-none">&times;</button>
            </div>
            <div class="p-5 space-y-4 text-xs">
                <div id="modal-orden-info" class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100"></div>
                <div class="border rounded-xl overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-b text-[11px]">
                            <tr>
                                <th class="p-2.5">Cant.</th>
                                <th class="p-2.5">Platillo</th>
                                <th class="p-2.5">Precio</th>
                                <th class="p-2.5 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="modal-orden-detalles-body" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
                <div class="flex justify-end text-sm font-extrabold text-slate-900" id="modal-orden-total"></div>
            </div>
        </div>
    </div>

    <!-- Modal Usuario / Mesero -->
    <div id="modal-usuario" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                <h3 id="modal-usuario-titulo" class="font-extrabold text-[#2E3C43] text-sm">Nuevo Empleado / Mesero</h3>
                <button onclick="closeModalUsuario()" class="text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg p-1.5 transition-colors duration-150 text-lg leading-none">&times;</button>
            </div>
            <form id="form-usuario" onsubmit="saveUsuario(event)" class="p-5 space-y-4 text-xs">
                <input type="hidden" id="usuario-id">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nombre Completo *</label>
                    <input type="text" id="usuario-nombre" required placeholder="Ej. Juan Pérez" class="w-full px-3 py-2 border rounded-xl outline-none focus:border-[#2879A8] text-xs">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Correo Electrónico *</label>
                    <input type="email" id="usuario-email" required placeholder="juan.mesero@restaurante.com" class="w-full px-3 py-2 border rounded-xl outline-none focus:border-[#2879A8] text-xs">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Rol en el Restaurante *</label>
                    <select id="usuario-rol" required class="w-full px-3 py-2 border rounded-xl outline-none focus:border-[#2879A8] text-xs font-semibold text-[#2E3C43] bg-slate-50"></select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1" id="label-usuario-password">Contraseña de Acceso *</label>
                    <input type="password" id="usuario-password" placeholder="Mínimo 6 caracteres" class="w-full px-3 py-2 border rounded-xl outline-none focus:border-[#2879A8] text-xs">
                    <p class="text-[11px] text-slate-400 mt-1" id="help-usuario-password">Se usará para iniciar sesión en la app móvil o el panel web.</p>
                </div>

                <div class="p-3 bg-blue-50/80 border border-blue-200/70 rounded-xl text-blue-900 text-[11px] flex items-start gap-2">
                    <span class="text-base shrink-0">📱</span>
                    <p class="leading-relaxed">Los meseros creados con esta cuenta podrán iniciar sesión de inmediato en la app móvil para tomar órdenes en las mesas.</p>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModalUsuario()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl font-semibold text-slate-700 transition">Cancelar</button>
                    <button type="submit" class="btn-action px-4 py-2 bg-[#2E3C43] hover:bg-[#202c32] text-white rounded-xl font-semibold shadow">Guardar Empleado</button>
                </div>
            </form>
        </div>
    </div>


    <!-- ================= VISTA DE MESERO ================= -->
    <div id="view-mesero" class="hidden min-h-screen flex flex-col bg-slate-100">
        <header class="apple-glass-nav text-white sticky top-0 z-30 shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <img src="/logo.png" alt="Antojería Pingüin"
                         class="w-10 h-10 rounded-full object-cover shadow-md ring-2 ring-[#EBD140] shrink-0"
                         onerror="this.src='/logo.jpg';">
                    <div>
                        <h1 class="text-base font-extrabold leading-tight text-white tracking-tight flex items-center gap-2">
                            Panel de Mesero
                            <span id="mesero-unread-badge" class="hidden text-[10px] font-black px-2 py-0.5 rounded-full bg-[#EBD140] text-[#2E3C43] shadow-sm">0 nuevas</span>
                        </h1>
                        <p class="text-[11px] text-slate-300 leading-none mt-0.5 font-medium">Notificaciones de cocina y estado de entrega por mesa</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="fetchMeseroData()" class="btn-action px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-xl border border-white/15">
                        🔄 Actualizar
                    </button>
                    <span class="text-xs text-white/90 font-medium px-2.5 py-1 bg-white/10 rounded-xl border border-white/10" id="user-display-mesero">
                        Mesero
                    </span>
                    <button onclick="handleLogout()" class="btn-action px-3 py-1.5 bg-[#C24840] hover:bg-[#a83c35] text-white text-xs font-bold rounded-xl shadow-sm">
                        Salir
                    </button>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 flex-1 w-full space-y-6">
            <!-- Notificaciones de Cocina -->
            <section class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-sm font-extrabold text-[#2E3C43] flex items-center gap-2">
                            <span>🔔</span> Notificaciones de Entrega
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Avisos de cocina cuando todos los platillos de una mesa están listos</p>
                    </div>
                    <button onclick="marcarTodasNotificacionesLeidas()" class="btn-action px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-[#2E3C43] text-xs font-bold rounded-xl">
                        ✓ Marcar todas como leídas
                    </button>
                </div>
                <div id="mesero-notificaciones-list" class="space-y-2.5"></div>
            </section>

            <!-- Estado de mesas / órdenes del mesero -->
            <section class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                <h2 class="text-sm font-extrabold text-[#2E3C43] mb-4 flex items-center gap-2">
                    <span>🍽️</span> Estado de Comandas por Mesa
                </h2>
                <div id="mesero-ordenes-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
            </section>
        </main>
    </div>

    <!-- Modal Confirmar Entrega de Mesa -->
    <div id="modal-confirmar-entrega" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-emerald-50/70">
                <h3 class="font-extrabold text-[#2E3C43] text-sm flex items-center gap-2">
                    <span>🍽️</span> Confirmar Entrega de Mesa
                </h3>
                <button onclick="closeConfirmarEntrega()" class="text-slate-400 hover:text-slate-600 rounded-lg p-1.5 text-lg leading-none">&times;</button>
            </div>
            <div class="p-5 space-y-4 text-xs">
                <p id="modal-entrega-mensaje" class="text-slate-700 leading-relaxed font-medium"></p>
                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900 text-[11px] font-semibold">
                    Se enviará una notificación al mesero responsable indicando que todos los platillos de la mesa están listos para llevarse a los comensales.
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeConfirmarEntrega()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl font-semibold text-slate-700">Cancelar</button>
                    <button type="button" id="btn-confirmar-entrega-submit" onclick="confirmarEntregarMesa()" class="btn-action px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow">
                        Confirmar Entrega
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenedor flotante de notificaciones / toasts -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <!-- ================= JAVASCRIPT ================= -->
    <script>
        // ESTADO GLOBAL
        let currentFilter = 'todas';
        let allOrders = [];
        let allMesaGroups = [];
        let deliveredMesaGroups = [];
        let pendingDeliveryMesa = null;
        let isDeliveringMesa = false;
        let lastMeseroUnreadCount = 0;
        let meseroNotificaciones = [];
        let meseroOrdenes = [];
        let allAdminOrders = [];
        let allPlatillos = [];
        let allCategorias = [];
        let allMesas = [];
        let allPagos = [];
        let allUsuarios = [];
        let allRoles = [];
        let pollingInterval = null;
        let lastOrdersCount = 0;

        // INICIALIZACIÓN
        document.addEventListener('DOMContentLoaded', () => {
            checkAuth();
        });

        function checkAuth() {
            const token = localStorage.getItem('token');
            const userStr = localStorage.getItem('user');

            if (!token || !userStr) {
                showView('login');
                stopPolling();
                return;
            }

            try {
                const user = JSON.parse(userStr);
                const role = user.rol;

                // Si es admin, mostramos el botón de retorno a admin cuando esté en cocina
                const btnReturn = document.getElementById('btn-return-admin');
                if (btnReturn) {
                    btnReturn.classList.toggle('hidden', role !== 'Administrador');
                }

                if (role === 'Cocina') {
                    showView('cocina');
                    document.getElementById('user-display-cocina').innerText = `${user.nombre} (${role})`;
                    fetchOrdenes();
                    startPolling(5000); // Polling cada 5s en cocina
                } else if (role === 'Administrador') {
                    showView('admin');
                    document.getElementById('user-display-admin').innerText = `${user.nombre} (${role})`;
                    loadAdminData();
                    startPolling(8000); // Polling cada 8s en admin para reflejar lo que envíe el móvil
                } else if (role === 'Mesero') {
                    showView('mesero');
                    document.getElementById('user-display-mesero').innerText = `${user.nombre} (${role})`;
                    fetchMeseroData();
                    startPolling(5000);
                } else {
                    showView('cocina');
                    fetchOrdenes();
                    startPolling(5000);
                }
            } catch (e) {
                localStorage.clear();
                showView('login');
            }
        }

        function showView(viewName) {
            document.getElementById('view-login').classList.toggle('hidden', viewName !== 'login');
            document.getElementById('view-cocina').classList.toggle('hidden', viewName !== 'cocina');
            document.getElementById('view-admin').classList.toggle('hidden', viewName !== 'admin');
            document.getElementById('view-mesero').classList.toggle('hidden', viewName !== 'mesero');
        }

        // PESTAÑAS ADMIN
        function switchAdminTab(tabName) {
            document.querySelectorAll('.admin-tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.admin-tab-btn').forEach(btn => {
                btn.classList.remove('tab-active', 'text-white');
                btn.classList.add('text-slate-400');
            });

            const content = document.getElementById(`tab-content-${tabName}`);
            const btn = document.getElementById(`tab-btn-${tabName}`);
            if (content) content.classList.remove('hidden');
            if (btn) {
                btn.classList.add('tab-active', 'text-white');
                btn.classList.remove('text-slate-400');
            }
        }

        // LOGIN / LOGOUT
        async function handleLogin(e) {
            e.preventDefault();
            await doLogin(document.getElementById('login-email').value, document.getElementById('login-password').value);
        }

        async function doLogin(email, password) {
            const errorDiv = document.getElementById('login-error');
            const submitBtn = document.getElementById('btn-login-submit');
            errorDiv.classList.add('hidden');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Verificando...</span>';

            try {
                const res = await fetch('/api/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const data = await res.json();
                if (!res.ok) throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Credenciales incorrectas'));

                localStorage.setItem('token', data.token);
                localStorage.setItem('user', JSON.stringify(data.usuario));
                checkAuth();
            } catch (err) {
                errorDiv.innerText = err.message;
                errorDiv.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span>Ingresar</span>';
            }
        }

        async function handleLogout() {
            const token = localStorage.getItem('token');
            stopPolling();
            if (token) {
                try {
                    await fetch('/api/logout', {
                        method: 'POST',
                        headers: apiHeaders()
                    });
                } catch (e) {}
            }
            localStorage.clear();
            showView('login');
        }

        // ENCABEZADOS ESTRICTOS DE LA API (Consumidos por Web y Móvil)
        function apiHeaders() {
            const token = localStorage.getItem('token') || '';
            return {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            };
        }

        // SONIDO Y NOTIFICACIÓN AL LLEGAR COMANDAS MÓVILES
        function playChime() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                osc.frequency.setValueAtTime(880, ctx.currentTime + 0.12);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.45);
            } catch(e) {}
        }

        function showNotification(msg, type = 'info') {
            const container = document.getElementById('toast-container');
            if (!container) return;
            const toast = document.createElement('div');

            let bg = 'bg-[#2E3C43] border-slate-700 text-white';
            let icon = '🔔';

            if (type === 'error') {
                bg = 'bg-[#C24840] border-red-700 text-white shadow-red-900/40';
                icon = '⚠️';
            } else if (type === 'success') {
                bg = 'bg-emerald-800 border-emerald-600 text-white shadow-emerald-900/40';
                icon = '✅';
            } else if (type === 'warning') {
                bg = 'bg-[#b88c00] border-amber-500 text-white shadow-amber-900/40';
                icon = '⚡';
            }

            toast.className = `pointer-events-auto ${bg} text-xs font-semibold px-4 py-3 rounded-xl shadow-2xl border flex items-center gap-2.5 transform transition-all duration-300 translate-y-2 scale-95 opacity-0 z-50`;
            toast.innerHTML = `<span class="text-sm shrink-0">${icon}</span> <span class="leading-tight">${msg}</span>`;
            container.appendChild(toast);
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-2', 'scale-95', 'opacity-0');
            });
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2', 'scale-95');
                setTimeout(() => toast.remove(), 300);
            }, 4500);
        }

        // POLLING AUTOMÁTICO PARA COCINA Y ADMIN
        function startPolling(intervalMs = 5000) {
            stopPolling();
            pollingInterval = setInterval(() => {
                const userStr = localStorage.getItem('user');
                if (!userStr) return;
                const user = JSON.parse(userStr);
                if (user.rol === 'Cocina') {
                    fetchOrdenes(true);
                } else if (user.rol === 'Administrador') {
                    loadAdminData(true);
                } else if (user.rol === 'Mesero') {
                    fetchMeseroData(true);
                }
            }, intervalMs);
        }

        function stopPolling() {
            if (pollingInterval) clearInterval(pollingInterval);
        }

        function buildMesaGroups(ordenes, includeDelivered = false) {
            const map = new Map();

            ordenes.forEach(orden => {
                const mesaId = orden.mesa_id;
                if (!map.has(mesaId)) {
                    map.set(mesaId, {
                        mesa_id: mesaId,
                        mesa: orden.mesa || { id: mesaId, numero: 'S/N' },
                        ordenes: [],
                        detalles: [],
                        meseros: [],
                        created_at: orden.created_at,
                    });
                }

                const group = map.get(mesaId);
                group.ordenes.push(orden);

                if (orden.user && !group.meseros.some(m => m.id === orden.user.id)) {
                    group.meseros.push(orden.user);
                }

                if (new Date(orden.created_at) < new Date(group.created_at)) {
                    group.created_at = orden.created_at;
                }

                (orden.detalles || []).forEach(d => {
                    group.detalles.push({
                        ...d,
                        estado: d.estado || (orden.estado === 'entregado' ? 'entregado' : (orden.estado === 'listo' ? 'listo' : (orden.estado === 'preparando' ? 'preparando' : 'pendiente'))),
                        orden_id: orden.id,
                    });
                });
            });

            return Array.from(map.values()).map(group => {
                const total = group.detalles.length;
                const pendientes = group.detalles.filter(d => d.estado === 'pendiente').length;
                const preparando = group.detalles.filter(d => d.estado === 'preparando').length;
                const listos = group.detalles.filter(d => d.estado === 'listo').length;
                const entregados = group.detalles.filter(d => d.estado === 'entregado').length;

                let estadoMesa = 'pendiente';
                if (includeDelivered && total > 0 && entregados === total) {
                    estadoMesa = 'entregado';
                } else if (total > 0 && pendientes === 0 && preparando === 0 && listos > 0) {
                    estadoMesa = 'listo';
                } else if (preparando > 0 || listos > 0) {
                    estadoMesa = 'preparando';
                }

                const listosOParaEntrega = listos + entregados;
                const progreso = total > 0 ? Math.round((listosOParaEntrega / total) * 100) : 0;
                const puedeEntregar = total > 0 && pendientes === 0 && preparando === 0 && listos > 0;

                return {
                    ...group,
                    total,
                    pendientes,
                    preparando,
                    listos,
                    entregados,
                    progreso,
                    estadoMesa,
                    puedeEntregar,
                };
            });
        }

        async function fetchOrdenes(isSilent = false) {
            const token = localStorage.getItem('token');
            if (!token) return;

            const syncIcon = document.getElementById('sync-icon');
            if (!isSilent && syncIcon) syncIcon.classList.add('animate-spin');

            try {
                const res = await fetch('/api/ordenes', { headers: apiHeaders() });
                if (res.status === 401) { handleLogout(); return; }
                if (!res.ok) throw new Error('Error al obtener órdenes');

                const data = await res.json();
                const active = data.filter(o => ['pendiente', 'preparando', 'listo'].includes(o.estado));
                const delivered = data.filter(o => o.estado === 'entregado');

                // Si llegaron comandas nuevas desde la app móvil, emitir aviso sonoro y visual
                if (isSilent && active.length > lastOrdersCount && lastOrdersCount > 0) {
                    playChime();
                    showNotification('¡Nueva comanda registrada desde la app móvil!');
                }
                lastOrdersCount = active.length;
                allOrders = active;
                allMesaGroups = buildMesaGroups(active, false);
                deliveredMesaGroups = buildMesaGroups(delivered, true);

                updateCounters();
                renderOrders();

                const syncText = document.getElementById('cocina-sync-text');
                if (syncText) {
                    syncText.innerText = `Sincronizado: ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })}`;
                }
            } catch (err) {
                console.error(err);
            } finally {
                if (!isSilent && syncIcon) syncIcon.classList.remove('animate-spin');
            }
        }

        async function updateDetalleEstado(detalleId, nuevoEstado) {
            try {
                const res = await fetch(`/api/orden-detalles/${detalleId}/estado`, {
                    method: 'PATCH',
                    headers: apiHeaders(),
                    body: JSON.stringify({ estado: nuevoEstado })
                });

                const data = await res.json();
                if (!res.ok) {
                    showNotification(data.mensaje || 'Error al actualizar platillo', 'error');
                    return;
                }
                showNotification(
                    nuevoEstado === 'preparando' ? 'Platillo marcado en preparación' : 'Platillo marcado como listo',
                    'success'
                );
                await fetchOrdenes();
            } catch (err) {
                showNotification('Error de conexión con el servidor', 'error');
            }
        }

        function openConfirmarEntrega(mesaId) {
            const group = allMesaGroups.find(g => g.mesa_id === mesaId);
            if (!group || !group.puedeEntregar) {
                showNotification('No puedes entregar la mesa mientras queden platillos pendientes o en preparación.', 'warning');
                return;
            }
            pendingDeliveryMesa = group;
            const meserosTxt = group.meseros.length ? group.meseros.map(m => m.name).join(', ') : 'Mesero asignado';
            document.getElementById('modal-entrega-mensaje').innerText =
                `¿Confirmas la entrega de la Mesa ${group.mesa.numero}? (${group.total} platillo${group.total === 1 ? '' : 's'} listo${group.total === 1 ? '' : 's'} · Responsable: ${meserosTxt})`;
            document.getElementById('modal-confirmar-entrega').classList.remove('hidden');
        }

        function closeConfirmarEntrega() {
            pendingDeliveryMesa = null;
            document.getElementById('modal-confirmar-entrega').classList.add('hidden');
        }

        async function confirmarEntregarMesa() {
            if (!pendingDeliveryMesa || isDeliveringMesa) return;
            isDeliveringMesa = true;
            const btn = document.getElementById('btn-confirmar-entrega-submit');
            if (btn) {
                btn.disabled = true;
                btn.innerText = 'Enviando...';
            }

            try {
                const res = await fetch(`/api/mesas/${pendingDeliveryMesa.mesa_id}/entregar`, {
                    method: 'POST',
                    headers: apiHeaders(),
                });
                const data = await res.json();
                if (!res.ok) {
                    showNotification(data.mensaje || 'No se pudo entregar la mesa', 'error');
                    return;
                }
                closeConfirmarEntrega();
                showNotification(data.mensaje || 'Mesa entregada y mesero notificado', 'success');
                await fetchOrdenes();
            } catch (err) {
                showNotification('Error de conexión con el servidor', 'error');
            } finally {
                isDeliveringMesa = false;
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = 'Confirmar Entrega';
                }
            }
        }

        function setFilter(filter) {
            currentFilter = filter;
            document.querySelectorAll('.filter-btn').forEach(btn => {
                const isActive = btn.dataset.filter === filter;
                btn.classList.toggle('bg-[#2E3C43]', isActive);
                btn.classList.toggle('text-white', isActive);
                btn.classList.toggle('bg-white', !isActive);
                btn.classList.toggle('text-[#2E3C43]', !isActive);
            });
            renderOrders();
        }

        function updateCounters() {
            const countPendiente = allMesaGroups.filter(g => g.estadoMesa === 'pendiente').length;
            const countPreparando = allMesaGroups.filter(g => g.estadoMesa === 'preparando').length;
            const countListo = allMesaGroups.filter(g => g.estadoMesa === 'listo').length;
            const countEntregado = deliveredMesaGroups.length;

            document.getElementById('badge-count-todas').innerText = allMesaGroups.length;
            document.getElementById('badge-count-pendiente').innerText = countPendiente;
            document.getElementById('badge-count-preparando').innerText = countPreparando;
            document.getElementById('badge-count-listo').innerText = countListo;
            const badgeEntregado = document.getElementById('badge-count-entregado');
            if (badgeEntregado) badgeEntregado.innerText = countEntregado;
        }

        function renderDetalleBadge(estado) {
            if (estado === 'preparando') {
                return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-[#2879A8]/15 text-[#2879A8] border border-[#2879A8]/30">🔥 En preparación</span>`;
            }
            if (estado === 'listo') {
                return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">✅ Listo</span>`;
            }
            if (estado === 'entregado') {
                return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-200 text-slate-700 border border-slate-300">🍽️ Entregado</span>`;
            }
            return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-[#EBD140]/30 text-[#685305] border border-[#EBD140]/50">⏳ Pendiente</span>`;
        }

        function renderOrders() {
            const grid = document.getElementById('ordenes-grid');
            const empty = document.getElementById('ordenes-empty');

            let filtered;
            if (currentFilter === 'entregado') {
                filtered = deliveredMesaGroups;
            } else if (currentFilter === 'todas') {
                filtered = allMesaGroups;
            } else {
                filtered = allMesaGroups.filter(g => g.estadoMesa === currentFilter);
            }

            if (filtered.length === 0) {
                grid.innerHTML = '';
                empty.classList.remove('hidden');
                return;
            }

            empty.classList.add('hidden');

            grid.innerHTML = filtered.map(group => {
                let cardClass, badgeBg, badgeText, badgeIcon, barColor;

                const elapsed = Math.round((Date.now() - new Date(group.created_at)) / 60000);
                const elapsedStr = isNaN(elapsed) ? '' : `⏱ ${elapsed}m`;

                if (group.estadoMesa === 'entregado') {
                    cardClass = 'bg-white border-l-4 border-slate-400';
                    badgeBg = 'bg-slate-200 text-slate-800 border-slate-300';
                    badgeText = 'Entregada';
                    badgeIcon = '🍽️';
                    barColor = 'bg-slate-500';
                } else if (group.estadoMesa === 'listo') {
                    cardClass = 'card-listo';
                    badgeBg = 'bg-emerald-100 text-emerald-900 border-emerald-300';
                    badgeText = 'Lista para Entregar';
                    badgeIcon = '✅';
                    barColor = 'bg-emerald-500';
                } else if (group.estadoMesa === 'preparando') {
                    cardClass = 'card-preparando';
                    badgeBg = 'bg-[#2879A8]/15 text-[#2879A8] border-[#2879A8]/30';
                    badgeText = 'En Preparación';
                    badgeIcon = '🔥';
                    barColor = 'bg-[#2879A8]';
                } else {
                    cardClass = 'card-pendiente';
                    badgeBg = 'bg-[#EBD140]/30 text-[#685305] border-[#EBD140]/50';
                    badgeText = 'Pendiente';
                    badgeIcon = '⏳';
                    barColor = 'bg-[#EBD140]';
                }

                const dateObj = new Date(group.created_at);
                const timeStr = isNaN(dateObj) ? '' : dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const ordenesLabel = group.ordenes.map(o => `#${o.id}`).join(', ');
                const meserosLabel = group.meseros.length ? group.meseros.map(m => m.name).join(', ') : 'Sin asignar';

                const platillosHtml = group.detalles.map(d => {
                    let itemAction = '';
                    if (d.estado === 'pendiente') {
                        itemAction = `<button onclick="updateDetalleEstado(${d.id}, 'preparando')" class="btn-action px-2.5 py-1.5 bg-[#EBD140] hover:bg-[#dfc635] text-[#2E3C43] font-extrabold rounded-lg text-[11px] shadow-sm whitespace-nowrap">🍳 En preparación</button>`;
                    } else if (d.estado === 'preparando') {
                        itemAction = `<button onclick="updateDetalleEstado(${d.id}, 'listo')" class="btn-action px-2.5 py-1.5 bg-[#2879A8] hover:bg-[#20638a] text-white font-bold rounded-lg text-[11px] shadow-sm whitespace-nowrap">✓ Listo</button>`;
                    }

                    return `
                    <li class="py-3 border-b border-slate-100 last:border-none">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-start gap-2.5 min-w-0">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[#2E3C43] text-white text-xs font-bold shrink-0">
                                    ${d.cantidad}x
                                </span>
                                <div class="min-w-0">
                                    <span class="font-bold text-[#2E3C43] text-sm leading-snug block">
                                        ${d.platillo ? d.platillo.nombre : 'Platillo'}
                                    </span>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                        ${renderDetalleBadge(d.estado)}
                                        <span class="text-[10px] text-slate-400 font-semibold">Comanda #${d.orden_id}</span>
                                    </div>
                                </div>
                            </div>
                            ${itemAction ? `<div class="shrink-0">${itemAction}</div>` : ''}
                        </div>
                        ${d.notas ? `
                            <div class="mt-2 ml-10 px-2.5 py-1.5 bg-[#EBD140]/15 border border-[#EBD140]/30 text-[#544304] text-xs rounded-lg flex items-start gap-1.5">
                                <span class="shrink-0 text-[#C24840] font-bold">Nota:</span>
                                <span class="font-medium">${d.notas}</span>
                            </div>
                        ` : ''}
                    </li>
                    `;
                }).join('');

                let footerHtml = '';
                if (group.estadoMesa === 'entregado') {
                    footerHtml = `<div class="w-full py-3 px-4 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl text-center border border-slate-200">🍽️ Mesa entregada al mesero</div>`;
                } else {
                    const faltantes = group.pendientes + group.preparando;
                    footerHtml = `
                        <button onclick="openConfirmarEntrega(${group.mesa_id})"
                            ${group.puedeEntregar ? '' : 'disabled'}
                            class="btn-action w-full py-3.5 px-4 rounded-xl text-sm font-extrabold flex items-center justify-center gap-2 shadow-md ${group.puedeEntregar ? 'bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer' : 'bg-slate-200 text-slate-500 cursor-not-allowed opacity-75 shadow-none'}">
                            <span>🍽️ Entregar mesa</span>
                        </button>
                        ${!group.puedeEntregar ? `<p class="text-[11px] text-slate-500 font-medium text-center mt-2">Faltan ${faltantes} platillo${faltantes === 1 ? '' : 's'} por terminar antes de entregar</p>` : ''}
                    `;
                }

                return `
                    <div class="${cardClass} bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col justify-between border border-slate-200/70">
                        <div>
                            <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                                <div class="flex items-center gap-2">
                                    <span class="px-3 py-1 rounded-lg bg-[#2E3C43] text-white text-sm font-black">
                                        Mesa ${group.mesa ? group.mesa.numero : 'S/N'}
                                    </span>
                                    <span class="text-xs font-bold text-slate-500">${ordenesLabel}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    ${elapsedStr ? `<span class="text-xs text-slate-500 font-medium">${elapsedStr}</span>` : ''}
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border ${badgeBg}">
                                        <span>${badgeIcon}</span>
                                        <span>${badgeText}</span>
                                    </span>
                                </div>
                            </div>
                            <div class="px-4 py-2 bg-slate-100/60 flex items-center justify-between text-[11px] text-slate-600 border-b border-slate-100">
                                <span class="inline-flex items-center gap-1.5 font-medium">Mesero: <strong class="bg-[#EBD140]/30 text-[#2E3C43] px-2 py-0.5 rounded-md border border-[#EBD140]/50 font-bold">👤 ${meserosLabel}</strong></span>
                                <span>Hora: <strong class="text-slate-700 font-semibold">${timeStr}</strong></span>
                            </div>
                            <!-- Progreso general de la mesa -->
                            <div class="px-4 pt-3 pb-2 bg-slate-50/40 border-b border-slate-100">
                                <div class="flex items-center justify-between text-[11px] font-bold text-[#2E3C43] mb-1.5">
                                    <span>Progreso de mesa</span>
                                    <span>${group.listos + group.entregados}/${group.total} platillos listos (${group.progreso}%)</span>
                                </div>
                                <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-full ${barColor} transition-all duration-300" style="width: ${group.progreso}%"></div>
                                </div>
                                <div class="flex items-center gap-3 mt-1.5 text-[10px] font-semibold text-slate-500">
                                    <span>⏳ ${group.pendientes} pend.</span>
                                    <span>🔥 ${group.preparando} prep.</span>
                                    <span>✅ ${group.listos} listos</span>
                                </div>
                            </div>
                            <div class="p-4">
                                <ul class="divide-y divide-slate-100">${platillosHtml}</ul>
                            </div>
                        </div>
                        <div class="p-4 bg-slate-50/60 border-t border-slate-100">
                            ${footerHtml}
                        </div>
                    </div>
                `;
            }).join('');
        }

        // ================= SECCIÓN MESERO (NOTIFICACIONES Y ENTREGAS) =================
        async function fetchMeseroData(isSilent = false) {
            const token = localStorage.getItem('token');
            if (!token) return;

            try {
                const [resNotif, resOrdenes] = await Promise.all([
                    fetch('/api/notificaciones', { headers: apiHeaders() }),
                    fetch('/api/ordenes', { headers: apiHeaders() }),
                ]);

                if (resNotif.status === 401 || resOrdenes.status === 401) {
                    handleLogout();
                    return;
                }

                if (resNotif.ok) {
                    const dataNotif = await resNotif.json();
                    const unreadCount = dataNotif.unread_count || 0;
                    if (isSilent && unreadCount > lastMeseroUnreadCount) {
                        playChime();
                        showNotification('¡Tienes una mesa lista para entregar!', 'success');
                    }
                    lastMeseroUnreadCount = unreadCount;
                    meseroNotificaciones = dataNotif.notificaciones || [];
                }

                if (resOrdenes.ok) {
                    meseroOrdenes = await resOrdenes.json();
                }

                renderMeseroPanel();
            } catch (err) {
                if (!isSilent) console.error(err);
            }
        }

        async function marcarNotificacionLeida(id) {
            try {
                const res = await fetch(`/api/notificaciones/${id}/leer`, {
                    method: 'PATCH',
                    headers: apiHeaders(),
                });
                if (res.ok) await fetchMeseroData(true);
            } catch (err) {}
        }

        async function marcarTodasNotificacionesLeidas() {
            try {
                const res = await fetch('/api/notificaciones/leer-todas', {
                    method: 'POST',
                    headers: apiHeaders(),
                });
                if (res.ok) {
                    showNotification('Notificaciones marcadas como leídas', 'success');
                    await fetchMeseroData(true);
                }
            } catch (err) {}
        }

        function renderMeseroPanel() {
            const badge = document.getElementById('mesero-unread-badge');
            if (badge) {
                if (lastMeseroUnreadCount > 0) {
                    badge.innerText = `${lastMeseroUnreadCount} nueva${lastMeseroUnreadCount === 1 ? '' : 's'}`;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }

            const notifList = document.getElementById('mesero-notificaciones-list');
            if (notifList) {
                if (meseroNotificaciones.length === 0) {
                    notifList.innerHTML = `<p class="text-xs text-slate-400 py-4 text-center">No tienes notificaciones por ahora.</p>`;
                } else {
                    notifList.innerHTML = meseroNotificaciones.map(n => {
                        const isUnread = !n.read_at;
                        const data = n.data || {};
                        const fecha = n.created_at ? new Date(n.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
                        return `
                            <div class="p-3.5 rounded-xl border ${isUnread ? 'bg-emerald-50/80 border-emerald-300' : 'bg-slate-50 border-slate-200'} flex items-center justify-between gap-3">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-extrabold text-[#2E3C43]">${data.titulo || 'Mesa lista'}</span>
                                        ${isUnread ? '<span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-600 text-white">Nueva</span>' : ''}
                                        <span class="text-[11px] text-slate-400">${fecha}</span>
                                    </div>
                                    <p class="text-xs text-slate-600">${data.mensaje || ''}</p>
                                </div>
                                ${isUnread ? `<button onclick="marcarNotificacionLeida('${n.id}')" class="btn-action px-2.5 py-1.5 bg-white hover:bg-slate-100 border border-slate-200 text-[#2E3C43] rounded-lg text-[11px] font-bold shrink-0">Marcar leída</button>` : ''}
                            </div>
                        `;
                    }).join('');
                }
            }

            const ordenesGrid = document.getElementById('mesero-ordenes-grid');
            if (ordenesGrid) {
                const activas = meseroOrdenes.filter(o => ['pendiente', 'preparando', 'listo', 'entregado'].includes(o.estado));
                if (activas.length === 0) {
                    ordenesGrid.innerHTML = `<p class="text-xs text-slate-400 col-span-full py-4 text-center">No hay comandas activas o recientes.</p>`;
                } else {
                    ordenesGrid.innerHTML = activas.map(o => `
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-sm text-[#2E3C43]">Mesa ${o.mesa ? o.mesa.numero : 'S/N'} · #${o.id}</span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold ${badgeClass(o.estado)}">${o.estado.charAt(0).toUpperCase() + o.estado.slice(1)}</span>
                            </div>
                            <ul class="space-y-1 text-xs text-slate-600">
                                ${(o.detalles || []).map(d => `
                                    <li class="flex items-center justify-between gap-2">
                                        <span><strong>${d.cantidad}x</strong> ${d.platillo ? d.platillo.nombre : 'Platillo'}</span>
                                        ${renderDetalleBadge(d.estado || o.estado)}
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    `).join('');
                }
            }
        }

        // ================= SECCIÓN ADMINISTRACIÓN =================
        async function loadAdminData(isSilent = false) {
            try {
                const [rPlatillos, rCategorias, rMesas, rOrdenes, rPagos, rUsuarios, rRoles] = await Promise.all([
                    fetch('/api/platillos', { headers: apiHeaders() }),
                    fetch('/api/categorias', { headers: apiHeaders() }),
                    fetch('/api/mesas', { headers: apiHeaders() }),
                    fetch('/api/ordenes', { headers: apiHeaders() }),
                    fetch('/api/pagos', { headers: apiHeaders() }),
                    fetch('/api/usuarios', { headers: apiHeaders() }),
                    fetch('/api/roles', { headers: apiHeaders() })
                ]);

                if (rPlatillos.ok) allPlatillos = await rPlatillos.json();
                if (rCategorias.ok) allCategorias = await rCategorias.json();
                if (rMesas.ok) allMesas = await rMesas.json();
                if (rOrdenes.ok) allAdminOrders = await rOrdenes.json();
                if (rPagos.ok) allPagos = await rPagos.json();
                if (rUsuarios.ok) allUsuarios = await rUsuarios.json();
                if (rRoles.ok) allRoles = await rRoles.json();

                renderAdminResumen();
                renderAdminPlatillos();
                renderAdminCategorias();
                renderAdminMesas();
                renderAdminOrdenes();
                renderAdminPagos();
                renderAdminUsuarios();
            } catch (err) {
                if (!isSilent) console.error("Error al cargar datos administrativos:", err);
            }
        }

        // RESUMEN GENERAL
        function renderAdminResumen() {
            const totalVentas = allPagos.reduce((sum, p) => sum + parseFloat(p.monto || 0), 0);
            document.getElementById('metric-ventas').innerText = `$${totalVentas.toFixed(2)}`;
            document.getElementById('metric-pagos-count').innerText = `${allPagos.length} transacciones registradas`;

            document.getElementById('metric-ordenes').innerText = allAdminOrders.length;
            const activas = allAdminOrders.filter(o => ['pendiente', 'preparando', 'listo'].includes(o.estado)).length;
            const terminadas = allAdminOrders.filter(o => ['entregado', 'cerrada'].includes(o.estado)).length;
            document.getElementById('metric-ordenes-sub').innerText = `${activas} activas / ${terminadas} cerradas`;

            const ocupadas = allMesas.filter(m => m.estado === 'ocupada').length;
            const disponibles = allMesas.filter(m => m.estado === 'disponible').length;
            document.getElementById('metric-mesas').innerText = `${ocupadas}/${allMesas.length}`;
            document.getElementById('metric-mesas-sub').innerText = `${disponibles} disponibles para clientes`;

            document.getElementById('metric-platillos').innerText = allPlatillos.length;
            document.getElementById('metric-categorias').innerText = `en ${allCategorias.length} categorías activas`;

            // Últimas órdenes resumen con acentos de color de marca
            const stateColors = {
                pendiente: 'border-l-[#EBD140]',
                preparando: 'border-l-[#2879A8]',
                listo: 'border-l-emerald-500',
                entregado: 'border-l-[#2E3C43]',
                cerrada: 'border-l-slate-300',
                cancelada: 'border-l-[#C24840]'
            };
            const ultimas = allAdminOrders.slice(0, 5);
            document.getElementById('resumen-ultimas-ordenes').innerHTML = ultimas.length ? ultimas.map(o => {
                const bc = stateColors[o.estado] || 'border-l-slate-200';
                const estadoLabel = o.estado.charAt(0).toUpperCase() + o.estado.slice(1);
                return `
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl text-xs border-l-4 ${bc} pl-3">
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold text-[#2E3C43]">#${o.id}</span>
                        <span class="text-slate-500 font-medium">Mesa ${o.mesa ? o.mesa.numero : 'S/N'}</span>
                        <span class="text-slate-300">·</span>
                        <span class="text-[#2879A8] font-bold">👤 ${o.user ? o.user.name : 'S/A'}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-emerald-700">$${parseFloat(o.total || 0).toFixed(2)}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold ${badgeClass(o.estado)}">${estadoLabel}</span>
                    </div>
                </div>
            `}).join('') : '<p class="text-slate-400 text-xs">Sin órdenes recientes.</p>';

            // Últimos pagos resumen
            const ultimosPagos = allPagos.slice(0, 5);
            document.getElementById('resumen-ultimos-pagos').innerHTML = ultimosPagos.length ? ultimosPagos.map(p => `
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl text-xs">
                    <div class="flex flex-col gap-0.5">
                        <span class="font-bold text-[#2E3C43]">Orden #${p.orden_id}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#2879A8]/10 text-[#2879A8] uppercase tracking-wide self-start">${p.metodo}</span>
                    </div>
                    <span class="font-extrabold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200">
                        +$${parseFloat(p.monto).toFixed(2)}
                    </span>
                </div>
            `).join('') : '<p class="text-slate-400 text-xs">Sin pagos registrados.</p>';
        }

        function badgeClass(estado) {
            switch(estado) {
                case 'pendiente': return 'bg-[#EBD140]/25 text-[#6c5806] border border-[#EBD140]/40';
                case 'preparando': return 'bg-[#2879A8]/15 text-[#2879A8] border border-[#2879A8]/30';
                case 'listo': return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
                case 'entregado': return 'bg-[#2E3C43]/15 text-[#2E3C43] border border-[#2E3C43]/20';
                case 'cerrada': return 'bg-slate-200 text-slate-800 border border-slate-300';
                case 'cancelada': return 'bg-[#C24840]/15 text-[#C24840] border border-[#C24840]/30';
                default: return 'bg-slate-100 text-slate-700';
            }
        }

        // PLATILLOS CRUD
        function renderAdminPlatillos() {
            const tbody = document.getElementById('tabla-platillos');
            if (!tbody) return;

            tbody.innerHTML = allPlatillos.map(p => `
                <tr class="tr-hover">
                    <td class="p-3.5">
                        <div class="flex items-center gap-3">
                            ${p.imagen ? `<img src="${p.imagen}" class="w-9 h-9 rounded-lg object-cover border" onerror="this.src=''; this.innerText='🍲'">` : `<div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center font-bold text-base">🍲</div>`}
                            <div>
                                <strong class="text-slate-900 block leading-tight">${p.nombre}</strong>
                                <span class="text-[11px] text-slate-400 block">${p.descripcion || 'Sin descripción'}</span>
                            </div>
                        </div>
                    </td>
                    <td class="p-3.5 font-medium text-slate-700">${p.categoria ? p.categoria.nombre : 'Sin categoría'}</td>
                    <td class="p-3.5 font-bold text-slate-900">$${parseFloat(p.precio).toFixed(2)}</td>
                    <td class="p-3.5">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold ${p.disponible ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'}">
                            ${p.disponible ? 'Disponible' : 'Agotado'}
                        </span>
                    </td>
                    <td class="p-3.5 text-right space-x-1">
                        <button onclick="editPlatillo(${p.id})" class="btn-action px-2.5 py-1 bg-[#2879A8]/10 hover:bg-[#2879A8]/20 text-[#2879A8] font-bold rounded-lg text-xs">Editar</button>
                        <button onclick="deletePlatillo(${p.id})" class="btn-action px-2.5 py-1 bg-red-50 hover:bg-red-100 text-[#C24840] font-bold rounded-lg text-xs">Eliminar</button>
                    </td>
                </tr>
            `).join('');

            // Llenar select del modal platillo
            const select = document.getElementById('platillo-categoria');
            if (select) {
                select.innerHTML = allCategorias.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');
            }
        }

        function openModalPlatillo(platillo = null) {
            document.getElementById('form-platillo').reset();
            document.getElementById('platillo-id').value = platillo ? platillo.id : '';
            document.getElementById('modal-platillo-titulo').innerText = platillo ? 'Editar Platillo' : 'Nuevo Platillo';

            if (platillo) {
                document.getElementById('platillo-nombre').value = platillo.nombre;
                document.getElementById('platillo-categoria').value = platillo.categoria_id;
                document.getElementById('platillo-precio').value = platillo.precio;
                document.getElementById('platillo-descripcion').value = platillo.descripcion || '';
                document.getElementById('platillo-imagen').value = platillo.imagen || '';
                document.getElementById('platillo-disponible').checked = !!platillo.disponible;
            } else {
                document.getElementById('platillo-disponible').checked = true;
            }

            document.getElementById('modal-platillo').classList.remove('hidden');
        }

        function closeModalPlatillo() {
            document.getElementById('modal-platillo').classList.add('hidden');
        }

        function editPlatillo(id) {
            const p = allPlatillos.find(x => x.id === id);
            if (p) openModalPlatillo(p);
        }

        async function savePlatillo(e) {
            e.preventDefault();
            const id = document.getElementById('platillo-id').value;
            const payload = {
                nombre: document.getElementById('platillo-nombre').value,
                categoria_id: parseInt(document.getElementById('platillo-categoria').value),
                precio: parseFloat(document.getElementById('platillo-precio').value),
                descripcion: document.getElementById('platillo-descripcion').value,
                imagen: document.getElementById('platillo-imagen').value || null,
                disponible: document.getElementById('platillo-disponible').checked
            };

            const url = id ? `/api/platillos/${id}` : '/api/platillos';
            const method = id ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method: method,
                    headers: apiHeaders(),
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.mensaje || 'Error al guardar platillo');

                closeModalPlatillo();
                showNotification(id ? 'Platillo actualizado correctamente' : 'Platillo registrado correctamente', 'success');
                await loadAdminData();
            } catch (err) {
                showNotification(err.message, 'error');
            }
        }

        async function deletePlatillo(id) {
            if (!confirm('¿Seguro que deseas eliminar este platillo?')) return;
            try {
                const res = await fetch(`/api/platillos/${id}`, { method: 'DELETE', headers: apiHeaders() });
                const data = await res.json();
                if (!res.ok) throw new Error(data.mensaje || 'Error al eliminar');
                showNotification('Platillo eliminado', 'success');
                await loadAdminData();
            } catch (err) {
                showNotification(err.message, 'error');
            }
        }

        // CATEGORÍAS CRUD
        function renderAdminCategorias() {
            const tbody = document.getElementById('tabla-categorias');
            if (!tbody) return;

            tbody.innerHTML = allCategorias.map(c => `
                <tr class="tr-hover">
                    <td class="p-3.5 font-bold text-slate-500">#${c.id}</td>
                    <td class="p-3.5 font-bold text-slate-900">${c.nombre}</td>
                    <td class="p-3.5 text-slate-500">${c.descripcion || 'Sin descripción'}</td>
                    <td class="p-3.5">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                            ${c.platillos ? c.platillos.length : 0} platillos
                        </span>
                    </td>
                    <td class="p-3.5">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold ${c.activo ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'}">
                            ${c.activo ? 'Activa' : 'Inactiva'}
                        </span>
                    </td>
                    <td class="p-3.5 text-right space-x-1">
                        <button onclick="editCategoria(${c.id})" class="btn-action px-2.5 py-1 bg-[#2879A8]/10 hover:bg-[#2879A8]/20 text-[#2879A8] font-bold rounded-lg text-xs">Editar</button>
                        <button onclick="deleteCategoria(${c.id})" class="btn-action px-2.5 py-1 bg-red-50 hover:bg-red-100 text-[#C24840] font-bold rounded-lg text-xs">Eliminar</button>
                    </td>
                </tr>
            `).join('');
        }

        function openModalCategoria(cat = null) {
            document.getElementById('form-categoria').reset();
            document.getElementById('categoria-id').value = cat ? cat.id : '';
            document.getElementById('modal-categoria-titulo').innerText = cat ? 'Editar Categoría' : 'Nueva Categoría';

            if (cat) {
                document.getElementById('categoria-nombre').value = cat.nombre;
                document.getElementById('categoria-descripcion').value = cat.descripcion || '';
                document.getElementById('categoria-activo').checked = !!cat.activo;
            } else {
                document.getElementById('categoria-activo').checked = true;
            }

            document.getElementById('modal-categoria').classList.remove('hidden');
        }

        function closeModalCategoria() {
            document.getElementById('modal-categoria').classList.add('hidden');
        }

        function editCategoria(id) {
            const cat = allCategorias.find(x => x.id === id);
            if (cat) openModalCategoria(cat);
        }

        async function saveCategoria(e) {
            e.preventDefault();
            const id = document.getElementById('categoria-id').value;
            const payload = {
                nombre: document.getElementById('categoria-nombre').value,
                descripcion: document.getElementById('categoria-descripcion').value,
                activo: document.getElementById('categoria-activo').checked
            };

            const url = id ? `/api/categorias/${id}` : '/api/categorias';
            const method = id ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method: method,
                    headers: apiHeaders(),
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.mensaje || 'Error al guardar categoría');

                closeModalCategoria();
                showNotification(id ? 'Categoría actualizada correctamente' : 'Categoría registrada correctamente', 'success');
                await loadAdminData();
            } catch (err) {
                showNotification(err.message, 'error');
            }
        }

        async function deleteCategoria(id) {
            if (!confirm('¿Seguro que deseas eliminar esta categoría?')) return;
            try {
                const res = await fetch(`/api/categorias/${id}`, { method: 'DELETE', headers: apiHeaders() });
                const data = await res.json();
                if (!res.ok) throw new Error(data.mensaje || 'Error al eliminar');
                showNotification('Categoría eliminada', 'success');
                await loadAdminData();
            } catch (err) {
                showNotification(err.message, 'error');
            }
        }

        // MESAS CRUD
        function renderAdminMesas() {
            const grid = document.getElementById('grid-mesas');
            if (!grid) return;

            grid.innerHTML = allMesas.map(m => {
                const topBorder = {
                    disponible: 'border-t-emerald-500',
                    ocupada: 'border-t-[#2879A8]',
                    reservada: 'border-t-[#EBD140]',
                    fuera_servicio: 'border-t-[#C24840]'
                }[m.estado] || 'border-t-slate-200';

                let statusBadge = {
                    disponible: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                    ocupada: 'bg-[#2879A8]/15 text-[#2879A8] border-[#2879A8]/30',
                    reservada: 'bg-[#EBD140]/25 text-[#6c5806] border-[#EBD140]/40',
                    fuera_servicio: 'bg-[#C24840]/15 text-[#C24840] border-[#C24840]/30'
                }[m.estado] || 'bg-slate-100 text-slate-700';

                return `
                    <div class="metric-card bg-white p-4 rounded-2xl border border-slate-200/80 border-t-4 ${topBorder} shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-2xl font-black text-[#2E3C43]">Mesa ${m.numero}</span>
                                <button onclick="deleteMesa(${m.id})" title="Eliminar mesa" class="text-slate-400 hover:text-[#C24840] hover:bg-red-50 rounded-lg p-1.5 transition-colors duration-150 text-sm font-bold leading-none">&times;</button>
                            </div>
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider border mb-3 ${statusBadge}">
                                ${m.estado.replace('_', ' ')}
                            </span>
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1 uppercase tracking-wide">Cambiar estado:</label>
                            <select onchange="updateMesaEstado(${m.id}, this.value)" class="w-full text-xs p-2 bg-slate-50 border border-slate-200 rounded-xl outline-none font-medium focus:border-[#2879A8]">
                                <option value="disponible" ${m.estado === 'disponible' ? 'selected' : ''}>Disponible</option>
                                <option value="ocupada" ${m.estado === 'ocupada' ? 'selected' : ''}>Ocupada</option>
                                <option value="reservada" ${m.estado === 'reservada' ? 'selected' : ''}>Reservada</option>
                                <option value="fuera_servicio" ${m.estado === 'fuera_servicio' ? 'selected' : ''}>Fuera de servicio</option>
                            </select>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function openModalMesa() {
            document.getElementById('form-mesa').reset();
            document.getElementById('modal-mesa').classList.remove('hidden');
        }

        function closeModalMesa() {
            document.getElementById('modal-mesa').classList.add('hidden');
        }

        async function saveMesa(e) {
            e.preventDefault();
            const payload = {
                numero: parseInt(document.getElementById('mesa-numero').value),
                estado: document.getElementById('mesa-estado').value
            };

            try {
                const res = await fetch('/api/mesas', {
                    method: 'POST',
                    headers: apiHeaders(),
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.mensaje || 'Error al registrar mesa');

                closeModalMesa();
                showNotification('Mesa registrada correctamente', 'success');
                await loadAdminData();
            } catch (err) {
                showNotification(err.message, 'error');
            }
        }

        async function updateMesaEstado(id, nuevoEstado) {
            try {
                const res = await fetch(`/api/mesas/${id}`, {
                    method: 'PUT',
                    headers: apiHeaders(),
                    body: JSON.stringify({ estado: nuevoEstado })
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.mensaje || 'Error al actualizar');
                showNotification('Estado de mesa actualizado', 'success');
                await loadAdminData();
            } catch (err) {
                showNotification(err.message, 'error');
            }
        }

        async function deleteMesa(id) {
            if (!confirm('¿Eliminar esta mesa?')) return;
            try {
                const res = await fetch(`/api/mesas/${id}`, { method: 'DELETE', headers: apiHeaders() });
                const data = await res.json();
                if (!res.ok) throw new Error(data.mensaje || 'Error al eliminar');
                showNotification('Mesa eliminada', 'success');
                await loadAdminData();
            } catch (err) {
                showNotification(err.message, 'error');
            }
        }

        // HISTORIAL DE ÓRDENES
        function renderAdminOrdenes() {
            const tbody = document.getElementById('tabla-admin-ordenes');
            const filtro = document.getElementById('filtro-historial-estado')?.value || 'todos';
            if (!tbody) return;

            let filtered = allAdminOrders;
            if (filtro !== 'todos') {
                filtered = allAdminOrders.filter(o => o.estado === filtro);
            }

            tbody.innerHTML = filtered.map(o => {
                const dateObj = new Date(o.created_at);
                const fecha = isNaN(dateObj) ? '' : `${dateObj.toLocaleDateString()} ${dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
                const estadoLabel = o.estado.charAt(0).toUpperCase() + o.estado.slice(1);

                return `
                    <tr class="tr-hover">
                        <td class="p-3.5 font-extrabold text-[#2E3C43]">#${o.id}</td>
                        <td class="p-3.5 font-bold text-[#2E3C43]">Mesa ${o.mesa ? o.mesa.numero : 'S/N'}</td>
                        <td class="p-3.5">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 font-bold text-xs border border-emerald-200">
                                👤 ${o.user ? o.user.name : 'Sin asignar'}
                            </span>
                        </td>
                        <td class="p-3.5 font-extrabold text-emerald-700">$${parseFloat(o.total || 0).toFixed(2)}</td>
                        <td class="p-3.5">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold border border-transparent ${badgeClass(o.estado)}">
                                ${estadoLabel}
                            </span>
                        </td>
                        <td class="p-3.5 text-slate-500">${fecha}</td>
                        <td class="p-3.5 text-right">
                            <button onclick="verDetalleOrden(${o.id})" class="btn-action px-3 py-1.5 bg-[#2879A8] hover:bg-[#20638a] text-white font-semibold rounded-lg text-xs shadow-sm">
                                Ver Detalle
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function verDetalleOrden(id) {
            const orden = allAdminOrders.find(o => o.id === id);
            if (!orden) return;

            document.getElementById('modal-orden-titulo').innerText = `Comanda #${orden.id} - Mesa ${orden.mesa ? orden.mesa.numero : 'S/N'}`;
            document.getElementById('modal-orden-info').innerHTML = `
                <div class="flex items-center gap-1.5">
                    <span class="text-slate-500">Mesero que atendió:</span>
                    <strong class="bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded-lg border border-emerald-200 font-bold text-xs">👤 ${orden.user ? orden.user.name : 'Sin asignar'}</strong>
                </div>
                <div>
                    <span class="text-slate-500">Estado:</span> <span class="px-2 py-0.5 rounded-full text-xs font-bold ${badgeClass(orden.estado)}">${orden.estado.charAt(0).toUpperCase() + orden.estado.slice(1)}</span>
                </div>
            `;

            document.getElementById('modal-orden-detalles-body').innerHTML = (orden.detalles || []).map(d => `
                <tr>
                    <td class="p-2.5 font-bold text-slate-900">${d.cantidad}x</td>
                    <td class="p-2.5">
                        <strong class="text-slate-900 block">${d.platillo ? d.platillo.nombre : 'Platillo'}</strong>
                        ${d.notas ? `<span class="text-[11px] text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">Nota: ${d.notas}</span>` : ''}
                    </td>
                    <td class="p-2.5 text-slate-600">$${parseFloat(d.precio).toFixed(2)}</td>
                    <td class="p-2.5 text-right font-bold text-slate-900">$${(d.cantidad * d.precio).toFixed(2)}</td>
                </tr>
            `).join('');

            document.getElementById('modal-orden-total').innerText = `Total: $${parseFloat(orden.total || 0).toFixed(2)}`;
            document.getElementById('modal-orden-detalle').classList.remove('hidden');
        }

        function closeModalOrdenDetalle() {
            document.getElementById('modal-orden-detalle').classList.add('hidden');
        }

        // PAGOS REGISTRADOS
        function renderAdminPagos() {
            const tbody = document.getElementById('tabla-pagos');
            if (!tbody) return;

            const total = allPagos.reduce((sum, p) => sum + parseFloat(p.monto || 0), 0);
            document.getElementById('pagos-total-banner').innerText = `Total Recaudado: $${total.toFixed(2)}`;

            tbody.innerHTML = allPagos.map(p => {
                const dateObj = new Date(p.created_at);
                const fecha = isNaN(dateObj) ? '' : `${dateObj.toLocaleDateString()} ${dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
                const mesaNum = p.orden && p.orden.mesa ? p.orden.mesa.numero : 'S/N';

                return `
                    <tr class="tr-hover">
                        <td class="p-3.5 font-bold text-slate-400">#${p.id}</td>
                        <td class="p-3.5 font-bold text-[#2E3C43]">Orden #${p.orden_id}</td>
                        <td class="p-3.5 text-slate-700 font-medium">Mesa ${mesaNum}</td>
                        <td class="p-3.5">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide bg-[#2879A8]/10 text-[#2879A8]">
                                ${p.metodo}
                            </span>
                        </td>
                        <td class="p-3.5 font-extrabold text-emerald-700">$${parseFloat(p.monto).toFixed(2)}</td>
                        <td class="p-3.5">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                ${p.estado}
                            </span>
                        </td>
                        <td class="p-3.5 text-slate-500">${fecha}</td>
                    </tr>
                `;
            }).join('');
        }

        // ================= GESTIÓN DE USUARIOS Y MESEROS (CRUD) =================
        function renderAdminUsuarios() {
            const tbody = document.getElementById('tabla-usuarios');
            if (!tbody) return;

            let loggedUserId = null;
            try {
                const uStr = localStorage.getItem('user');
                if (uStr) loggedUserId = JSON.parse(uStr).id;
            } catch(e) {}

            tbody.innerHTML = allUsuarios.map(u => {
                const dateObj = new Date(u.created_at);
                const fecha = isNaN(dateObj) ? '' : `${dateObj.toLocaleDateString()}`;
                const rolNombre = u.role ? u.role.nombre : (u.role_id === 1 ? 'Administrador' : (u.role_id === 2 ? 'Mesero' : (u.role_id === 3 ? 'Cocina' : 'Usuario')));
                
                let badgeRol = 'bg-slate-100 text-slate-700 border-slate-200';
                if (rolNombre === 'Administrador') {
                    badgeRol = 'bg-[#C24840]/10 text-[#C24840] border-[#C24840]/30';
                } else if (rolNombre === 'Mesero') {
                    badgeRol = 'bg-[#2879A8]/10 text-[#2879A8] border-[#2879A8]/30';
                } else if (rolNombre === 'Cocina') {
                    badgeRol = 'bg-[#EBD140]/25 text-[#6c5806] border-[#EBD140]/40';
                }

                const initial = (u.name || 'U').charAt(0).toUpperCase();
                const isSelf = loggedUserId && loggedUserId === u.id;

                return `
                    <tr class="tr-hover">
                        <td class="p-3.5 font-bold text-slate-400">#${u.id}</td>
                        <td class="p-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-[#2E3C43] text-white flex items-center justify-center font-bold text-xs shadow-sm shrink-0">
                                    ${initial}
                                </div>
                                <strong class="text-slate-900 block leading-tight font-bold">${u.name}</strong>
                            </div>
                        </td>
                        <td class="p-3.5 font-medium text-slate-700">${u.email}</td>
                        <td class="p-3.5">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold border ${badgeRol}">
                                ${rolNombre}
                            </span>
                        </td>
                        <td class="p-3.5">
                            ${rolNombre === 'Mesero' ? `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 text-[11px] font-bold border border-emerald-200">📱 App Móvil</span>` : `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[11px] font-medium border border-slate-200">💻 Panel Web</span>`}
                        </td>
                        <td class="p-3.5 text-slate-500 text-xs">${fecha}</td>
                        <td class="p-3.5 text-right space-x-1">
                            <button onclick="editUsuario(${u.id})" class="btn-action px-2.5 py-1 bg-[#2879A8]/10 hover:bg-[#2879A8]/20 text-[#2879A8] font-bold rounded-lg text-xs">Editar</button>
                            ${isSelf ? '' : `<button onclick="deleteUsuario(${u.id})" class="btn-action px-2.5 py-1 bg-red-50 hover:bg-red-100 text-[#C24840] font-bold rounded-lg text-xs">Eliminar</button>`}
                        </td>
                    </tr>
                `;
            }).join('');

            // Llenar select del modal usuario con los roles disponibles
            const selectRol = document.getElementById('usuario-rol');
            if (selectRol && allRoles && allRoles.length) {
                const currentVal = selectRol.value;
                selectRol.innerHTML = allRoles.map(r => `<option value="${r.id}">${r.nombre}</option>`).join('');
                if (currentVal) selectRol.value = currentVal;
            }
        }

        function openModalUsuario(usuario = null) {
            document.getElementById('form-usuario').reset();
            document.getElementById('usuario-id').value = usuario ? usuario.id : '';
            document.getElementById('modal-usuario-titulo').innerText = usuario ? 'Editar Usuario / Empleado' : 'Nuevo Usuario / Empleado';
            
            const passInput = document.getElementById('usuario-password');
            const passHelp = document.getElementById('help-usuario-password');

            if (usuario) {
                document.getElementById('usuario-nombre').value = usuario.name || '';
                document.getElementById('usuario-email').value = usuario.email || '';
                if (usuario.role_id) document.getElementById('usuario-rol').value = usuario.role_id;
                passInput.required = false;
                passHelp.innerText = 'Dejar vacío si no deseas cambiar la contraseña actual.';
            } else {
                passInput.required = true;
                passHelp.innerText = 'Mínimo 6 caracteres. Con esta clave iniciará sesión en la app móvil o el panel web.';
                // Predeterminar al rol Mesero si existe
                const selectRol = document.getElementById('usuario-rol');
                if (selectRol) {
                    const meseroOption = Array.from(selectRol.options).find(o => o.text.toLowerCase().includes('mesero'));
                    if (meseroOption) selectRol.value = meseroOption.value;
                }
            }

            document.getElementById('modal-usuario').classList.remove('hidden');
        }

        function closeModalUsuario() {
            document.getElementById('modal-usuario').classList.add('hidden');
        }

        function editUsuario(id) {
            const u = allUsuarios.find(x => x.id === id);
            if (u) openModalUsuario(u);
        }

        async function saveUsuario(e) {
            e.preventDefault();
            const id = document.getElementById('usuario-id').value;
            const name = document.getElementById('usuario-nombre').value.trim();
            const email = document.getElementById('usuario-email').value.trim();
            const role_id = document.getElementById('usuario-rol').value;
            const password = document.getElementById('usuario-password').value;

            const payload = { name, email, role_id };
            if (password) payload.password = password;

            try {
                const url = id ? `/api/usuarios/${id}` : '/api/usuarios';
                const method = id ? 'PUT' : 'POST';
                const res = await fetch(url, {
                    method: method,
                    headers: apiHeaders(),
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.mensaje || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Error al guardar usuario'));

                closeModalUsuario();
                showNotification(id ? 'Empleado actualizado correctamente' : 'Empleado registrado. Ya puede iniciar sesión en la app', 'success');
                await loadAdminData();
            } catch (err) {
                showNotification(err.message, 'error');
            }
        }

        async function deleteUsuario(id) {
            if (!confirm('¿Estás seguro de eliminar este usuario/empleado?')) return;
            try {
                const res = await fetch(`/api/usuarios/${id}`, {
                    method: 'DELETE',
                    headers: apiHeaders()
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.mensaje || 'Error al eliminar usuario');
                showNotification('Usuario eliminado correctamente', 'success');
                await loadAdminData();
            } catch (err) {
                showNotification(err.message, 'error');
            }
        }
    </script>
</body>
</html>
