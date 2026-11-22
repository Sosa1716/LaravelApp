<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Restaurante - Cocina & Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        :root {
            --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
            --ease-ui: cubic-bezier(0.16, 1, 0.3, 1);
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition-duration: 0.01ms !important; animation-duration: 0.01ms !important; }
        }

        /* ── Tabs de administración ── */
        .admin-tab-btn { border-bottom: 2px solid transparent; }
        .admin-tab-btn.tab-active {
            color: #fff !important;
            border-bottom-color: #60a5fa !important;
        }

        /* ── Cards de cocina con acento lateral ── */
        .card-pendiente {
            border-left: 4px solid #f59e0b;
            background: linear-gradient(to right, rgba(254,243,199,.22) 0%, transparent 90px);
        }
        .card-preparando {
            border-left: 4px solid #3b82f6;
            background: linear-gradient(to right, rgba(219,234,254,.22) 0%, transparent 90px);
        }
        .card-listo {
            border-left: 4px solid #10b981;
            background: linear-gradient(to right, rgba(209,250,229,.22) 0%, transparent 90px);
        }

        /* ── Tarjetas métricas con hover suave ── */
        .metric-card {
            transition: box-shadow 200ms var(--ease-ui), transform 150ms var(--ease-ui);
        }
        .metric-card:hover {
            box-shadow: 0 6px 20px 0 rgba(15,23,42,.11);
            transform: translateY(-2px);
        }

        /* ── Botones con feedback táctil ── */
        .btn-action {
            transition: background-color 150ms var(--ease-ui), transform 100ms var(--ease-ui), box-shadow 150ms var(--ease-ui);
        }
        .btn-action:active { transform: scale(0.97); }

        /* ── Filas de tabla con hover suave ── */
        .tr-hover { transition: background-color 120ms var(--ease-ui); }
        .tr-hover:hover { background-color: rgb(248 250 252); }

        /* ── Filtros de cocina ── */
        .filter-btn {
            transition: background-color 150ms var(--ease-ui), color 150ms var(--ease-ui), transform 100ms var(--ease-ui);
        }
        .filter-btn:active { transform: scale(0.97); }

        /* ── Foco visible global ── */
        :focus-visible { outline: 2px solid #3b82f6; outline-offset: 2px; border-radius: 6px; }

        /* ── Separador de header admin ── */
        .tab-bar-separator { border-top: 1px solid rgba(51,65,85,.8); }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen antialiased">

    <!-- ================= VISTA DE LOGIN ================= -->
    <div id="view-login" class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-200/80 p-8">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-500 text-white text-2xl shadow-md mb-3">
                    🍽️
                </div>
                <h1 class="text-2xl font-bold text-slate-900">El Pingüin</h1>
                <p class="text-sm text-slate-500 mt-1">Inicia sesión con tu rol de Cocina o Administración</p>
            </div>

            <form id="form-login" class="space-y-4" onsubmit="handleLogin(event)">
                <div id="login-error" class="hidden p-3 bg-red-50 text-red-700 text-sm rounded-xl border border-red-200"></div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Correo Electrónico</label>
                    <input type="email" id="login-email" required placeholder="cocina@restaurante.com"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Contraseña</label>
                    <input type="password" id="login-password" required placeholder="••••••••"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition text-sm">
                </div>

                <button type="submit" id="btn-login-submit"
                    class="w-full py-3 px-4 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl shadow transition duration-150 flex items-center justify-center gap-2">
                    <span>Ingresar</span>
                </button>
            </form>


        </div>
    </div>


    <!-- ================= VISTA DE COCINA ================= -->
    <div id="view-cocina" class="hidden min-h-screen flex flex-col bg-slate-100">
        <!-- Barra Superior Cocina -->
        <header class="bg-slate-900 text-white border-b border-slate-800 sticky top-0 z-30 shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-500 flex items-center justify-center text-xl shadow-sm shrink-0">🍳</div>
                    <div>
                        <h1 class="text-base font-bold leading-tight text-white">
                            Módulo de Cocina
                            <span class="ml-1.5 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 align-middle">En vivo</span>
                        </h1>
                        <p class="text-[11px] text-slate-400 leading-none mt-0.5">Comandas en tiempo real · <span id="cocina-sync-text" class="text-emerald-400">Sincronizado</span></p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="hidden sm:flex items-center gap-1.5 text-[11px] text-slate-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        API móvil
                    </span>

                    <button onclick="fetchOrdenes()" title="Actualizar órdenes"
                        class="btn-action px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium rounded-lg border border-slate-700 flex items-center gap-1.5">
                        <span id="sync-icon">🔄</span> Actualizar
                    </button>

                    <div class="h-5 w-px bg-slate-700 hidden sm:block"></div>

                    <button id="btn-return-admin" onclick="showView('admin')"
                        class="hidden btn-action px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-lg">
                        ⚙️ Admin
                    </button>

                    <span class="text-xs text-slate-300 font-medium px-2.5 py-1 bg-slate-800 rounded-lg border border-slate-700" id="user-display-cocina">
                        Cocina
                    </span>

                    <button onclick="handleLogout()"
                        class="btn-action px-3 py-1.5 bg-red-700/80 hover:bg-red-600 text-white text-xs font-medium rounded-lg">
                        Salir
                    </button>
                </div>
            </div>
        </header>

        <!-- Contenido Cocina -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 py-5 flex-1 w-full">

            <!-- Aviso de modo cocina compacto -->
            <div class="mb-5 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 border border-amber-200 rounded-full text-[11px] font-semibold text-amber-800">
                    🍳 Modo Cocina — solo preparación
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 border border-slate-200 rounded-full text-[11px] font-medium text-slate-500">
                    🔒 Administración restringida
                </span>
            </div>

            <!-- Filtros Cocina -->
            <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-1" id="cocina-filters">
                <button onclick="setFilter('todas')" data-filter="todas"
                    class="filter-btn active-filter px-4 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 bg-slate-900 text-white shadow-sm whitespace-nowrap">
                    <span>📋 Todas</span>
                    <span id="badge-count-todas" class="px-1.5 py-0.5 rounded-full text-[10px] bg-white/20 font-bold">0</span>
                </button>
                <button onclick="setFilter('pendiente')" data-filter="pendiente"
                    class="filter-btn px-4 py-2.5 rounded-xl text-xs font-semibold flex items-center gap-2 bg-white text-slate-700 hover:bg-amber-50 border border-slate-200 whitespace-nowrap">
                    <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
                    <span>Pendientes</span>
                    <span id="badge-count-pendiente" class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-800 font-bold">0</span>
                </button>
                <button onclick="setFilter('preparando')" data-filter="preparando"
                    class="filter-btn px-4 py-2.5 rounded-xl text-xs font-semibold flex items-center gap-2 bg-white text-slate-700 hover:bg-blue-50 border border-slate-200 whitespace-nowrap">
                    <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                    <span>En Preparación</span>
                    <span id="badge-count-preparando" class="px-1.5 py-0.5 rounded-full text-[10px] bg-blue-100 text-blue-800 font-bold">0</span>
                </button>
                <button onclick="setFilter('listo')" data-filter="listo"
                    class="filter-btn px-4 py-2.5 rounded-xl text-xs font-semibold flex items-center gap-2 bg-white text-slate-700 hover:bg-emerald-50 border border-slate-200 whitespace-nowrap">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                    <span>Listas ✓</span>
                    <span id="badge-count-listo" class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 font-bold">0</span>
                </button>
            </div>

            <!-- Grilla de Órdenes Cocina -->
            <div id="ordenes-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"></div>

            <div id="ordenes-empty" class="hidden text-center py-20 bg-white rounded-2xl border border-dashed border-slate-200 p-8">
                <span class="text-5xl block mb-3">✨</span>
                <h3 class="text-lg font-bold text-slate-800">¡Todo al día en cocina!</h3>
                <p class="text-sm text-slate-500 mt-1 max-w-xs mx-auto">No hay comandas activas en este momento.</p>
            </div>
        </main>
    </div>


    <!-- ================= VISTA DE ADMINISTRACIÓN ================= -->
    <div id="view-admin" class="hidden min-h-screen flex flex-col bg-slate-100">
        <!-- Barra Superior Admin -->
        <header class="bg-slate-900 text-white border-b border-slate-800 sticky top-0 z-30 shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-xl shadow-sm shrink-0">⚙️</div>
                    <div>
                        <h1 class="text-base font-bold leading-tight text-white">
                            Panel de Administración
                            <span class="ml-1.5 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/30 align-middle">Admin</span>
                        </h1>
                        <p class="text-[11px] text-slate-400 leading-none mt-0.5">Menú · Mesas · Órdenes · Finanzas</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button onclick="showView('cocina'); fetchOrdenes();"
                        class="btn-action px-3 py-1.5 bg-amber-500/15 hover:bg-amber-500/25 text-amber-300 text-xs font-semibold rounded-lg border border-amber-500/30 flex items-center gap-1.5">
                        🍳 Cocina
                    </button>
                    <button onclick="loadAdminData()"
                        class="btn-action px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium rounded-lg border border-slate-700">
                        🔄 Recargar
                    </button>
                    <span class="text-xs text-slate-300 font-medium px-2.5 py-1 bg-slate-800 rounded-lg border border-slate-700" id="user-display-admin">
                        Administrador
                    </span>
                    <button onclick="handleLogout()"
                        class="btn-action px-3 py-1.5 bg-red-700/80 hover:bg-red-600 text-white text-xs font-medium rounded-lg">
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
                    👥 Usuarios
                    <span class="text-[9px] bg-amber-500/20 text-amber-400 px-1.5 py-0.5 rounded border border-amber-400/30">Pronto</span>
                </button>
                <button onclick="switchAdminTab('reportes')" id="tab-btn-reportes"
                    class="admin-tab-btn px-4 py-2.5 text-slate-400 hover:text-white transition-colors duration-150 whitespace-nowrap flex items-center gap-1.5">
                    📈 Reportes
                    <span class="text-[9px] bg-amber-500/20 text-amber-400 px-1.5 py-0.5 rounded border border-amber-400/30">Pronto</span>
                </button>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 flex-1 w-full">

            <!-- 1. TAB: RESUMEN GENERAL -->
            <div id="tab-content-resumen" class="admin-tab-content space-y-6">
                <!-- Tarjetas Métricas Clave — ventas destacada con borde superior -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="metric-card col-span-2 lg:col-span-1 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm border-t-4 border-t-emerald-500">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Recaudado</span>
                            <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 text-base">💰</span>
                        </div>
                        <div class="text-3xl font-black text-slate-900 leading-none" id="metric-ventas">$0.00</div>
                        <p class="text-xs text-slate-500 mt-2" id="metric-pagos-count">0 transacciones</p>
                    </div>

                    <div class="metric-card bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Órdenes</span>
                            <span class="p-2 rounded-xl bg-blue-50 text-blue-600 text-base">📋</span>
                        </div>
                        <div class="text-2xl font-extrabold text-slate-900 leading-none" id="metric-ordenes">0</div>
                        <p class="text-xs text-slate-500 mt-2" id="metric-ordenes-sub">0 activas / 0 cerradas</p>
                    </div>

                    <div class="metric-card bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Mesas</span>
                            <span class="p-2 rounded-xl bg-purple-50 text-purple-600 text-base">🪑</span>
                        </div>
                        <div class="text-2xl font-extrabold text-slate-900 leading-none" id="metric-mesas">0</div>
                        <p class="text-xs text-slate-500 mt-2" id="metric-mesas-sub">0 disponibles</p>
                    </div>

                    <div class="metric-card bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Platillos</span>
                            <span class="p-2 rounded-xl bg-amber-50 text-amber-600 text-base">🍲</span>
                        </div>
                        <div class="text-2xl font-extrabold text-slate-900 leading-none" id="metric-platillos">0</div>
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

            <!-- 7. TAB: USUARIOS (PENDIENTE DE BACKEND) -->
            <div id="tab-content-usuarios" class="admin-tab-content hidden space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl shrink-0">
                            👥
                        </div>
                        <div>
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300 mb-2">
                                Módulo Pendiente de Backend
                            </span>
                            <h2 class="text-lg font-bold text-slate-900">Gestión de Usuarios y Empleados</h2>
                            <p class="text-sm text-slate-600 mt-1 leading-relaxed max-w-2xl">
                                Este módulo forma parte del alcance funcional original del sistema. Actualmente se encuentra pendiente de la implementación de los endpoints REST en el backend de Laravel (<code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded text-slate-800">/api/usuarios</code>).
                            </p>
                            <p class="text-xs text-slate-500 mt-2">
                                🔒 Siguiendo la regla de arquitectura, la interfaz web no inventa endpoints sin coordinarlos previamente con el desarrollador de backend.
                            </p>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-100">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Usuarios sembrados en el sistema actual:</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                                <span class="font-bold text-slate-800">Administrador</span>
                                <p class="text-slate-500 mt-0.5">admin@restaurante.com</p>
                                <span class="inline-block mt-2 px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-blue-800">Rol: Administrador</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                                <span class="font-bold text-slate-800">Cocina</span>
                                <p class="text-slate-500 mt-0.5">cocina@restaurante.com</p>
                                <span class="inline-block mt-2 px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800">Rol: Cocina</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                                <span class="font-bold text-slate-800">Mesero</span>
                                <p class="text-slate-500 mt-0.5">mesero@restaurante.com</p>
                                <span class="inline-block mt-2 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">Rol: Mesero</span>
                            </div>
                        </div>
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
                    <button type="submit" class="btn-action px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-semibold shadow">Guardar Platillo</button>
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
                    <input type="text" id="categoria-nombre" required class="w-full px-3 py-2 border rounded-xl outline-none focus:border-amber-500 text-xs">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Descripción</label>
                    <textarea id="categoria-descripcion" rows="2" class="w-full px-3 py-2 border rounded-xl outline-none focus:border-amber-500 text-xs"></textarea>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="categoria-activo" checked class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500">
                    <label for="categoria-activo" class="font-semibold text-slate-700">Categoría Activa</label>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModalCategoria()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl font-semibold text-slate-700 transition">Cancelar</button>
                    <button type="submit" class="btn-action px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-semibold shadow">Guardar Categoría</button>
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
                    <input type="number" id="mesa-numero" min="1" required class="w-full px-3 py-2 border rounded-xl outline-none focus:border-amber-500 text-xs">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Estado Inicial *</label>
                    <select id="mesa-estado" required class="w-full px-3 py-2 border rounded-xl outline-none focus:border-amber-500 text-xs">
                        <option value="disponible">Disponible (Libre)</option>
                        <option value="ocupada">Ocupada</option>
                        <option value="reservada">Reservada</option>
                        <option value="fuera_servicio">Fuera de Servicio</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModalMesa()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl font-semibold text-slate-700 transition">Cancelar</button>
                    <button type="submit" class="btn-action px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-semibold shadow">Guardar Mesa</button>
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


    <!-- Contenedor flotante de notificaciones / toasts -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <!-- ================= JAVASCRIPT ================= -->
    <script>
        // ESTADO GLOBAL
        let currentFilter = 'todas';
        let allOrders = [];
        let allAdminOrders = [];
        let allPlatillos = [];
        let allCategorias = [];
        let allMesas = [];
        let allPagos = [];
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

        function showNotification(msg) {
            const container = document.getElementById('toast-container');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = 'pointer-events-auto bg-slate-900 text-white text-xs font-semibold px-4 py-3 rounded-xl shadow-2xl border border-slate-700 flex items-center gap-2 transform transition-all duration-300 translate-y-2 scale-95 opacity-0';
            toast.innerHTML = `<span>🔔</span> <span>${msg}</span>`;
            container.appendChild(toast);
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-2', 'scale-95', 'opacity-0');
            });
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2', 'scale-95');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
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
                }
            }, intervalMs);
        }

        function stopPolling() {
            if (pollingInterval) clearInterval(pollingInterval);
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

                // Si llegaron comandas nuevas desde la app móvil, emitir aviso sonoro y visual
                if (isSilent && active.length > lastOrdersCount && lastOrdersCount > 0) {
                    playChime();
                    showNotification('¡Nueva comanda registrada desde la app móvil!');
                }
                lastOrdersCount = active.length;
                allOrders = active;

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

        async function updateEstado(ordenId, nuevoEstado) {
            try {
                const res = await fetch(`/api/ordenes/${ordenId}/estado`, {
                    method: 'PATCH',
                    headers: apiHeaders(),
                    body: JSON.stringify({ estado: nuevoEstado })
                });

                if (!res.ok) {
                    const data = await res.json();
                    alert(data.mensaje || 'Error al actualizar estado');
                    return;
                }
                await fetchOrdenes();
            } catch (err) {
                alert('Error de conexión');
            }
        }

        function setFilter(filter) {
            currentFilter = filter;
            document.querySelectorAll('.filter-btn').forEach(btn => {
                const isActive = btn.dataset.filter === filter;
                btn.classList.toggle('bg-slate-900', isActive);
                btn.classList.toggle('text-white', isActive);
                btn.classList.toggle('bg-white', !isActive);
                btn.classList.toggle('text-slate-700', !isActive);
            });
            renderOrders();
        }

        function updateCounters() {
            const countPendiente = allOrders.filter(o => o.estado === 'pendiente').length;
            const countPreparando = allOrders.filter(o => o.estado === 'preparando').length;
            const countListo = allOrders.filter(o => o.estado === 'listo').length;

            document.getElementById('badge-count-todas').innerText = allOrders.length;
            document.getElementById('badge-count-pendiente').innerText = countPendiente;
            document.getElementById('badge-count-preparando').innerText = countPreparando;
            document.getElementById('badge-count-listo').innerText = countListo;
        }

        function renderOrders() {
            const grid = document.getElementById('ordenes-grid');
            const empty = document.getElementById('ordenes-empty');

            let filtered = currentFilter === 'todas' ? allOrders : allOrders.filter(o => o.estado === currentFilter);

            if (filtered.length === 0) {
                grid.innerHTML = '';
                empty.classList.remove('hidden');
                return;
            }

            empty.classList.add('hidden');

            grid.innerHTML = filtered.map(orden => {
                let cardClass, badgeBg, badgeText, badgeIcon, btnAction;

                const elapsed = Math.round((Date.now() - new Date(orden.created_at)) / 60000);
                const elapsedStr = isNaN(elapsed) ? '' : `⏱ ${elapsed}m`;

                if (orden.estado === 'preparando') {
                    cardClass  = 'card-preparando';
                    badgeBg    = 'bg-blue-100 text-blue-900 border-blue-300';
                    badgeText  = 'En Preparación';
                    badgeIcon  = '🔥';
                    btnAction  = `<button onclick="updateEstado(${orden.id}, 'listo')" class="btn-action w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm flex items-center justify-center gap-2 shadow">✓ Marcar como Listo</button>`;
                } else if (orden.estado === 'listo') {
                    cardClass  = 'card-listo';
                    badgeBg    = 'bg-emerald-100 text-emerald-900 border-emerald-300';
                    badgeText  = 'Listo para Servir';
                    badgeIcon  = '✅';
                    btnAction  = `<div class="w-full py-3 px-4 bg-emerald-50 text-emerald-800 text-sm font-semibold rounded-xl text-center border-2 border-emerald-300">✅ Listo para servir</div>`;
                } else {
                    cardClass  = 'card-pendiente';
                    badgeBg    = 'bg-amber-100 text-amber-900 border-amber-300';
                    badgeText  = 'Pendiente';
                    badgeIcon  = '⏳';
                    btnAction  = `<button onclick="updateEstado(${orden.id}, 'preparando')" class="btn-action w-full py-3.5 px-4 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-sm flex items-center justify-center gap-2 shadow">🍳 Iniciar Preparación</button>`;
                }

                const dateObj = new Date(orden.created_at);
                const timeStr = isNaN(dateObj) ? '' : dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                const platillosHtml = (orden.detalles || []).map(d => `
                    <li class="py-2.5 border-b border-slate-100 last:border-none">
                        <div class="flex items-start gap-2.5">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-900 text-white text-sm font-bold shrink-0">
                                ${d.cantidad}x
                            </span>
                            <span class="font-bold text-slate-900 text-sm leading-snug pt-1">
                                ${d.platillo ? d.platillo.nombre : 'Platillo'}
                            </span>
                        </div>
                        ${d.notas ? `
                            <div class="mt-1.5 ml-10 px-2.5 py-1.5 bg-amber-50 border border-amber-200 text-amber-900 text-xs rounded-lg flex items-start gap-1.5">
                                <span class="shrink-0 text-amber-700 font-bold">Nota:</span>
                                <span class="font-medium">${d.notas}</span>
                            </div>
                        ` : ''}
                    </li>
                `).join('');

                return `
                    <div class="${cardClass} rounded-2xl shadow-sm overflow-hidden flex flex-col justify-between">
                        <div>
                            <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl font-black text-slate-900">#${orden.id}</span>
                                    <span class="px-2.5 py-0.5 rounded-lg bg-slate-800 text-white text-xs font-bold">
                                        Mesa ${orden.mesa ? orden.mesa.numero : 'S/N'}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    ${elapsedStr ? `<span class="text-xs text-slate-500 font-medium">${elapsedStr}</span>` : ''}
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border ${badgeBg}">
                                        <span>${badgeIcon}</span>
                                        <span>${badgeText}</span>
                                    </span>
                                </div>
                            </div>
                            <div class="px-4 py-2 bg-slate-100/50 flex items-center justify-between text-[11px] text-slate-500 border-b border-slate-100">
                                <span>Mesero: <strong class="text-slate-700">${orden.user ? orden.user.name : 'N/A'}</strong></span>
                                <span>Hora: <strong class="text-slate-700">${timeStr}</strong></span>
                            </div>
                            <div class="p-4">
                                <ul class="divide-y divide-slate-100">${platillosHtml}</ul>
                            </div>
                        </div>
                        <div class="p-4 bg-slate-50/60 border-t border-slate-100">
                            ${btnAction}
                        </div>
                    </div>
                `;
            }).join('');
        }

        // ================= SECCIÓN ADMINISTRACIÓN =================
        async function loadAdminData(isSilent = false) {
            try {
                const [rPlatillos, rCategorias, rMesas, rOrdenes, rPagos] = await Promise.all([
                    fetch('/api/platillos', { headers: apiHeaders() }),
                    fetch('/api/categorias', { headers: apiHeaders() }),
                    fetch('/api/mesas', { headers: apiHeaders() }),
                    fetch('/api/ordenes', { headers: apiHeaders() }),
                    fetch('/api/pagos', { headers: apiHeaders() })
                ]);

                if (rPlatillos.ok) allPlatillos = await rPlatillos.json();
                if (rCategorias.ok) allCategorias = await rCategorias.json();
                if (rMesas.ok) allMesas = await rMesas.json();
                if (rOrdenes.ok) allAdminOrders = await rOrdenes.json();
                if (rPagos.ok) allPagos = await rPagos.json();

                renderAdminResumen();
                renderAdminPlatillos();
                renderAdminCategorias();
                renderAdminMesas();
                renderAdminOrdenes();
                renderAdminPagos();
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

            // Últimas órdenes resumen
            const stateColors = {
                pendiente: 'border-l-amber-400',
                preparando: 'border-l-blue-400',
                listo: 'border-l-emerald-500',
                entregado: 'border-l-purple-400',
                cerrada: 'border-l-slate-300',
                cancelada: 'border-l-red-400'
            };
            const ultimas = allAdminOrders.slice(0, 5);
            document.getElementById('resumen-ultimas-ordenes').innerHTML = ultimas.length ? ultimas.map(o => {
                const bc = stateColors[o.estado] || 'border-l-slate-200';
                const estadoLabel = o.estado.charAt(0).toUpperCase() + o.estado.slice(1);
                return `
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl text-xs border-l-4 ${bc} pl-3">
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold text-slate-900">#${o.id}</span>
                        <span class="text-slate-500">Mesa ${o.mesa ? o.mesa.numero : 'S/N'}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-emerald-700">$${parseFloat(o.total || 0).toFixed(2)}</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold ${badgeClass(o.estado)}">${estadoLabel}</span>
                    </div>
                </div>
            `}).join('') : '<p class="text-slate-400 text-xs">Sin órdenes recientes.</p>';

            // Últimos pagos resumen
            const ultimosPagos = allPagos.slice(0, 5);
            document.getElementById('resumen-ultimos-pagos').innerHTML = ultimosPagos.length ? ultimosPagos.map(p => `
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl text-xs">
                    <div class="flex flex-col gap-0.5">
                        <span class="font-bold text-slate-800">Orden #${p.orden_id}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 uppercase tracking-wide self-start">${p.metodo}</span>
                    </div>
                    <span class="font-extrabold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200">
                        +$${parseFloat(p.monto).toFixed(2)}
                    </span>
                </div>
            `).join('') : '<p class="text-slate-400 text-xs">Sin pagos registrados.</p>';
        }

        function badgeClass(estado) {
            switch(estado) {
                case 'pendiente': return 'bg-amber-100 text-amber-800';
                case 'preparando': return 'bg-blue-100 text-blue-800';
                case 'listo': return 'bg-emerald-100 text-emerald-800';
                case 'entregado': return 'bg-purple-100 text-purple-800';
                case 'cerrada': return 'bg-slate-200 text-slate-800';
                case 'cancelada': return 'bg-red-100 text-red-800';
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
                        <button onclick="editPlatillo(${p.id})" class="btn-action px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs">Editar</button>
                        <button onclick="deletePlatillo(${p.id})" class="btn-action px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-700 font-semibold rounded-lg text-xs">Eliminar</button>
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
                await loadAdminData();
            } catch (err) {
                alert(err.message);
            }
        }

        async function deletePlatillo(id) {
            if (!confirm('¿Seguro que deseas eliminar este platillo?')) return;
            try {
                const res = await fetch(`/api/platillos/${id}`, { method: 'DELETE', headers: apiHeaders() });
                const data = await res.json();
                if (!res.ok) throw new Error(data.mensaje || 'Error al eliminar');
                await loadAdminData();
            } catch (err) {
                alert(err.message);
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
                        <button onclick="editCategoria(${c.id})" class="btn-action px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs">Editar</button>
                        <button onclick="deleteCategoria(${c.id})" class="btn-action px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-700 font-semibold rounded-lg text-xs">Eliminar</button>
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
                await loadAdminData();
            } catch (err) {
                alert(err.message);
            }
        }

        async function deleteCategoria(id) {
            if (!confirm('¿Seguro que deseas eliminar esta categoría?')) return;
            try {
                const res = await fetch(`/api/categorias/${id}`, { method: 'DELETE', headers: apiHeaders() });
                const data = await res.json();
                if (!res.ok) throw new Error(data.mensaje || 'Error al eliminar');
                await loadAdminData();
            } catch (err) {
                alert(err.message);
            }
        }

        // MESAS CRUD
        function renderAdminMesas() {
            const grid = document.getElementById('grid-mesas');
            if (!grid) return;

            grid.innerHTML = allMesas.map(m => {
                const topBorder = {
                    disponible: 'border-t-emerald-400',
                    ocupada: 'border-t-blue-400',
                    reservada: 'border-t-purple-400',
                    fuera_servicio: 'border-t-red-400'
                }[m.estado] || 'border-t-slate-200';

                let statusBadge = {
                    disponible: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                    ocupada: 'bg-blue-100 text-blue-800 border-blue-200',
                    reservada: 'bg-purple-100 text-purple-800 border-purple-200',
                    fuera_servicio: 'bg-red-100 text-red-800 border-red-200'
                }[m.estado] || 'bg-slate-100 text-slate-700';

                return `
                    <div class="metric-card bg-white p-4 rounded-2xl border border-slate-200/80 border-t-4 ${topBorder} shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-2xl font-black text-slate-900">Mesa ${m.numero}</span>
                                <button onclick="deleteMesa(${m.id})" title="Eliminar mesa" class="text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg p-1.5 transition-colors duration-150 text-sm font-bold leading-none">&times;</button>
                            </div>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider border mb-3 ${statusBadge}">
                                ${m.estado.replace('_', ' ')}
                            </span>
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1 uppercase">Cambiar estado:</label>
                            <select onchange="updateMesaEstado(${m.id}, this.value)" class="w-full text-xs p-1.5 bg-slate-50 border border-slate-200 rounded-lg outline-none font-medium">
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
                await loadAdminData();
            } catch (err) {
                alert(err.message);
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
                await loadAdminData();
            } catch (err) {
                alert(err.message);
            }
        }

        async function deleteMesa(id) {
            if (!confirm('¿Eliminar esta mesa?')) return;
            try {
                const res = await fetch(`/api/mesas/${id}`, { method: 'DELETE', headers: apiHeaders() });
                const data = await res.json();
                if (!res.ok) throw new Error(data.mensaje || 'Error al eliminar');
                await loadAdminData();
            } catch (err) {
                alert(err.message);
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
                        <td class="p-3.5 font-extrabold text-slate-900">#${o.id}</td>
                        <td class="p-3.5 font-bold text-slate-700">Mesa ${o.mesa ? o.mesa.numero : 'S/N'}</td>
                        <td class="p-3.5 text-slate-600">${o.user ? o.user.name : 'N/A'}</td>
                        <td class="p-3.5 font-extrabold text-emerald-700">$${parseFloat(o.total || 0).toFixed(2)}</td>
                        <td class="p-3.5">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold border border-transparent ${badgeClass(o.estado)}">
                                ${estadoLabel}
                            </span>
                        </td>
                        <td class="p-3.5 text-slate-500">${fecha}</td>
                        <td class="p-3.5 text-right">
                            <button onclick="verDetalleOrden(${o.id})" class="btn-action px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg text-xs">
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
                <div>
                    <span class="text-slate-500">Mesero:</span> <strong>${orden.user ? orden.user.name : 'N/A'}</strong>
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
                        <td class="p-3.5 font-bold text-slate-500">#${p.id}</td>
                        <td class="p-3.5 font-bold text-slate-800">Orden #${p.orden_id}</td>
                        <td class="p-3.5 text-slate-700 font-medium">Mesa ${mesaNum}</td>
                        <td class="p-3.5">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide bg-slate-100 text-slate-700">
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
    </script>
</body>
</html>
