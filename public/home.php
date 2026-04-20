<?php
declare(strict_types=1);

require __DIR__ . '/partials/auth_guard.php';

$pageTitle  = 'SoportET20 ET20 - Dashboard';
$activePage = 'dashboard';
require __DIR__ . '/partials/head.php';
?>
<body class="bg-[#0b111a] text-slate-200 min-h-screen flex">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
        <header class="h-20 border-b border-slate-800 flex items-center justify-between px-8 bg-[#0b111a]/50 backdrop-blur-md sticky top-0 z-10">
            <div class="relative w-full max-w-xl">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" placeholder="Buscar aulas, tickets o equipos..."
                    class="w-full bg-[#0f172a] border-none rounded-xl pl-12 pr-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-blue-500/20 outline-none transition-all">
            </div>
            <div class="flex items-center gap-4">
                <?php require __DIR__ . '/partials/notification_bell.php'; ?>
            </div>
        </header>

        <main class="flex-1 p-10 overflow-y-auto">
            <div class="max-w-[1400px] mx-auto">
                <!-- Dashboard Tab -->
                <div id="tab-dashboard" class="tab-content space-y-10">
                    <div class="max-w-3xl">
                        <h1 class="text-white text-4xl font-black tracking-tight mb-4">Bienvenido, <span id="welcomeName"><?= htmlspecialchars($currentUser['nombre'] ?: $currentUser['usuario'], ENT_QUOTES, 'UTF-8') ?></span></h1>
                        <p class="text-slate-400 text-lg leading-relaxed">
                            Monitorea el estado de los equipos en tiempo real, gestiona tickets de soporte técnico y genera reportes detallados de toda la institución.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <button onclick="showTab('status')" class="group bg-[#0f172a] border border-slate-800 rounded-2xl overflow-hidden hover:border-blue-500/50 transition-all hover:shadow-2xl hover:shadow-blue-500/5 text-left">
                            <div class="h-40 bg-[#1e293b]/50 flex items-center justify-center">
                                <svg class="text-blue-500 group-hover:scale-110 transition-transform" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                            </div>
                            <div class="p-6">
                                <h3 class="text-white text-lg font-bold mb-2">Ver Estado de PC</h3>
                                <p class="text-slate-400 text-sm">Monitorea la disponibilidad y salud de los equipos en todos los laboratorios.</p>
                            </div>
                        </button>

                        <button onclick="showTab('new-ticket')" class="group bg-[#0f172a] border border-slate-800 rounded-2xl overflow-hidden hover:border-blue-500/50 transition-all hover:shadow-2xl hover:shadow-blue-500/5 text-left">
                            <div class="h-40 bg-blue-600/10 flex items-center justify-center">
                                <svg class="text-blue-500 group-hover:scale-110 transition-transform" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            </div>
                            <div class="p-6">
                                <h3 class="text-white text-lg font-bold mb-2">Crear Nuevo Ticket</h3>
                                <p class="text-slate-400 text-sm">Reporta rápidamente problemas de hardware o software en cualquier aula.</p>
                            </div>
                        </button>

                        <button onclick="showTab('tickets')" class="group bg-[#0f172a] border border-slate-800 rounded-2xl overflow-hidden hover:border-blue-500/50 transition-all hover:shadow-2xl hover:shadow-blue-500/5 text-left">
                            <div class="h-40 bg-slate-800/50 flex items-center justify-center">
                                <svg class="text-blue-500 group-hover:scale-110 transition-transform" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9V5.2a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2V9"/><path d="M2 15v3.8a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V15"/><path d="M2 9c0 1.1.9 2 2 2h16a2 2 0 0 0 2-2"/><path d="M2 15c0-1.1.9-2 2-2h16a2 2 0 0 1 2 2"/></svg>
                            </div>
                            <div class="p-6">
                                <h3 class="text-white text-lg font-bold mb-2">Ver Tickets Abiertos</h3>
                                <p class="text-slate-400 text-sm">Gestiona y haz seguimiento a los casos de soporte técnico pendientes.</p>
                            </div>
                        </button>
                    </div>

                    <?php if (in_array($currentUser['rol'], ['ADMIN','TECNICO'], true)): ?>
                    <!-- ============================================ -->
                    <!-- ESTADÍSTICAS (ADMIN + TECNICO) -->
                    <!-- ============================================ -->
                    <div class="space-y-6 pt-8 border-t border-slate-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-2 text-blue-500 font-bold text-xs uppercase tracking-widest mb-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                    Panel de Control
                                </div>
                                <h2 class="text-2xl font-black text-white tracking-tight">Estadísticas</h2>
                            </div>
                            <button onclick="cargarEstadisticas()" class="flex items-center gap-2 px-4 py-2 bg-[#0f172a] hover:bg-slate-800 border border-slate-700 rounded-xl text-xs font-bold text-slate-300 hover:text-white transition-all" title="Refrescar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M3 21v-5h5"/></svg>
                                REFRESCAR
                            </button>
                        </div>

                        <!-- KPI cards -->
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4" id="kpiCards">
                            <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-5">
                                <div class="text-[10px] uppercase tracking-widest font-bold text-slate-500">Tickets abiertos</div>
                                <div class="mt-2 text-3xl font-black text-white" id="kpiAbiertos">—</div>
                            </div>
                            <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-5">
                                <div class="text-[10px] uppercase tracking-widest font-bold text-slate-500">Resueltos últ. 7 días</div>
                                <div class="mt-2 text-3xl font-black text-emerald-400" id="kpiResueltos">—</div>
                            </div>
                            <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-5">
                                <div class="text-[10px] uppercase tracking-widest font-bold text-slate-500">PCs operativas</div>
                                <div class="mt-2 text-3xl font-black text-white"><span id="kpiPcOperativas">—</span> <span class="text-sm text-slate-500" id="kpiPcTotal"></span></div>
                                <div class="mt-1 text-xs text-slate-400" id="kpiPcPct">—</div>
                            </div>
                            <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-5">
                                <div class="text-[10px] uppercase tracking-widest font-bold text-slate-500">Tiempo medio resol.</div>
                                <div class="mt-2 text-3xl font-black text-white" id="kpiTiempoMedio">—</div>
                                <div class="mt-1 text-xs text-slate-500">últimos 30 días</div>
                            </div>
                        </div>

                        <!-- Charts -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-6">
                                <h3 class="text-white text-sm font-bold mb-4">Tickets activos por aula</h3>
                                <div class="relative h-72"><canvas id="chartTicketsAula"></canvas></div>
                            </div>
                            <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-6">
                                <h3 class="text-white text-sm font-bold mb-4">Estado de PCs</h3>
                                <div class="relative h-72"><canvas id="chartEstadoPc"></canvas></div>
                            </div>
                        </div>

                        <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-6">
                            <h3 class="text-white text-sm font-bold mb-4">Resolución semanal (últimas 8 semanas)</h3>
                            <div class="relative h-64"><canvas id="chartResolucion"></canvas></div>
                        </div>

                        <!-- Top PCs problemáticas -->
                        <div class="bg-[#0f172a] border border-slate-800 rounded-2xl overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-800">
                                <h3 class="text-white text-sm font-bold">Top 5 PCs con más incidencias (histórico)</h3>
                                <p class="text-xs text-slate-500 mt-1">PCs que requieren atención prioritaria o reemplazo.</p>
                            </div>
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">
                                        <th class="px-6 py-3">#</th>
                                        <th class="px-6 py-3">PC</th>
                                        <th class="px-6 py-3">Aula</th>
                                        <th class="px-6 py-3 text-right">Incidencias</th>
                                    </tr>
                                </thead>
                                <tbody id="topPcsBody" class="divide-y divide-slate-800"></tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Status Tab -->
                <div id="tab-status" class="tab-content hidden space-y-8">
                    <div>
                        <div class="flex items-center gap-2 text-blue-500 font-bold text-xs uppercase tracking-widest mb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            Estado de Red en Vivo
                        </div>
                        <h2 class="text-3xl font-black text-white tracking-tight">Grilla de Operaciones Central</h2>
                        <p class="text-slate-400 mt-1">Monitoreo de disponibilidad y salud de hardware en las 24 secciones de la escuela.</p>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex bg-[#0f172a] p-1 rounded-xl border border-slate-800" id="aulaFilters"></div>

                        <div class="flex flex-wrap items-center gap-5 bg-[#0f172a] px-6 py-3 rounded-xl border border-slate-800">
                            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-emerald-500"></span><span class="text-xs font-bold text-slate-300">Operativa</span></div>
                            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-amber-500"></span><span class="text-xs font-bold text-slate-300">Mantenimiento</span></div>
                            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-rose-500"></span><span class="text-xs font-bold text-slate-300">Fuera de Servicio</span></div>
                            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-slate-400"></span><span class="text-xs font-bold text-slate-300">Hibernando</span></div>
                            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-cyan-400"></span><span class="text-xs font-bold text-slate-300">Alerta</span></div>
                        </div>
                    </div>

                    <div class="border-l-4 border-blue-600 pl-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-xl font-bold text-white" id="currentAulaTitle">Aula 101</h3>
                                <p class="text-xs text-slate-500 mt-1">Subred primaria: 10.0.101.*</p>
                            </div>
                            <button onclick="openAulaInfoModal()" class="flex items-center gap-2 px-4 py-2 bg-[#0f172a] hover:bg-slate-800 border border-slate-700 hover:border-purple-500/50 rounded-xl text-slate-300 hover:text-white transition-all" title="Ver Access Point, contraseñas y software del aula">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                <span class="text-xs font-bold uppercase tracking-wider">Info aula</span>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-4" id="pcGrid"></div>
                    </div>
                </div>

                <!-- Tickets Tab -->
                <div id="tab-tickets" class="tab-content hidden space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-3xl font-black text-white tracking-tight">Cola de Tickets</h2>
                        <button onclick="showTab('new-ticket')" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-6 py-2.5 rounded-xl transition-all flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            Nuevo Ticket
                        </button>
                    </div>

                    <!-- Filtros -->
                    <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-5">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-3 items-end">
                            <div>
                                <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Estado</label>
                                <select id="fTicketEstado" class="w-full bg-[#1e293b] border border-slate-700 rounded-lg px-2 py-2 text-white text-xs focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">Todos</option>
                                    <option value="ABIERTO">Abierto</option>
                                    <option value="EN_PROGRESO">En Progreso</option>
                                    <option value="RESUELTO">Resuelto</option>
                                    <option value="CERRADO">Cerrado</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Prioridad</label>
                                <select id="fTicketPrioridad" class="w-full bg-[#1e293b] border border-slate-700 rounded-lg px-2 py-2 text-white text-xs focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">Todas</option>
                                    <option value="BAJA">Baja</option>
                                    <option value="MEDIA">Media</option>
                                    <option value="ALTA">Alta</option>
                                    <option value="CRITICA">Crítica</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Aula</label>
                                <select id="fTicketAula" class="w-full bg-[#1e293b] border border-slate-700 rounded-lg px-2 py-2 text-white text-xs focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">Todas</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Tipo</label>
                                <select id="fTicketTipo" class="w-full bg-[#1e293b] border border-slate-700 rounded-lg px-2 py-2 text-white text-xs focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">Todos</option>
                                    <option value="HARDWARE">Hardware</option>
                                    <option value="SOFTWARE">Software</option>
                                    <option value="RED">Red</option>
                                    <option value="PERIFERICO">Periférico</option>
                                    <option value="OTRO">Otro</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Desde</label>
                                <input type="date" id="fTicketDesde" class="w-full bg-[#1e293b] border border-slate-700 rounded-lg px-2 py-2 text-white text-xs focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Hasta</label>
                                <input type="date" id="fTicketHasta" class="w-full bg-[#1e293b] border border-slate-700 rounded-lg px-2 py-2 text-white text-xs focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                            <div class="flex gap-2">
                                <button onclick="aplicarFiltrosTickets()" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-bold px-3 py-2 rounded-lg text-xs transition-all">Filtrar</button>
                                <button onclick="limpiarFiltrosTickets()" class="px-2 py-2 text-slate-400 hover:text-white border border-slate-700 hover:border-slate-500 rounded-lg transition-all" title="Limpiar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="mt-3">
                            <input type="text" id="fTicketBusqueda" placeholder="Buscar en descripción..." class="w-full bg-[#1e293b] border border-slate-700 rounded-lg px-3 py-2 text-white text-xs focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                    </div>

                    <div class="bg-[#0f172a] border border-slate-800 rounded-2xl overflow-hidden">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-slate-800 text-[10px] uppercase font-bold text-slate-500 tracking-widest">
                                    <th class="px-6 py-4">ID</th>
                                    <th class="px-6 py-4">Aula / PC</th>
                                    <th class="px-6 py-4">Prioridad</th>
                                    <th class="px-6 py-4">Descripción</th>
                                    <th class="px-6 py-4">Usuario</th>
                                    <th class="px-6 py-4">Estado</th>
                                    <th class="px-6 py-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="ticketTableBody" class="divide-y divide-slate-800"></tbody>
                        </table>
                    </div>
                </div>

                <!-- New Ticket Tab -->
                <div id="tab-new-ticket" class="tab-content hidden max-w-3xl mx-auto py-10">
                    <div class="mb-8">
                        <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">
                            <button onclick="showTab('tickets')" class="hover:text-blue-500">Tickets</button>
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            <span class="text-white">Nuevo Ticket</span>
                        </nav>
                        <h1 class="text-white text-4xl font-black tracking-tight mb-2">Crear Ticket de Soporte</h1>
                        <p class="text-slate-400 text-lg">Reporta problemas técnicos con el equipamiento del aula.</p>
                    </div>

                    <div class="bg-[#0f172a] border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
                        <div class="p-8 bg-gradient-to-r from-blue-600/10 to-transparent border-b border-slate-800 flex items-center gap-6">
                            <div class="w-14 h-14 bg-blue-600/20 rounded-2xl flex items-center justify-center text-blue-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            </div>
                            <div>
                                <h3 class="text-white text-xl font-bold">Información del Ticket</h3>
                                <p class="text-slate-500 text-sm">Completa los detalles para agilizar tu solicitud.</p>
                            </div>
                        </div>

                        <form id="ticketForm" class="p-8 space-y-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-3">
                                    <label class="text-slate-300 text-sm font-bold block">Aula / Laboratorio</label>
                                    <select id="formAula" required class="w-full bg-[#1e293b] border border-slate-700 rounded-xl px-4 py-3.5 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                        <option value="">Seleccionar ubicación</option>
                                    </select>
                                </div>
                                <div class="space-y-3">
                                    <label class="text-slate-300 text-sm font-bold block">PC</label>
                                    <select id="formPC" required disabled class="w-full bg-[#1e293b] border border-slate-700 rounded-xl px-4 py-3.5 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                        <option value="">Primero elegí un aula…</option>
                                    </select>
                                </div>
                                <div class="space-y-3">
                                    <label class="text-slate-300 text-sm font-bold block">Tipo de Problema</label>
                                    <select id="formTipo" required class="w-full bg-[#1e293b] border border-slate-700 rounded-xl px-4 py-3.5 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                        <option value="">Seleccionar categoría</option>
                                        <option value="hardware">Hardware (Monitor, PC, etc)</option>
                                        <option value="software">Software / Aplicaciones</option>
                                        <option value="red">Red / Internet</option>
                                        <option value="periferico">Periféricos (Teclado, Mouse)</option>
                                    </select>
                                </div>
                                <div class="space-y-3">
                                    <label class="text-slate-300 text-sm font-bold block">Prioridad</label>
                                    <div class="flex bg-[#1e293b] p-1 rounded-xl border border-slate-700">
                                        <button type="button" onclick="setPriority('BAJA')" id="prio-BAJA" class="prio-btn flex-1 py-2.5 text-xs font-bold rounded-lg transition-all text-slate-500">Baja</button>
                                        <button type="button" onclick="setPriority('MEDIA')" id="prio-MEDIA" class="prio-btn flex-1 py-2.5 text-xs font-bold rounded-lg transition-all bg-blue-600 text-white shadow-lg shadow-blue-600/20">Media</button>
                                        <button type="button" onclick="setPriority('ALTA')" id="prio-ALTA" class="prio-btn flex-1 py-2.5 text-xs font-bold rounded-lg transition-all text-slate-500">Alta</button>
                                        <button type="button" onclick="setPriority('CRITICA')" id="prio-CRITICA" class="prio-btn flex-1 py-2.5 text-xs font-bold rounded-lg transition-all text-slate-500">Crítica</button>
                                    </div>
                                    <input type="hidden" id="formPrioridad" value="MEDIA">
                                </div>
                            </div>

                            <div class="space-y-3">
                                <label class="text-slate-300 text-sm font-bold block">Descripción del Problema</label>
                                <textarea id="formDesc" rows="5" placeholder="Describe el problema en detalle..." required class="w-full bg-[#1e293b] border border-slate-700 rounded-xl px-4 py-3.5 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all resize-none"></textarea>
                            </div>

                            <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-800">
                                <button type="button" onclick="showTab('dashboard')" class="px-8 py-3.5 text-slate-400 font-bold hover:text-white transition-colors">Cancelar</button>
                                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-12 py-3.5 rounded-xl transition-all shadow-lg shadow-blue-600/20 active:scale-[0.98] flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polyline points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                    Enviar Ticket
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <!-- MODAL: Ficha Técnica de PC -->
        <div id="pcModal" class="hidden fixed inset-0 z-40 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-[#0f172a] border border-slate-800 rounded-2xl w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-start justify-between p-6 border-b border-slate-800 sticky top-0 bg-[#0f172a] z-10">
                    <div>
                        <h3 id="pcModalTitle" class="text-white text-xl font-bold">PC</h3>
                        <p id="pcModalSubtitle" class="text-slate-500 text-xs mt-1">—</p>
                    </div>
                    <button onclick="closePcModal()" class="text-slate-500 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <div id="pcModalBody" class="p-6 space-y-6">
                    <div class="text-center text-slate-500 py-8">Cargando...</div>
                </div>
            </div>
        </div>

        <!-- Modal: Info del Aula (read-only) -->
        <div id="aulaInfoModal" class="hidden fixed inset-0 z-40 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-[#0f172a] border border-slate-800 rounded-2xl w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-start justify-between p-6 border-b border-slate-800 sticky top-0 bg-[#0f172a] z-10">
                    <div>
                        <h3 id="aulaInfoTitle" class="text-white text-xl font-bold">Info del aula</h3>
                        <p id="aulaInfoSubtitle" class="text-slate-500 text-xs mt-1">—</p>
                    </div>
                    <button onclick="closeAulaInfoModal()" class="text-slate-500 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <div id="aulaInfoBody" class="p-6 space-y-6">
                    <div class="text-center text-slate-500 py-8">Cargando…</div>
                </div>
            </div>
        </div>

        <div id="toast" class="hidden fixed top-24 right-8 z-50 px-5 py-3 rounded-xl shadow-2xl font-bold text-sm max-w-md"></div>

        <footer class="p-8 border-t border-slate-800 flex flex-col md:flex-row items-center justify-between gap-4 opacity-50">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <p class="text-xs font-medium tracking-tight">SoportET20 ET20 v1.0.0</p>
            </div>
            <div class="flex gap-8 text-[10px] font-bold uppercase tracking-widest">
                <a href="#" class="hover:text-blue-500 transition-colors">Documentación</a>
                <a href="#" class="hover:text-blue-500 transition-colors">Soporte</a>
                <a href="#" class="hover:text-blue-500 transition-colors">Privacidad</a>
            </div>
        </footer>
    </div>

    <?php require __DIR__ . '/partials/scripts_common.php'; ?>
    <?php if (in_array($currentUser['rol'], ['ADMIN','TECNICO'], true)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php endif; ?>

    <script>
        // --- State (user provided server-side via window.CURRENT_USER) ---
        const user = window.CURRENT_USER;
        let aulas = [];
        let computers = [];
        let tickets = [];
        let selectedAula = null;

        (async function init() {
            await loadAulas();

            const urlParams = new URLSearchParams(window.location.search);
            const pcParam = urlParams.get('pc');
            if (pcParam) {
                await abrirTicketDesdeQR(pcParam);
                return;
            }

            const hashTab = window.location.hash.replace('#', '');
            const validTabs = ['dashboard', 'tickets', 'status', 'new-ticket'];
            showTab(validTabs.includes(hashTab) ? hashTab : 'dashboard');
        })();

        // --- Deep-link desde QR: abre "Nuevo Ticket" con la PC pre-seleccionada ---
        async function abrirTicketDesdeQR(pcId) {
            try {
                const res = await fetch(`../backend/computers.php?id=${encodeURIComponent(pcId)}&detalle=1`, { credentials: 'include' });
                if (res.status === 401) { window.location.href = 'index.php'; return; }
                if (!res.ok) {
                    toast(`No se encontró la PC "${pcId}".`, 'error');
                    showTab('dashboard');
                    return;
                }
                const pc = await res.json();

                const aulaSelect = document.getElementById('formAula');
                aulaSelect.value = pc.id_aula;
                selectedAula = pc.id_aula;

                await cargarPcsDelAula(pc.id_aula);
                const pcSelect = document.getElementById('formPC');
                pcSelect.value = pc.id;

                showTab('new-ticket');
                toast(`PC ${pc.id} — ${pc.aula_nombre || 'Aula ' + pc.id_aula}. Completá el ticket.`, 'success');

                if (history.replaceState) {
                    const clean = window.location.pathname + '#new-ticket';
                    history.replaceState(null, '', clean);
                }
            } catch (err) {
                console.error('Error abriendo ticket por QR:', err);
                toast('Error al procesar el QR.', 'error');
                showTab('dashboard');
            }
        }

        async function loadAulas() {
            try {
                const res = await fetch('../backend/aulas.php', { credentials: 'include' });
                if (res.status === 401) { window.location.href = 'index.php'; return; }
                aulas = await res.json();
            } catch (err) {
                console.error('Error cargando aulas:', err);
                aulas = [];
            }

            if (aulas.length > 0) {
                selectedAula = aulas[0].id;
                const select = document.getElementById('formAula');
                select.innerHTML = '<option value="">Seleccionar ubicación</option>' +
                    aulas.map(a => `<option value="${escapeHtml(a.id)}">${escapeHtml(a.nombre)} (${escapeHtml(a.id)})</option>`).join('');
                select.addEventListener('change', () => cargarPcsDelAula(select.value));
            }
        }

        async function cargarPcsDelAula(aulaId) {
            const selectPC = document.getElementById('formPC');
            if (!aulaId) {
                selectPC.innerHTML = '<option value="">Primero elegí un aula…</option>';
                selectPC.disabled = true;
                return;
            }
            selectPC.disabled = true;
            selectPC.innerHTML = '<option value="">Cargando PCs…</option>';
            try {
                const res = await fetch(`../backend/computers.php?id_aula=${encodeURIComponent(aulaId)}`, { credentials: 'include' });
                if (res.status === 401) { window.location.href = 'index.php'; return; }
                const pcs = await res.json();
                if (!Array.isArray(pcs) || pcs.length === 0) {
                    selectPC.innerHTML = '<option value="">No hay PCs registradas en esta aula</option>';
                    return;
                }
                const ESTADO_LABEL = {
                    'OPERATIVA': '✓', 'MANTENIMIENTO': '⚠', 'FUERA_SERVICIO': '✕',
                    'HIBERNANDO': '💤', 'ALERTA': '!',
                };
                selectPC.innerHTML = '<option value="">Seleccionar PC…</option>' +
                    pcs.map(p => {
                        const marker = ESTADO_LABEL[p.estado] || '';
                        const label = `${p.id} — ${p.nombre || 'Sin nombre'} ${marker ? '(' + marker + ' ' + p.estado + ')' : ''}`;
                        return `<option value="${escapeHtml(p.id)}">${escapeHtml(label)}</option>`;
                    }).join('');
                selectPC.disabled = false;
            } catch (err) {
                console.error('Error cargando PCs del aula:', err);
                selectPC.innerHTML = '<option value="">Error al cargar PCs</option>';
            }
        }

        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
            const tabEl = document.getElementById('tab-' + tabId);
            if (tabEl) tabEl.classList.remove('hidden');

            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('text-blue-500', 'bg-blue-600/10', 'font-bold');
                btn.classList.add('text-slate-400');
            });
            const activeBtn = document.getElementById('btn-' + tabId);
            if (activeBtn) {
                activeBtn.classList.add('text-blue-500', 'bg-blue-600/10', 'font-bold');
                activeBtn.classList.remove('text-slate-400');
            }

            if (tabId === 'status') renderStatusGrid();
            if (tabId === 'tickets') renderTickets();
            if (tabId === 'dashboard' && document.getElementById('kpiCards') && typeof Chart !== 'undefined') cargarEstadisticas();
        }

        // --- Status Grid ---
        const ESTADO_COLORES = {
            'OPERATIVA':       'text-emerald-500 border-emerald-500/20 bg-emerald-500/5',
            'MANTENIMIENTO':   'text-amber-500 border-amber-500/20 bg-amber-500/5',
            'FUERA_SERVICIO':  'text-rose-500 border-rose-500/20 bg-rose-500/5',
            'HIBERNANDO':      'text-slate-400 border-slate-600/20 bg-slate-600/5',
            'ALERTA':          'text-cyan-400 border-cyan-400/20 bg-cyan-400/5',
        };

        async function renderStatusGrid() {
            const filters = document.getElementById('aulaFilters');
            filters.innerHTML = aulas.map(a => `
                <button onclick="selectAula('${a.id}')" class="px-4 py-2 text-sm font-bold rounded-lg transition-all ${selectedAula === a.id ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'text-slate-400 hover:text-white'}">
                    ${escapeHtml(a.id)}
                </button>
            `).join('');

            const aulaInfo = aulas.find(a => a.id === selectedAula);
            document.getElementById('currentAulaTitle').textContent = aulaInfo ? aulaInfo.nombre : 'Aula ' + selectedAula;

            const grid = document.getElementById('pcGrid');

            try {
                const res = await fetch(`../backend/computers.php?id_aula=${encodeURIComponent(selectedAula)}`, { credentials: 'include' });
                if (res.status === 401) { window.location.href = 'index.php'; return; }
                computers = await res.json();
            } catch (err) {
                console.error('Error cargando PCs:', err);
                computers = [];
            }

            const capacidad = aulaInfo ? aulaInfo.capacidad_pcs : 12;

            let html = computers.map(pc => {
                const colorClass = ESTADO_COLORES[pc.estado] || 'text-slate-500 border-slate-700 bg-slate-800/20';
                const idEscaped = pc.id.replace(/'/g, "\\'");
                return `
                    <div onclick="openPcModal('${idEscaped}')" class="aspect-square rounded-2xl border flex flex-col items-center justify-center gap-2 cursor-pointer transition-all hover:scale-105 ${colorClass}" title="${escapeHtml(pc.nombre || pc.id)} — ${escapeHtml(pc.estado)} (click para ver ficha)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        <span class="text-[10px] font-bold uppercase tracking-wider">${escapeHtml(pc.id)}</span>
                    </div>
                `;
            }).join('');

            for (let i = computers.length; i < capacidad; i++) {
                html += `
                    <div class="aspect-square rounded-2xl border border-slate-800 bg-slate-800/20 flex flex-col items-center justify-center gap-2 opacity-30">
                        <svg class="text-slate-700" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        <span class="text-[10px] font-bold text-slate-700 uppercase tracking-wider">---</span>
                    </div>
                `;
            }
            grid.innerHTML = html;
        }

        function selectAula(aulaId) {
            selectedAula = aulaId;
            renderStatusGrid();
        }

        // --- Tickets ---
        const ESTADO_TICKET_BADGE = {
            'ABIERTO':     'bg-amber-500/10 text-amber-500',
            'EN_PROGRESO': 'bg-blue-500/10 text-blue-500',
            'RESUELTO':    'bg-emerald-500/10 text-emerald-500',
            'CERRADO':     'bg-slate-500/10 text-slate-400',
        };
        const ESTADO_TICKET_LABEL = {
            'ABIERTO': 'Abierto', 'EN_PROGRESO': 'En Progreso', 'RESUELTO': 'Resuelto', 'CERRADO': 'Cerrado',
        };
        const PRIORIDAD_TICKET_BADGE = {
            'BAJA':    'bg-slate-500/10 text-slate-400',
            'MEDIA':   'bg-blue-500/10 text-blue-400',
            'ALTA':    'bg-amber-500/10 text-amber-500',
            'CRITICA': 'bg-rose-500/10 text-rose-500',
        };

        let ticketFiltros = {};

        function aplicarFiltrosTickets() {
            ticketFiltros = {
                estado:    document.getElementById('fTicketEstado').value,
                prioridad: document.getElementById('fTicketPrioridad').value,
                id_aula:   document.getElementById('fTicketAula').value,
                tipo:      document.getElementById('fTicketTipo').value,
                desde:     document.getElementById('fTicketDesde').value,
                hasta:     document.getElementById('fTicketHasta').value,
                busqueda:  document.getElementById('fTicketBusqueda').value.trim(),
            };
            renderTickets();
        }

        function limpiarFiltrosTickets() {
            ['fTicketEstado','fTicketPrioridad','fTicketAula','fTicketTipo','fTicketDesde','fTicketHasta','fTicketBusqueda']
                .forEach(id => { document.getElementById(id).value = ''; });
            ticketFiltros = {};
            renderTickets();
        }

        async function renderTickets() {
            const tbody = document.getElementById('ticketTableBody');

            const selAula = document.getElementById('fTicketAula');
            if (selAula && selAula.options.length <= 1 && aulas.length > 0) {
                selAula.innerHTML = '<option value="">Todas</option>' +
                    aulas.map(a => `<option value="${escapeHtml(a.id)}">${escapeHtml(a.id)} — ${escapeHtml(a.nombre || '')}</option>`).join('');
            }

            const params = new URLSearchParams();
            for (const [k, v] of Object.entries(ticketFiltros)) {
                if (v) params.set(k, v);
            }
            const qs = params.toString();
            const url = '../backend/tickets.php' + (qs ? '?' + qs : '');

            try {
                const res = await fetch(url, { credentials: 'include' });
                if (res.status === 401) { window.location.href = 'index.php'; return; }
                tickets = await res.json();
            } catch (err) {
                console.error('Error cargando tickets:', err);
                tickets = [];
            }

            if (!Array.isArray(tickets) || tickets.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">No hay tickets que coincidan con los filtros.</td></tr>`;
                return;
            }

            tbody.innerHTML = tickets.map(t => `
                <tr class="hover:bg-slate-800/30 transition-colors">
                    <td class="px-6 py-4 text-sm font-bold text-white">#${escapeHtml(String(t.id))}</td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm text-white font-medium">Aula ${escapeHtml(t.id_aula)}</span>
                            <span class="text-[10px] text-slate-500">${escapeHtml(t.id_pc)}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded text-[10px] font-bold uppercase ${PRIORIDAD_TICKET_BADGE[t.prioridad] || ''}">${escapeHtml(t.prioridad)}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-400 max-w-xs truncate">${escapeHtml(t.descripcion)}</td>
                    <td class="px-6 py-4 text-sm text-slate-400">${escapeHtml(t.reportado_por)}</td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase ${ESTADO_TICKET_BADGE[t.estado] || ''}">
                            ${ESTADO_TICKET_LABEL[t.estado] || escapeHtml(t.estado)}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <button onclick="openPcModal('${t.id_pc.replace(/'/g, "\\'")}')" class="text-blue-500 hover:text-blue-400 text-xs font-bold">Ver PC</button>
                    </td>
                </tr>
            `).join('');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const busq = document.getElementById('fTicketBusqueda');
            if (busq) {
                busq.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') { e.preventDefault(); aplicarFiltrosTickets(); }
                });
            }
        });

        // --- New Ticket ---
        function setPriority(p) {
            document.getElementById('formPrioridad').value = p;
            document.querySelectorAll('.prio-btn').forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white', 'shadow-lg', 'shadow-blue-600/20');
                btn.classList.add('text-slate-500');
            });
            const activeBtn = document.getElementById('prio-' + p);
            activeBtn.classList.add('bg-blue-600', 'text-white', 'shadow-lg', 'shadow-blue-600/20');
            activeBtn.classList.remove('text-slate-500');
        }

        document.getElementById('ticketForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const ticketData = {
                id_pc: document.getElementById('formPC').value.trim(),
                tipo: document.getElementById('formTipo').value.toUpperCase(),
                prioridad: document.getElementById('formPrioridad').value.toUpperCase(),
                descripcion: document.getElementById('formDesc').value.trim(),
            };

            if (!ticketData.id_pc || !ticketData.descripcion) {
                toast('Completá todos los campos obligatorios.', 'error');
                return;
            }

            try {
                const res = await fetch('../backend/tickets.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify(ticketData),
                });
                const result = await res.json();
                if (result.success) {
                    toast(result.message, result.escalado_automatico ? 'warning' : 'success');
                    showTab('tickets');
                    this.reset();
                    setPriority('MEDIA');
                    cargarPcsDelAula('');
                } else {
                    toast('Error: ' + result.message, 'error');
                }
            } catch (err) {
                console.error('Error creando ticket:', err);
                toast('Error de conexión al crear el ticket.', 'error');
            }
        });

        // --- Toast ---
        function toast(message, type = 'success') {
            const el = document.getElementById('toast');
            el.textContent = message;
            const colors = {
                success: 'bg-emerald-600 text-white',
                error:   'bg-rose-600 text-white',
                warning: 'bg-amber-500 text-black',
            };
            el.className = 'fixed top-24 right-8 z-50 px-5 py-3 rounded-xl shadow-2xl font-bold text-sm max-w-md ' + (colors[type] || colors.success);
            el.classList.remove('hidden');
            setTimeout(() => el.classList.add('hidden'), 4500);
        }

        // --- PC Modal ---
        const ESTADO_LABEL_PC = {
            'OPERATIVA':      { label: 'Operativa',         color: 'bg-emerald-500/10 text-emerald-500' },
            'MANTENIMIENTO':  { label: 'Mantenimiento',     color: 'bg-amber-500/10 text-amber-500' },
            'FUERA_SERVICIO': { label: 'Fuera de Servicio', color: 'bg-rose-500/10 text-rose-500' },
            'HIBERNANDO':     { label: 'Hibernando',        color: 'bg-slate-500/10 text-slate-400' },
            'ALERTA':         { label: 'Alerta',            color: 'bg-cyan-500/10 text-cyan-400' },
        };
        const PRIORIDAD_BADGE = {
            'BAJA':    'bg-slate-500/10 text-slate-400',
            'MEDIA':   'bg-blue-500/10 text-blue-400',
            'ALTA':    'bg-amber-500/10 text-amber-500',
            'CRITICA': 'bg-rose-500/10 text-rose-500',
        };

        let currentPcModalId = null;

        async function openPcModal(idPc) {
            currentPcModalId = idPc;
            const modal = document.getElementById('pcModal');
            const body  = document.getElementById('pcModalBody');

            document.getElementById('pcModalTitle').textContent = idPc;
            document.getElementById('pcModalSubtitle').textContent = 'Cargando ficha técnica...';
            body.innerHTML = '<div class="text-center text-slate-500 py-8">Cargando...</div>';
            modal.classList.remove('hidden');

            try {
                const res = await fetch(`../backend/computers.php?id=${encodeURIComponent(idPc)}&detalle=1`, { credentials: 'include' });
                if (res.status === 401) { window.location.href = 'index.php'; return; }
                if (!res.ok) {
                    const err = await res.json();
                    throw new Error(err.error || err.message || 'Error al cargar la ficha');
                }
                const pc = await res.json();
                renderPcModal(pc);
            } catch (err) {
                body.innerHTML = `<div class="text-center text-rose-500 py-8">${escapeHtml(err.message)}</div>`;
            }
        }

        function closePcModal() {
            document.getElementById('pcModal').classList.add('hidden');
            currentPcModalId = null;
        }

        function renderPcModal(pc) {
            const estadoInfo = ESTADO_LABEL_PC[pc.estado] || { label: pc.estado, color: 'bg-slate-500/10 text-slate-400' };

            document.getElementById('pcModalTitle').textContent = pc.nombre || pc.id;
            document.getElementById('pcModalSubtitle').innerHTML =
                `<span class="text-slate-400">${escapeHtml(pc.id)}</span> · ${escapeHtml(pc.aula_nombre)} · <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase ${estadoInfo.color}">${escapeHtml(estadoInfo.label)}</span>`;

            let html = '';
            html += `<div><h4 class="text-[10px] uppercase tracking-widest font-bold text-slate-500 mb-3">Ficha Técnica</h4>`;

            if (pc.specs) {
                const s = pc.specs;
                html += `<div class="bg-[#1e293b] rounded-xl p-5 space-y-3 border border-slate-700">
                    ${s.cpu ? specRow('CPU', s.cpu, cpuIcon) : ''}
                    ${s.ram ? specRow('RAM', s.ram, ramIcon) : ''}
                    ${s.os  ? specRow('Sistema Operativo', s.os, osIcon) : ''}
                    ${s.placa ? specRow('Placa Madre', s.placa, mbIcon) : ''}
                </div>`;

                if (Array.isArray(s.discos) && s.discos.length > 0) {
                    html += `<div class="mt-3">
                        <div class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-2">Almacenamiento</div>
                        <div class="space-y-2">
                            ${s.discos.map(d => `
                                <div class="flex items-center justify-between bg-[#1e293b] rounded-xl px-4 py-3 border border-slate-700">
                                    <div class="flex items-center gap-3">
                                        <span class="text-[10px] font-bold bg-blue-600/20 text-blue-400 px-2 py-1 rounded">${escapeHtml(d.tipo)}</span>
                                        <span class="text-sm text-white">${escapeHtml(d.modelo || '-')}</span>
                                    </div>
                                    <span class="text-sm text-slate-400 font-mono">${escapeHtml(d.capacidad)}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>`;
                }
            } else {
                html += `<div class="bg-[#1e293b] rounded-xl p-5 text-center text-slate-500 text-sm border border-slate-700 border-dashed">
                    No hay especificaciones registradas para esta PC.
                </div>`;
            }

            if (pc.ip || pc.mac) {
                html += `<div class="mt-3 grid grid-cols-2 gap-2">
                    ${pc.ip ? `<div class="bg-[#1e293b] rounded-xl px-4 py-3 border border-slate-700">
                        <div class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">IP</div>
                        <div class="text-sm text-white font-mono mt-1">${escapeHtml(pc.ip)}</div>
                    </div>` : ''}
                    ${pc.mac ? `<div class="bg-[#1e293b] rounded-xl px-4 py-3 border border-slate-700">
                        <div class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">MAC</div>
                        <div class="text-sm text-white font-mono mt-1">${escapeHtml(pc.mac)}</div>
                    </div>` : ''}
                </div>`;
            }
            html += `</div>`;

            if (pc.ticket_activo) {
                const t = pc.ticket_activo;
                const prioClass = PRIORIDAD_BADGE[t.prioridad] || 'bg-slate-500/10 text-slate-400';
                const puedeResolver = ['ADMIN', 'TECNICO'].includes(user.rol);

                html += `<div>
                    <h4 class="text-[10px] uppercase tracking-widest font-bold text-slate-500 mb-3">Ticket Activo</h4>
                    <div class="bg-gradient-to-br from-rose-500/5 to-transparent border border-rose-500/30 rounded-xl p-5">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-white text-lg font-bold">#${escapeHtml(String(t.id))}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase ${prioClass}">${escapeHtml(t.prioridad)}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-500/10 text-amber-500">${escapeHtml(t.estado)}</span>
                                </div>
                                <div class="text-xs text-slate-500">${escapeHtml(t.tipo)} · Reportado por ${escapeHtml(t.reportado_por_nombre || t.reportado_por)} · ${escapeHtml(t.creado_en)}</div>
                            </div>
                        </div>
                        <p class="text-sm text-slate-300 mb-4 leading-relaxed">${escapeHtml(t.descripcion)}</p>
                        ${puedeResolver ? `
                            <div class="flex items-center gap-2 pt-3 border-t border-slate-800">
                                <input type="text" id="notaResolucion" placeholder="Nota de resolución (opcional)"
                                    class="flex-1 bg-[#1e293b] border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                                <button onclick="resolverTicket(${Number(t.id)})" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-5 py-2 rounded-lg transition-all flex items-center gap-2 shadow-lg shadow-emerald-600/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    Resolver
                                </button>
                            </div>
                        ` : `
                            <div class="text-[10px] text-slate-500 italic pt-3 border-t border-slate-800">
                                Solo ADMIN o TÉCNICO pueden resolver tickets.
                            </div>
                        `}
                    </div>
                </div>`;
            } else if (['MANTENIMIENTO', 'FUERA_SERVICIO'].includes(pc.estado)) {
                html += `<div class="bg-amber-500/5 border border-amber-500/30 rounded-xl p-4 text-sm text-amber-400">
                    La PC está en ${escapeHtml(estadoInfo.label.toLowerCase())} pero no hay ticket activo registrado.
                </div>`;
            } else {
                html += `<div>
                    <h4 class="text-[10px] uppercase tracking-widest font-bold text-slate-500 mb-3">Tickets</h4>
                    <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-4 text-sm text-emerald-400 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Sin incidencias activas.
                    </div>
                </div>`;
            }

            // --- Historial de tickets ---
            const historial = Array.isArray(pc.historial_tickets) ? pc.historial_tickets : [];
            if (historial.length > 0) {
                const ESTADO_HIST = {
                    'RESUELTO': { label: 'Resuelto', color: 'bg-emerald-500/10 text-emerald-500', dot: 'bg-emerald-500' },
                    'CERRADO':  { label: 'Cerrado',  color: 'bg-slate-500/10 text-slate-400',  dot: 'bg-slate-500' },
                };
                html += `<div>
                    <h4 class="text-[10px] uppercase tracking-widest font-bold text-slate-500 mb-3">Historial de tickets (${historial.length})</h4>
                    <div class="relative pl-6 space-y-3">
                        <div class="absolute left-2 top-2 bottom-2 w-px bg-slate-800"></div>`;
                historial.forEach(t => {
                    const info = ESTADO_HIST[t.estado] || ESTADO_HIST.CERRADO;
                    const prioClass = PRIORIDAD_BADGE[t.prioridad] || 'bg-slate-500/10 text-slate-400';
                    const fecha = t.cerrado_en || t.creado_en || '—';
                    html += `<div class="relative">
                        <span class="absolute -left-[18px] top-2 w-2.5 h-2.5 rounded-full ${info.dot} ring-4 ring-[#0f172a]"></span>
                        <div class="bg-[#1e293b] rounded-xl p-4 border border-slate-700">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-white text-sm font-bold">#${escapeHtml(String(t.id))}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase ${prioClass}">${escapeHtml(t.prioridad)}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase ${info.color}">${info.label}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-800 text-slate-400">${escapeHtml(t.tipo)}</span>
                                </div>
                                <span class="text-[10px] text-slate-500 font-mono whitespace-nowrap">${escapeHtml(fecha)}</span>
                            </div>
                            <p class="text-xs text-slate-300 leading-relaxed">${escapeHtml(t.descripcion)}</p>
                            ${t.nota_resolucion ? `<p class="text-[11px] text-emerald-400 mt-2 pt-2 border-t border-slate-800"><span class="text-slate-500 uppercase tracking-wider font-bold">Resolución:</span> ${escapeHtml(t.nota_resolucion)}</p>` : ''}
                            <p class="text-[10px] text-slate-500 mt-2">Reportado por ${escapeHtml(t.reportado_por_nombre || t.reportado_por)}${t.resuelto_por ? ' · Resuelto por ' + escapeHtml(t.resuelto_por_nombre || t.resuelto_por) : ''}</p>
                        </div>
                    </div>`;
                });
                html += '</div></div>';
            }

            document.getElementById('pcModalBody').innerHTML = html;
        }

        const cpuIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M15 2v2"/><path d="M15 20v2"/><path d="M2 15h2"/><path d="M2 9h2"/><path d="M20 15h2"/><path d="M20 9h2"/><path d="M9 2v2"/><path d="M9 20v2"/></svg>';
        const ramIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 19V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14"/><path d="M3 19h18"/><path d="M7 10v3"/><path d="M11 10v3"/><path d="M15 10v3"/></svg>';
        const osIcon  = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>';
        const mbIcon  = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 7h10v10H7z"/><path d="M7 12h10"/><path d="M12 7v10"/></svg>';

        function specRow(label, value, icon) {
            return `<div class="flex items-center gap-3">
                <span class="text-slate-500">${icon}</span>
                <div class="flex-1 flex items-center justify-between">
                    <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">${escapeHtml(label)}</span>
                    <span class="text-sm text-white font-medium">${escapeHtml(value)}</span>
                </div>
            </div>`;
        }

        async function resolverTicket(ticketId) {
            if (!['ADMIN', 'TECNICO'].includes(user.rol)) {
                toast('Tu rol no puede resolver tickets.', 'error');
                return;
            }

            const nota = document.getElementById('notaResolucion')?.value.trim() || '';
            if (!confirm(`¿Resolver el ticket #${ticketId}? La PC volverá a estado OPERATIVA.`)) return;

            try {
                const res = await fetch('../backend/tickets.php', {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ id: ticketId, estado: 'RESUELTO', nota_resolucion: nota }),
                });
                const result = await res.json();
                if (result.success) {
                    toast(result.message);
                    if (currentPcModalId) openPcModal(currentPcModalId);
                    if (selectedAula) renderStatusGrid();
                } else {
                    toast('Error: ' + result.message, 'error');
                }
            } catch (err) {
                console.error('Error resolviendo ticket:', err);
                toast('Error de conexión al resolver el ticket.', 'error');
            }
        }

        document.getElementById('pcModal').addEventListener('click', (e) => {
            if (e.target.id === 'pcModal') closePcModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const pcM = document.getElementById('pcModal');
                const aiM = document.getElementById('aulaInfoModal');
                if (pcM && !pcM.classList.contains('hidden')) closePcModal();
                else if (aiM && !aiM.classList.contains('hidden')) closeAulaInfoModal();
            }
        });

        // ============================================================
        // MODAL: Info del Aula (read-only)
        // ============================================================
        async function openAulaInfoModal() {
            if (!selectedAula) {
                toast('Primero elegí un aula.', 'error');
                return;
            }
            const modal = document.getElementById('aulaInfoModal');
            const body  = document.getElementById('aulaInfoBody');
            document.getElementById('aulaInfoTitle').textContent = 'Aula ' + selectedAula;
            document.getElementById('aulaInfoSubtitle').textContent = 'Cargando info…';
            body.innerHTML = '<div class="text-center text-slate-500 py-8">Cargando…</div>';
            modal.classList.remove('hidden');

            try {
                const res = await fetch('../backend/aulas_info.php?id_aula=' + encodeURIComponent(selectedAula), { credentials: 'include' });
                if (res.status === 401) { window.location.href = 'index.php'; return; }
                if (!res.ok) {
                    const err = await res.json();
                    throw new Error(err.error || err.message || 'Error al cargar la info del aula');
                }
                renderAulaInfoModal(await res.json());
            } catch (err) {
                body.innerHTML = `<div class="text-center text-rose-500 py-8">${escapeHtml(err.message)}</div>`;
            }
        }

        function closeAulaInfoModal() {
            document.getElementById('aulaInfoModal').classList.add('hidden');
        }

        function renderAulaInfoModal(info) {
            document.getElementById('aulaInfoTitle').textContent = info.aula_nombre || 'Aula ' + info.id_aula;
            document.getElementById('aulaInfoSubtitle').textContent =
                info.actualizado_en ? 'Última actualización: ' + info.actualizado_en : '—';

            let html = '';

            // --- Access Point ---
            html += `<div>
                <h4 class="text-[10px] uppercase tracking-widest font-bold text-slate-500 mb-3">Access Point</h4>
                <div class="bg-[#1e293b] rounded-xl px-4 py-3 border border-slate-700 flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-400"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
                    <span class="text-white font-mono text-sm">${info.access_point ? escapeHtml(info.access_point) : '<span class=\"text-slate-500 italic\">No registrado</span>'}</span>
                </div>
            </div>`;

            // --- Contraseñas ---
            html += `<div>
                <h4 class="text-[10px] uppercase tracking-widest font-bold text-slate-500 mb-3">Contraseñas</h4>`;
            if (info.passwords && Object.keys(info.passwords).length > 0) {
                html += '<div class="bg-[#1e293b] rounded-xl border border-slate-700 divide-y divide-slate-800">';
                for (const [cuenta, valor] of Object.entries(info.passwords)) {
                    html += `<div class="flex items-center justify-between px-4 py-3">
                        <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">${escapeHtml(cuenta)}</span>
                        <span class="text-sm text-white font-mono">${valor ? escapeHtml(valor) : '<span class=\"text-slate-500 italic\">—</span>'}</span>
                    </div>`;
                }
                html += '</div>';
            } else if (info.passwords_ocultas) {
                html += '<div class="bg-amber-500/5 border border-amber-500/30 rounded-xl p-4 text-sm text-amber-400">Solo ADMIN o TÉCNICO pueden ver las contraseñas.</div>';
            } else {
                html += '<div class="text-slate-500 text-sm italic">Sin contraseñas registradas.</div>';
            }
            html += '</div>';

            // --- Software ---
            const sw = info.software || {};
            const instalados    = Array.isArray(sw.instalados)    ? sw.instalados    : [];
            const noInstalados  = Array.isArray(sw.no_instalados) ? sw.no_instalados : [];
            html += `<div>
                <h4 class="text-[10px] uppercase tracking-widest font-bold text-slate-500 mb-3">Software</h4>`;
            if (instalados.length === 0 && noInstalados.length === 0) {
                html += '<div class="text-slate-500 text-sm italic">Sin software registrado.</div>';
            } else {
                if (instalados.length > 0) {
                    html += `<div class="mb-3">
                        <div class="text-xs text-emerald-500 font-bold uppercase tracking-wider mb-2">✓ Instalado (${instalados.length})</div>
                        <div class="flex flex-wrap gap-2">`;
                    instalados.forEach(s => {
                        html += `<span class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 rounded-lg text-xs font-medium">${escapeHtml(s)}</span>`;
                    });
                    html += '</div></div>';
                }
                if (noInstalados.length > 0) {
                    html += `<div>
                        <div class="text-xs text-rose-500 font-bold uppercase tracking-wider mb-2">✕ No instalado (${noInstalados.length})</div>
                        <div class="flex flex-wrap gap-2">`;
                    noInstalados.forEach(s => {
                        html += `<span class="px-2.5 py-1 bg-rose-500/10 text-rose-400 rounded-lg text-xs font-medium line-through opacity-70">${escapeHtml(s)}</span>`;
                    });
                    html += '</div></div>';
                }
            }
            html += '</div>';

            document.getElementById('aulaInfoBody').innerHTML = html;
        }

        document.getElementById('aulaInfoModal').addEventListener('click', (e) => {
            if (e.target.id === 'aulaInfoModal') closeAulaInfoModal();
        });

        // ============================================================
        // ESTADÍSTICAS (solo ADMIN + TECNICO)
        // ============================================================
        const chartInstances = {};

        function formatearHoras(h) {
            if (h === null || h === undefined) return '—';
            if (h < 1) return Math.round(h * 60) + ' min';
            if (h < 48) return h.toFixed(1) + ' h';
            return (h / 24).toFixed(1) + ' d';
        }

        async function cargarEstadisticas() {
            if (!document.getElementById('kpiCards')) return;
            try {
                const res = await fetch('../backend/estadisticas.php', { credentials: 'include' });
                if (res.status === 401) { window.location.href = 'index.php'; return; }
                if (res.status === 403) return; // rol insuficiente, silencioso
                const data = await res.json();
                renderKpis(data.kpis);
                renderChartTicketsAula(data.tickets_por_aula);
                renderChartEstadoPc(data.estado_pcs);
                renderChartResolucion(data.resolucion_semanal);
                renderTopPcs(data.top_pcs);
            } catch (err) {
                console.error('Error cargando estadísticas:', err);
            }
        }

        function renderKpis(k) {
            document.getElementById('kpiAbiertos').textContent   = k.tickets_abiertos;
            document.getElementById('kpiResueltos').textContent  = k.resueltos_ultima_semana;
            document.getElementById('kpiPcOperativas').textContent = k.pcs_operativas;
            document.getElementById('kpiPcTotal').textContent    = '/ ' + k.pcs_total;
            document.getElementById('kpiPcPct').textContent      = k.pcs_operativas_pct + '% operativas';
            document.getElementById('kpiTiempoMedio').textContent = formatearHoras(k.tiempo_medio_resolucion_horas);
        }

        const COLOR_ESTADO_PC = {
            'OPERATIVA':      '#10b981',
            'MANTENIMIENTO':  '#f59e0b',
            'FUERA_SERVICIO': '#f43f5e',
            'HIBERNANDO':     '#64748b',
            'ALERTA':         '#22d3ee',
        };

        function renderChartTicketsAula(data) {
            const aulas = [...new Set(data.map(d => d.aula))].sort();
            const abiertos    = aulas.map(a => Number(data.find(d => d.aula === a && d.estado === 'ABIERTO')?.cnt || 0));
            const enProgreso  = aulas.map(a => Number(data.find(d => d.aula === a && d.estado === 'EN_PROGRESO')?.cnt || 0));

            if (chartInstances.ticketsAula) chartInstances.ticketsAula.destroy();
            const ctx = document.getElementById('chartTicketsAula');
            chartInstances.ticketsAula = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: aulas.map(a => 'Aula ' + a),
                    datasets: [
                        { label: 'Abiertos',    data: abiertos,   backgroundColor: '#f59e0b' },
                        { label: 'En progreso', data: enProgreso, backgroundColor: '#3b82f6' },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: '#cbd5e1' } } },
                    scales: {
                        x: { stacked: true, ticks: { color: '#94a3b8' }, grid: { color: '#1e293b' } },
                        y: { stacked: true, ticks: { color: '#94a3b8', precision: 0 }, grid: { color: '#1e293b' } },
                    },
                },
            });
        }

        function renderChartEstadoPc(data) {
            const labels = data.map(d => d.estado);
            const values = data.map(d => Number(d.cnt));
            const colors = labels.map(l => COLOR_ESTADO_PC[l] || '#64748b');

            if (chartInstances.estadoPc) chartInstances.estadoPc.destroy();
            const ctx = document.getElementById('chartEstadoPc');
            chartInstances.estadoPc = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{ data: values, backgroundColor: colors, borderColor: '#0b111a', borderWidth: 2 }],
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '65%',
                    plugins: { legend: { position: 'right', labels: { color: '#cbd5e1', boxWidth: 12 } } },
                },
            });
        }

        function renderChartResolucion(data) {
            const labels = data.map(d => d.inicio_semana);
            const resueltos = data.map(d => Number(d.resueltos));
            const tiempoMedio = data.map(d => d.tiempo_medio_h !== null ? Number(d.tiempo_medio_h) : null);

            if (chartInstances.resolucion) chartInstances.resolucion.destroy();
            const ctx = document.getElementById('chartResolucion');
            chartInstances.resolucion = new Chart(ctx, {
                data: {
                    labels,
                    datasets: [
                        {
                            type: 'bar', label: 'Tickets resueltos', data: resueltos,
                            backgroundColor: '#10b981', yAxisID: 'y',
                        },
                        {
                            type: 'line', label: 'Tiempo medio (h)', data: tiempoMedio,
                            borderColor: '#22d3ee', backgroundColor: '#22d3ee',
                            tension: 0.3, yAxisID: 'y1',
                        },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: '#cbd5e1' } } },
                    scales: {
                        x:  { ticks: { color: '#94a3b8' }, grid: { color: '#1e293b' } },
                        y:  { position: 'left',  ticks: { color: '#10b981', precision: 0 }, grid: { color: '#1e293b' }, title: { display: true, text: 'Resueltos', color: '#10b981' } },
                        y1: { position: 'right', ticks: { color: '#22d3ee' }, grid: { drawOnChartArea: false }, title: { display: true, text: 'Horas promedio', color: '#22d3ee' } },
                    },
                },
            });
        }

        function renderTopPcs(rows) {
            const tbody = document.getElementById('topPcsBody');
            if (!Array.isArray(rows) || rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">Sin datos de incidencias aún.</td></tr>';
                return;
            }
            tbody.innerHTML = rows.map((r, i) => `
                <tr class="hover:bg-slate-800/30 transition-colors">
                    <td class="px-6 py-4 text-sm font-bold text-slate-500">#${i + 1}</td>
                    <td class="px-6 py-4">
                        <button onclick="openPcModal('${r.id_pc.replace(/'/g, "\\'")}')" class="text-sm text-white font-bold hover:text-blue-500 transition-colors">${escapeHtml(r.id_pc)}</button>
                        <div class="text-[10px] text-slate-500">${escapeHtml(r.nombre || '')}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-400">Aula ${escapeHtml(r.id_aula)}</td>
                    <td class="px-6 py-4 text-right">
                        <span class="px-3 py-1 rounded-lg bg-rose-500/10 text-rose-400 text-sm font-bold">${escapeHtml(String(r.incidencias))}</span>
                    </td>
                </tr>
            `).join('');
        }

        // Auto-cargar al entrar a dashboard
        if (document.getElementById('kpiCards')) {
            // Esperar a Chart.js (cargado por CDN con defer implícito)
            const waitForChart = () => {
                if (typeof Chart !== 'undefined') cargarEstadisticas();
                else setTimeout(waitForChart, 100);
            };
            waitForChart();
        }
    </script>
</body>
</html>
