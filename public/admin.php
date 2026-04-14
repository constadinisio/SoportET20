<?php

declare(strict_types=1);

$requireAdmin = true;
require __DIR__ . '/partials/auth_guard.php';

$pageTitle  = 'SoportET20 ET20 - Administración';
$activePage = 'aulas';
require __DIR__ . '/partials/head.php';
?>

<body class="bg-[#0b111a] text-slate-200 min-h-screen flex">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <!-- Main -->
    <div class="flex-1 flex flex-col">
        <header class="h-20 border-b border-slate-800 flex items-center justify-between px-8 bg-[#0b111a]/50 backdrop-blur-md sticky top-0 z-10">
            <div>
                <h1 class="text-white text-xl font-bold" id="pageTitle">Gestión de Aulas</h1>
                <p class="text-xs text-slate-500">Administración de entidades del sistema</p>
            </div>
            <div class="flex items-center gap-4">
                <?php require __DIR__ . '/partials/notification_bell.php'; ?>
                <button id="btnCreate" onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-5 py-2.5 rounded-xl transition-all flex items-center gap-2 shadow-lg shadow-blue-600/20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14" />
                        <path d="M12 5v14" />
                    </svg>
                    <span id="btnCreateLabel">Nueva Aula</span>
                </button>
            </div>
        </header>

        <main class="flex-1 p-8 overflow-y-auto">
            <div id="toast" class="hidden fixed top-24 right-8 z-50 px-5 py-3 rounded-xl shadow-2xl font-bold text-sm"></div>

            <!-- Tab: Aulas -->
            <div id="tab-aulas" class="tab-content">
                <div class="bg-[#0f172a] border border-slate-800 rounded-2xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-800 text-[10px] uppercase font-bold text-slate-500 tracking-widest">
                                <th class="px-6 py-4">ID</th>
                                <th class="px-6 py-4">Nombre</th>
                                <th class="px-6 py-4">Piso</th>
                                <th class="px-6 py-4">Capacidad</th>
                                <th class="px-6 py-4">PCs</th>
                                <th class="px-6 py-4">Estado</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="aulasBody" class="divide-y divide-slate-800"></tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: PCs -->
            <div id="tab-pcs" class="tab-content hidden">
                <div class="flex items-center gap-3 mb-4">
                    <label class="text-slate-400 text-xs font-bold uppercase">Filtrar por aula:</label>
                    <select id="pcFilterAula" onchange="renderPcs()" class="bg-[#0f172a] border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="">Todas</option>
                    </select>
                </div>
                <div class="bg-[#0f172a] border border-slate-800 rounded-2xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-800 text-[10px] uppercase font-bold text-slate-500 tracking-widest">
                                <th class="px-6 py-4">ID</th>
                                <th class="px-6 py-4">Aula</th>
                                <th class="px-6 py-4">Nombre</th>
                                <th class="px-6 py-4">Estado</th>
                                <th class="px-6 py-4">IP</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="pcsBody" class="divide-y divide-slate-800"></tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: Usuarios -->
            <div id="tab-usuarios" class="tab-content hidden">
                <div class="bg-[#0f172a] border border-slate-800 rounded-2xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-800 text-[10px] uppercase font-bold text-slate-500 tracking-widest">
                                <th class="px-6 py-4">Usuario</th>
                                <th class="px-6 py-4">Nombre</th>
                                <th class="px-6 py-4">Rol</th>
                                <th class="px-6 py-4">Estado</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="usuariosBody" class="divide-y divide-slate-800"></tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: Auditoría -->
            <div id="tab-auditoria" class="tab-content hidden">
                <div class="bg-[#0f172a] border border-slate-800 rounded-2xl p-5 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                        <div>
                            <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Usuario</label>
                            <select id="auditFiltroUsuario" class="w-full bg-[#1e293b] border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">Todos</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Acción</label>
                            <input type="text" id="auditFiltroAccion" placeholder="Ej: LOGIN, TICKET..." class="w-full bg-[#1e293b] border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Desde</label>
                            <input type="date" id="auditFiltroDesde" class="w-full bg-[#1e293b] border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Hasta</label>
                            <input type="date" id="auditFiltroHasta" class="w-full bg-[#1e293b] border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div class="flex gap-2">
                            <button onclick="aplicarFiltrosAudit()" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-bold px-4 py-2 rounded-lg text-sm transition-all">Filtrar</button>
                            <button onclick="limpiarFiltrosAudit()" class="px-3 py-2 text-slate-400 hover:text-white border border-slate-700 hover:border-slate-500 rounded-lg text-sm transition-all" title="Limpiar filtros">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 6h18" />
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-[#0f172a] border border-slate-800 rounded-2xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-800 text-[10px] uppercase font-bold text-slate-500 tracking-widest">
                                <th class="px-6 py-4 w-12">#</th>
                                <th class="px-6 py-4">Fecha/Hora</th>
                                <th class="px-6 py-4">Usuario</th>
                                <th class="px-6 py-4">Acción</th>
                                <th class="px-6 py-4">Detalle</th>
                                <th class="px-6 py-4">IP</th>
                            </tr>
                        </thead>
                        <tbody id="auditBody" class="divide-y divide-slate-800"></tbody>
                    </table>
                </div>

                <div id="auditPagination" class="flex items-center justify-between mt-4 text-sm text-slate-400"></div>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="modal" class="hidden fixed inset-0 z-40 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div id="modalCard" class="bg-[#0f172a] border border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between p-6 border-b border-slate-800 flex-shrink-0">
                <h3 id="modalTitle" class="text-white text-lg font-bold">Nuevo</h3>
                <button onclick="closeModal()" class="text-slate-500 hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>
            <form id="modalForm" class="flex flex-col flex-1 overflow-hidden">
                <div class="p-6 overflow-y-auto flex-1">
                    <div id="modalFields" class="space-y-4"></div>
                    <div id="modalError" class="hidden text-rose-500 text-sm mt-2"></div>
                </div>
                <div class="flex items-center justify-end gap-3 p-4 border-t border-slate-800 bg-[#0f172a] flex-shrink-0">
                    <button type="button" onclick="closeModal()" class="px-5 py-2.5 text-slate-400 font-bold hover:text-white transition-colors">Cancelar</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-6 py-2.5 rounded-xl transition-all shadow-lg shadow-blue-600/20">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <?php require __DIR__ . '/partials/scripts_common.php'; ?>

    <script>
        const user = window.CURRENT_USER;
        let aulas = [];
        let pcs = [];
        let usuarios = [];
        let currentTab = 'aulas';
        let modalContext = null;

        (function init() {
            const hashTab = window.location.hash.replace('#', '');
            const validTabs = ['aulas', 'pcs', 'usuarios', 'auditoria'];
            showTab(validTabs.includes(hashTab) ? hashTab : 'aulas');
        })();

        async function apiFetch(url, options = {}) {
            const res = await fetch(url, {
                credentials: 'include',
                ...options
            });
            if (res.status === 401) {
                window.location.href = 'index.php';
                throw new Error('No autorizado');
            }
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || data.error || 'Error en la solicitud');
            return data;
        }

        function toast(message, type = 'success') {
            const el = document.getElementById('toast');
            el.textContent = message;
            el.className = 'fixed top-24 right-8 z-50 px-5 py-3 rounded-xl shadow-2xl font-bold text-sm ' +
                (type === 'success' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white');
            el.classList.remove('hidden');
            setTimeout(() => el.classList.add('hidden'), 3500);
        }

        function showTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab-content').forEach(e => e.classList.add('hidden'));
            document.getElementById('tab-' + tab).classList.remove('hidden');
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('text-blue-500', 'bg-blue-600/10', 'font-bold');
                b.classList.add('text-slate-400');
            });
            const active = document.getElementById('btn-' + tab);
            if (active) {
                active.classList.add('text-blue-500', 'bg-blue-600/10', 'font-bold');
                active.classList.remove('text-slate-400');
            }

            const labels = {
                aulas: 'Gestión de Aulas',
                pcs: 'Gestión de Computadoras',
                usuarios: 'Gestión de Usuarios',
                auditoria: 'Registro de Auditoría'
            };
            const createLabels = {
                aulas: 'Nueva Aula',
                pcs: 'Nueva PC',
                usuarios: 'Nuevo Usuario',
                auditoria: ''
            };
            document.getElementById('pageTitle').textContent = labels[tab];

            const btnCreate = document.getElementById('btnCreate');
            if (tab === 'auditoria') {
                btnCreate.classList.add('hidden');
            } else {
                btnCreate.classList.remove('hidden');
                document.getElementById('btnCreateLabel').textContent = createLabels[tab];
            }

            if (tab === 'aulas') loadAulas();
            else if (tab === 'pcs') loadPcs();
            else if (tab === 'usuarios') loadUsuarios();
            else if (tab === 'auditoria') loadAudit();
        }

        // --- AULAS ---
        async function loadAulas() {
            try {
                aulas = await apiFetch('../backend/aulas.php?incluir_inactivas=1');
                renderAulas();
            } catch (err) {
                toast(err.message, 'error');
            }
        }

        function renderAulas() {
            const tbody = document.getElementById('aulasBody');
            if (aulas.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">No hay aulas registradas.</td></tr>`;
                return;
            }
            tbody.innerHTML = aulas.map(a => `
        <tr class="hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-4 text-sm font-bold text-white">${escapeHtml(a.id)}</td>
            <td class="px-6 py-4 text-sm text-slate-300">${escapeHtml(a.nombre)}</td>
            <td class="px-6 py-4 text-sm text-slate-400">${escapeHtml(String(a.piso))}</td>
            <td class="px-6 py-4 text-sm text-slate-400">${escapeHtml(String(a.capacidad_pcs))}</td>
            <td class="px-6 py-4 text-sm text-slate-400">
                <span class="text-emerald-500 font-bold">${escapeHtml(String(a.pcs_operativas || 0))}</span> /
                <span class="text-white">${escapeHtml(String(a.total_pcs || 0))}</span>
            </td>
            <td class="px-6 py-4">
                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase ${Number(a.activa) ? 'bg-emerald-500/10 text-emerald-500' : 'bg-slate-500/10 text-slate-400'}">
                    ${Number(a.activa) ? 'Activa' : 'Inactiva'}
                </span>
            </td>
            <td class="px-6 py-4 text-right">
                <button onclick='openEditModal("aulas", ${JSON.stringify(a)})' class="text-blue-500 hover:text-blue-400 text-xs font-bold mr-3">Editar</button>
                <button onclick='openAulaInfoModal(${JSON.stringify(a.id)}, ${JSON.stringify(a.nombre)})' class="text-purple-400 hover:text-purple-300 text-xs font-bold mr-3">Info</button>
                ${Number(a.activa) ? `<button onclick='confirmDelete("aulas", ${JSON.stringify(a.id)})' class="text-rose-500 hover:text-rose-400 text-xs font-bold">Desactivar</button>` : ''}
            </td>
        </tr>
    `).join('');
        }

        // --- PCs ---
        async function loadPcs() {
            try {
                if (aulas.length === 0) aulas = await apiFetch('../backend/aulas.php?incluir_inactivas=1');
                pcs = await apiFetch('../backend/computers.php');

                const select = document.getElementById('pcFilterAula');
                select.innerHTML = '<option value="">Todas</option>' +
                    aulas.filter(a => Number(a.activa)).map(a => `<option value="${escapeHtml(a.id)}">${escapeHtml(a.id)} - ${escapeHtml(a.nombre)}</option>`).join('');

                renderPcs();
            } catch (err) {
                toast(err.message, 'error');
            }
        }

        const ESTADO_BADGE = {
            'OPERATIVA': 'bg-emerald-500/10 text-emerald-500',
            'MANTENIMIENTO': 'bg-amber-500/10 text-amber-500',
            'FUERA_SERVICIO': 'bg-rose-500/10 text-rose-500',
            'HIBERNANDO': 'bg-slate-500/10 text-slate-400',
            'ALERTA': 'bg-cyan-500/10 text-cyan-400',
        };

        function renderPcs() {
            const filter = document.getElementById('pcFilterAula').value;
            const visible = filter ? pcs.filter(p => p.id_aula === filter) : pcs;
            const tbody = document.getElementById('pcsBody');

            if (visible.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">No hay PCs para mostrar.</td></tr>`;
                return;
            }

            tbody.innerHTML = visible.map(p => `
        <tr class="hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-4 text-sm font-bold text-white">${escapeHtml(p.id)}</td>
            <td class="px-6 py-4 text-sm text-slate-300">${escapeHtml(p.id_aula)}</td>
            <td class="px-6 py-4 text-sm text-slate-400">${escapeHtml(p.nombre || '-')}</td>
            <td class="px-6 py-4">
                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase ${ESTADO_BADGE[p.estado] || ''}">${escapeHtml(p.estado)}</span>
            </td>
            <td class="px-6 py-4 text-xs text-slate-500 font-mono">${escapeHtml(p.ip || '-')}</td>
            <td class="px-6 py-4 text-right">
                <button onclick='openEditModal("pcs", ${JSON.stringify(p)})' class="text-blue-500 hover:text-blue-400 text-xs font-bold mr-3">Editar</button>
                <button onclick='confirmDelete("pcs", ${JSON.stringify(p.id)})' class="text-rose-500 hover:text-rose-400 text-xs font-bold">Eliminar</button>
            </td>
        </tr>
    `).join('');
        }

        // --- USUARIOS ---
        async function loadUsuarios() {
            try {
                usuarios = await apiFetch('../backend/usuarios.php?incluir_inactivos=1');
                renderUsuarios();
            } catch (err) {
                toast(err.message, 'error');
            }
        }

        function renderUsuarios() {
            const tbody = document.getElementById('usuariosBody');
            if (usuarios.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">No hay usuarios.</td></tr>`;
                return;
            }
            tbody.innerHTML = usuarios.map(u => `
        <tr class="hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-4 text-sm font-bold text-white">${escapeHtml(u.usuario)}</td>
            <td class="px-6 py-4 text-sm text-slate-300">${escapeHtml(u.nombre_completo)}</td>
            <td class="px-6 py-4">
                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase ${u.rol === 'ADMIN' ? 'bg-blue-500/10 text-blue-400' : u.rol === 'TECNICO' ? 'bg-purple-500/10 text-purple-400' : 'bg-slate-500/10 text-slate-400'}">${escapeHtml(u.rol)}</span>
            </td>
            <td class="px-6 py-4">
                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase ${Number(u.activo) ? 'bg-emerald-500/10 text-emerald-500' : 'bg-rose-500/10 text-rose-400'}">${Number(u.activo) ? 'Activo' : 'Inactivo'}</span>
            </td>
            <td class="px-6 py-4 text-right">
                <button onclick='openEditModal("usuarios", ${JSON.stringify(u)})' class="text-blue-500 hover:text-blue-400 text-xs font-bold mr-3">Editar</button>
                ${u.id !== user.id && Number(u.activo) ? `<button onclick='confirmDelete("usuarios", ${Number(u.id)})' class="text-rose-500 hover:text-rose-400 text-xs font-bold">Desactivar</button>` : ''}
            </td>
        </tr>
    `).join('');
        }

        // --- MODAL helpers ---
        function inputField(label, name, value = '', type = 'text', attrs = '') {
            return `
        <div class="space-y-2">
            <label class="text-slate-300 text-xs font-bold uppercase tracking-wider">${escapeHtml(label)}</label>
            <input type="${type}" name="${name}" value="${escapeHtml(value)}" ${attrs}
                class="w-full bg-[#1e293b] border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        </div>`;
        }

        function selectField(label, name, options, value = '') {
            const opts = options.map(o => {
                const v = typeof o === 'object' ? o.value : o;
                const l = typeof o === 'object' ? o.label : o;
                return `<option value="${escapeHtml(v)}" ${String(v) === String(value) ? 'selected' : ''}>${escapeHtml(l)}</option>`;
            }).join('');
            return `
        <div class="space-y-2">
            <label class="text-slate-300 text-xs font-bold uppercase tracking-wider">${escapeHtml(label)}</label>
            <select name="${name}" class="w-full bg-[#1e293b] border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none">${opts}</select>
        </div>`;
        }

        function openCreateModal() {
            openModal(currentTab, 'create');
        }

        function openEditModal(entity, data) {
            openModal(entity, 'edit', data);
        }

        function openModal(entity, mode, data = {}) {
            modalContext = {
                entity,
                mode,
                data
            };

            const titles = {
                aulas: {
                    create: 'Nueva Aula',
                    edit: 'Editar Aula'
                },
                pcs: {
                    create: 'Nueva PC',
                    edit: 'Editar PC'
                },
                usuarios: {
                    create: 'Nuevo Usuario',
                    edit: 'Editar Usuario'
                },
                aula_info: {
                    create: 'Info del Aula',
                    edit: 'Info del Aula'
                },
            };

            document.getElementById('modalTitle').textContent =
                titles[entity]?.[mode] || 'Detalle';

            document.getElementById('modalError').classList.add('hidden');

            const card = document.getElementById('modalCard');
            card.classList.remove('max-w-lg', 'max-w-2xl', 'max-w-3xl');
            card.classList.add((entity === 'pcs' || entity === 'aula_info') ? 'max-w-2xl' : 'max-w-lg');
            if (entity === 'pcs') {
                card.classList.remove('max-w-2xl');
                card.classList.add('max-w-3xl');
            }

            const fields = document.getElementById('modalFields');

            if (entity === 'aulas') {
                fields.innerHTML =
                    inputField('ID (ej: 101)', 'id', data.id || '', 'text', mode === 'edit' ? 'readonly disabled' : 'required maxlength="10"') +
                    inputField('Nombre', 'nombre', data.nombre || '', 'text', 'required maxlength="100"') +
                    inputField('Piso', 'piso', data.piso ?? 0, 'number', 'min="0"') +
                    inputField('Capacidad de PCs', 'capacidad_pcs', data.capacidad_pcs ?? 0, 'number', 'min="0"') +
                    (mode === 'edit' ? selectField('Estado', 'activa', [{
                        value: 1,
                        label: 'Activa'
                    }, {
                        value: 0,
                        label: 'Inactiva'
                    }], data.activa) : '');
            } else if (entity === 'pcs') {
                const aulaOpts = aulas.filter(a => Number(a.activa)).map(a => ({
                    value: a.id,
                    label: `${a.id} - ${a.nombre}`
                }));
                const specs = (data.specs && typeof data.specs === 'object') ? data.specs : {};

                fields.innerHTML =
                    `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${inputField('ID (ej: 101-05)', 'id', data.id || '', 'text', mode === 'edit' ? 'readonly disabled' : 'required maxlength="20"')}
                ${selectField('Aula', 'id_aula', aulaOpts, data.id_aula || '')}
            </div>` +
                    `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${inputField('Nombre', 'nombre', data.nombre || '', 'text', 'maxlength="50"')}
                ${selectField('Estado', 'estado', ['OPERATIVA','MANTENIMIENTO','FUERA_SERVICIO','HIBERNANDO','ALERTA'], data.estado || 'OPERATIVA')}
            </div>` +
                    `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${inputField('IP', 'ip', data.ip || '', 'text', 'maxlength="45"')}
                ${inputField('MAC', 'mac', data.mac || '', 'text', 'maxlength="17"')}
            </div>` +
                    inputField('Observaciones', 'observaciones', data.observaciones || '') +
                    `<div class="border-t border-slate-800 mt-6 pt-6 mb-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/></svg>
                    <h4 class="text-white text-sm font-bold uppercase tracking-wider">Especificaciones Técnicas</h4>
                </div>
                <p class="text-xs text-slate-500 mt-1">Opcional. Dejá vacío lo que no apliques.</p>
            </div>` +
                    `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${inputField('CPU', 'spec_cpu', specs.cpu || '', 'text', 'maxlength="100" placeholder="Intel Core i5-10400"')}
                ${inputField('RAM', 'spec_ram', specs.ram || '', 'text', 'maxlength="50" placeholder="8 GB DDR4"')}
                ${inputField('Sistema Operativo', 'spec_os', specs.os || '', 'text', 'maxlength="100" placeholder="Windows 11 Pro"')}
                ${inputField('Placa Madre', 'spec_placa', specs.placa || '', 'text', 'maxlength="100" placeholder="ASUS H410M-E"')}
            </div>` +
                    `<div class="space-y-2 mb-4">
                <div class="flex items-center justify-between">
                    <label class="text-slate-300 text-xs font-bold uppercase tracking-wider">Discos</label>
                    <button type="button" onclick="addDiscoRow()" class="text-xs font-bold text-blue-500 hover:text-blue-400 flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        Agregar disco
                    </button>
                </div>
                <div id="discosContainer" class="space-y-2"></div>
            </div>`;

                setTimeout(() => {
                    const discos = Array.isArray(specs.discos) ? specs.discos : [];
                    if (discos.length === 0) {
                        addDiscoRow();
                    } else {
                        discos.forEach(d => addDiscoRow(d));
                    }
                }, 0);
            } else if (entity === 'usuarios') {
                const rolOpts = [{
                        value: 'PROFESOR',
                        label: 'Profesor'
                    },
                    {
                        value: 'TECNICO',
                        label: 'Técnico'
                    },
                    {
                        value: 'ADMIN',
                        label: 'Administrador'
                    },
                ];

                fields.innerHTML =
                    inputField('Nombre de usuario', 'usuario', data.usuario || '', 'text', mode === 'edit' ? 'readonly disabled' : 'required minlength="3" maxlength="50"') +
                    inputField('Nombre completo', 'nombre_completo', data.nombre_completo || '', 'text', 'required maxlength="100"') +
                    selectField('Rol', 'rol', rolOpts, data.rol || 'PROFESOR') +
                    inputField(mode === 'create' ? 'Clave' : 'Nueva clave (dejar vacío para no cambiar)', 'clave', '', 'password', mode === 'create' ? 'required minlength="6"' : 'minlength="6"') +
                    (mode === 'edit' ? selectField('Estado', 'activo', [{
                        value: 1,
                        label: 'Activo'
                    }, {
                        value: 0,
                        label: 'Inactivo'
                    }], data.activo) : '');
            } else if (entity === 'aula_info') {
                const passwords = (data.passwords && typeof data.passwords === 'object' && !Array.isArray(data.passwords)) ?
                    data.passwords : {};

                const rawSoftware = (data.software && typeof data.software === 'object') ?
                    data.software : {};

                const normalizarLista = (valor) => {
                    if (Array.isArray(valor)) return valor;

                    if (typeof valor === 'string') {
                        const texto = valor.trim();

                        if (!texto) return [];

                        // Si viene como JSON stringificado: ["A","B","C"]
                        if (
                            (texto.startsWith('[') && texto.endsWith(']')) ||
                            (texto.startsWith('{') && texto.endsWith('}'))
                        ) {
                            try {
                                const parsed = JSON.parse(texto);
                                if (Array.isArray(parsed)) {
                                    return parsed.map(item => String(item).trim()).filter(Boolean);
                                }
                            } catch (e) {
                                // sigue abajo con parseo normal
                            }
                        }

                        // Si viene línea por línea o separado por comas
                        return texto
                            .split(/\r?\n|,/)
                            .map(s => s.trim().replace(/^"(.*)"$/, '$1'))
                            .filter(Boolean);
                    }

                    return [];
                };

                const sw = {
                    instalados: normalizarLista(rawSoftware.instalados),
                    no_instalados: normalizarLista(rawSoftware.no_instalados),
                };

                fields.innerHTML =
                    inputField('Access Point', 'access_point', data.access_point || '', 'text', 'maxlength="50" placeholder="Ej: RC. F19"') +
                    `<div class="border-t border-slate-800 pt-4">
            <div class="flex items-center justify-between mb-2">
                <label class="text-slate-300 text-xs font-bold uppercase tracking-wider">Contraseñas</label>
                <button type="button" onclick="addPasswordRow()" class="text-xs font-bold text-blue-500 hover:text-blue-400">+ Agregar cuenta</button>
            </div>
            <div id="passwordsContainer" class="space-y-2"></div>
            <p class="text-[10px] text-slate-500 mt-2">Ej: alumno / AlumnoET20. Dejá en blanco lo que no tengas.</p>
        </div>` +
                    `<div class="border-t border-slate-800 pt-4 space-y-2">
            <label class="text-slate-300 text-xs font-bold uppercase tracking-wider">Software instalado (uno por línea)</label>
            <textarea name="sw_instalados" rows="6" class="w-full bg-[#1e293b] border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none">${escapeHtml(sw.instalados.join('\n'))}</textarea>
        </div>` +
                    `<div class="space-y-2">
            <label class="text-slate-300 text-xs font-bold uppercase tracking-wider">Software NO instalado (opcional)</label>
            <textarea name="sw_no_instalados" rows="3" class="w-full bg-[#1e293b] border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none">${escapeHtml(sw.no_instalados.join('\n'))}</textarea>
        </div>`;

                setTimeout(() => {
                    const entries = Object.entries(passwords);
                    if (entries.length === 0) addPasswordRow();
                    else entries.forEach(([k, v]) => addPasswordRow({
                        cuenta: k,
                        valor: v ?? ''
                    }));
                }, 0);
            }

            document.getElementById('modal').classList.remove('hidden');
        }

        async function openAulaInfoModal(aulaId, aulaNombre) {
            let info;
            try {
                info = await apiFetch('../backend/aulas_info.php?id_aula=' + encodeURIComponent(aulaId));
            } catch (err) {
                toast(err.message, 'error');
                return;
            }
            openModal('aula_info', 'edit', {
                ...info,
                id_aula: aulaId,
                aula_nombre: aulaNombre
            });
            document.getElementById('modalTitle').textContent = 'Info del Aula ' + (aulaNombre || aulaId);
        }

        function addPasswordRow(pwd = {}) {
            const container = document.getElementById('passwordsContainer');
            if (!container) return;
            const row = document.createElement('div');
            row.className = 'flex gap-2 pwd-row';
            row.innerHTML = `
        <input type="text" class="pwd-cuenta bg-[#1e293b] border border-slate-700 rounded-lg px-3 py-2 text-white text-sm w-1/3" placeholder="Cuenta (ej: alumno)" value="${escapeHtml(pwd.cuenta || '')}">
        <input type="text" class="pwd-valor bg-[#1e293b] border border-slate-700 rounded-lg px-3 py-2 text-white text-sm flex-1" placeholder="Contraseña" value="${escapeHtml(pwd.valor ?? '')}">
        <button type="button" onclick="this.closest('.pwd-row').remove()" class="text-rose-500 hover:text-rose-400 p-2" title="Quitar">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
        </button>
    `;
            container.appendChild(row);
        }

        function recolectarPasswords() {
            const container = document.getElementById('passwordsContainer');
            if (!container) return null;
            const out = {};
            container.querySelectorAll('.pwd-row').forEach(row => {
                const k = row.querySelector('.pwd-cuenta').value.trim().toLowerCase();
                const v = row.querySelector('.pwd-valor').value.trim();
                if (k) out[k] = v !== '' ? v : null;
            });
            return Object.keys(out).length > 0 ? out : null;
        }

        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
            modalContext = null;
        }

        function addDiscoRow(disco = {}) {
            const container = document.getElementById('discosContainer');
            if (!container) return;
            const row = document.createElement('div');
            row.className = 'flex flex-col sm:grid sm:grid-cols-[90px_1fr_1fr_auto] gap-2 sm:items-center disco-row';
            row.innerHTML = `
        <select class="disco-tipo bg-[#1e293b] border border-slate-700 rounded-lg px-2 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="SSD"  ${disco.tipo === 'SSD'  ? 'selected' : ''}>SSD</option>
            <option value="HDD"  ${disco.tipo === 'HDD'  ? 'selected' : ''}>HDD</option>
            <option value="NVMe" ${disco.tipo === 'NVMe' ? 'selected' : ''}>NVMe</option>
        </select>
        <input type="text" class="disco-capacidad bg-[#1e293b] border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none"
               placeholder="Capacidad (ej: 500 GB)" value="${escapeHtml(disco.capacidad || '')}">
        <input type="text" class="disco-modelo bg-[#1e293b] border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none"
               placeholder="Modelo (ej: Kingston A400)" value="${escapeHtml(disco.modelo || '')}">
        <button type="button" onclick="this.closest('.disco-row').remove()" class="text-rose-500 hover:text-rose-400 p-2" title="Quitar">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </button>
    `;
            container.appendChild(row);
        }

        function recolectarDiscos() {
            const container = document.getElementById('discosContainer');
            if (!container) return [];
            return Array.from(container.querySelectorAll('.disco-row')).map(row => ({
                tipo: row.querySelector('.disco-tipo').value,
                capacidad: row.querySelector('.disco-capacidad').value.trim(),
                modelo: row.querySelector('.disco-modelo').value.trim(),
            })).filter(d => d.capacidad !== '');
        }

        document.getElementById('modalForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const errorEl = document.getElementById('modalError');
            errorEl.classList.add('hidden');

            const formData = new FormData(this);
            const payload = {};
            for (const [k, v] of formData.entries()) payload[k] = v;

            const {
                entity,
                mode,
                data
            } = modalContext;

            if (entity === 'aula_info') {
                const instalados = (payload.sw_instalados || '').split('\n').map(s => s.trim()).filter(Boolean);
                const noInstalados = (payload.sw_no_instalados || '').split('\n').map(s => s.trim()).filter(Boolean);
                const body = {
                    id_aula: data.id_aula || data.id,
                    access_point: (payload.access_point || '').trim() || null,
                    passwords: recolectarPasswords(),
                    software: (instalados.length || noInstalados.length) ? {
                        instalados,
                        no_instalados: noInstalados
                    } : null,
                };
                try {
                    await apiFetch('../backend/aulas_info.php', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(body),
                    });
                    toast('Info del aula actualizada');
                    closeModal();
                } catch (err) {
                    errorEl.textContent = err.message;
                    errorEl.classList.remove('hidden');
                }
                return;
            }

            if (entity === 'pcs') {
                const specs = {
                    cpu: (payload.spec_cpu || '').trim(),
                    ram: (payload.spec_ram || '').trim(),
                    os: (payload.spec_os || '').trim(),
                    placa: (payload.spec_placa || '').trim(),
                    discos: recolectarDiscos(),
                };
                delete payload.spec_cpu;
                delete payload.spec_ram;
                delete payload.spec_os;
                delete payload.spec_placa;

                const tieneContenido = specs.cpu || specs.ram || specs.os || specs.placa || specs.discos.length > 0;
                payload.specs = tieneContenido ? specs : null;
            }

            const endpoints = {
                aulas: '../backend/aulas.php',
                pcs: '../backend/computers.php',
                usuarios: '../backend/usuarios.php'
            };
            const url = endpoints[entity];

            try {
                if (mode === 'create') {
                    await apiFetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload),
                    });
                    toast('Creado correctamente');
                } else {
                    payload.id = data.id;
                    if (entity === 'usuarios' && payload.clave === '') delete payload.clave;

                    await apiFetch(url, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload),
                    });
                    toast('Actualizado correctamente');
                }

                closeModal();
                if (entity === 'aulas') loadAulas();
                else if (entity === 'pcs') loadPcs();
                else loadUsuarios();
            } catch (err) {
                errorEl.textContent = err.message;
                errorEl.classList.remove('hidden');
            }
        });

        async function confirmDelete(entity, id) {
            const labels = {
                aulas: 'desactivar esta aula',
                pcs: 'eliminar esta PC',
                usuarios: 'desactivar este usuario'
            };
            if (!confirm('¿Seguro que querés ' + labels[entity] + '?')) return;

            const endpoints = {
                aulas: '../backend/aulas.php',
                pcs: '../backend/computers.php',
                usuarios: '../backend/usuarios.php'
            };
            try {
                await apiFetch(endpoints[entity], {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id
                    }),
                });
                toast('Acción completada');
                if (entity === 'aulas') loadAulas();
                else if (entity === 'pcs') loadPcs();
                else loadUsuarios();
            } catch (err) {
                toast(err.message, 'error');
            }
        }

        // --- AUDITORÍA ---
        let auditState = {
            page: 1,
            limit: 50,
            filtros: {}
        };

        const ACCION_BADGE = {
            'LOGIN_EXITOSO': 'bg-emerald-500/10 text-emerald-500',
            'LOGIN_FALLIDO': 'bg-rose-500/10 text-rose-500',
            'LOGIN_USUARIO_INACTIVO': 'bg-rose-500/10 text-rose-500',
            'LOGOUT': 'bg-slate-500/10 text-slate-400',
            'TICKET_CREADO': 'bg-amber-500/10 text-amber-500',
            'TICKET_ACTUALIZADO': 'bg-blue-500/10 text-blue-400',
            'CAMBIO_ESTADO_PC': 'bg-cyan-500/10 text-cyan-400',
            'AULA_CREADA': 'bg-emerald-500/10 text-emerald-500',
            'AULA_ACTUALIZADA': 'bg-blue-500/10 text-blue-400',
            'AULA_DESACTIVADA': 'bg-rose-500/10 text-rose-500',
            'PC_CREADA': 'bg-emerald-500/10 text-emerald-500',
            'PC_ACTUALIZADA': 'bg-blue-500/10 text-blue-400',
            'PC_ELIMINADA': 'bg-rose-500/10 text-rose-500',
            'USUARIO_CREADO': 'bg-emerald-500/10 text-emerald-500',
            'USUARIO_ACTUALIZADO': 'bg-blue-500/10 text-blue-400',
            'USUARIO_DESACTIVADO': 'bg-rose-500/10 text-rose-500',
            'HEARTBEAT_CAMBIO_ESTADO': 'bg-purple-500/10 text-purple-400',
        };

        async function loadAudit() {
            const sel = document.getElementById('auditFiltroUsuario');
            if (sel && sel.options.length <= 1) {
                try {
                    const us = await apiFetch('../backend/usuarios.php?incluir_inactivos=1');
                    sel.innerHTML = '<option value="">Todos</option>' +
                        us.map(u => `<option value="${escapeHtml(String(u.id))}">${escapeHtml(u.usuario)} — ${escapeHtml(u.nombre_completo)}</option>`).join('');
                } catch {
                    /* silencioso */
                }
            }
            await fetchAudit();
        }

        function aplicarFiltrosAudit() {
            auditState.page = 1;
            auditState.filtros = {
                id_usuario: document.getElementById('auditFiltroUsuario').value,
                accion: document.getElementById('auditFiltroAccion').value.trim(),
                desde: document.getElementById('auditFiltroDesde').value,
                hasta: document.getElementById('auditFiltroHasta').value,
            };
            fetchAudit();
        }

        function limpiarFiltrosAudit() {
            document.getElementById('auditFiltroUsuario').value = '';
            document.getElementById('auditFiltroAccion').value = '';
            document.getElementById('auditFiltroDesde').value = '';
            document.getElementById('auditFiltroHasta').value = '';
            auditState.page = 1;
            auditState.filtros = {};
            fetchAudit();
        }

        async function fetchAudit() {
            const params = new URLSearchParams();
            params.set('page', auditState.page);
            params.set('limit', auditState.limit);
            for (const [k, v] of Object.entries(auditState.filtros)) {
                if (v !== '' && v !== null && v !== undefined) params.set(k, v);
            }

            const tbody = document.getElementById('auditBody');
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">Cargando...</td></tr>';

            try {
                const data = await apiFetch('../backend/log_acciones.php?' + params.toString());
                renderAudit(data);
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-12 text-center text-rose-500">${escapeHtml(err.message)}</td></tr>`;
            }
        }

        function renderAudit(data) {
            const tbody = document.getElementById('auditBody');
            if (!data.logs || data.logs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">No hay registros que coincidan con los filtros.</td></tr>';
                document.getElementById('auditPagination').innerHTML = '';
                return;
            }

            tbody.innerHTML = data.logs.map(l => {
                const badge = ACCION_BADGE[l.accion] || 'bg-slate-500/10 text-slate-400';
                const usuario = l.usuario ?
                    `<span class="text-white">${escapeHtml(l.usuario)}</span><span class="text-[10px] text-slate-500 block">${escapeHtml(l.nombre_completo || '')}</span>` :
                    `<span class="text-slate-500 italic">Sistema</span>`;

                let detalle = '—';
                if (l.detalle_json) {
                    const preview = Object.entries(l.detalle_json).slice(0, 2).map(([k, v]) => {
                        const val = typeof v === 'object' ? JSON.stringify(v) : String(v);
                        return `<span class="text-slate-500">${escapeHtml(k)}:</span> ${escapeHtml(val.length > 30 ? val.slice(0, 30) + '…' : val)}`;
                    }).join(' · ');
                    detalle = `<div class="text-xs">${preview}</div>
                <details class="mt-1">
                    <summary class="text-[10px] text-blue-500 cursor-pointer hover:text-blue-400">ver JSON completo</summary>
                    <pre class="mt-2 p-2 bg-[#1e293b] rounded text-[10px] text-slate-300 overflow-x-auto max-w-md">${escapeHtml(JSON.stringify(l.detalle_json, null, 2))}</pre>
                </details>`;
                } else if (l.detalle) {
                    detalle = `<span class="text-xs text-slate-400">${escapeHtml(l.detalle)}</span>`;
                }

                return `<tr class="hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-3 text-xs text-slate-500 font-mono">${escapeHtml(String(l.id))}</td>
            <td class="px-6 py-3 text-xs text-slate-300 font-mono whitespace-nowrap">${escapeHtml(l.creado_en)}</td>
            <td class="px-6 py-3 text-xs">${usuario}</td>
            <td class="px-6 py-3">
                <span class="px-2 py-1 rounded text-[10px] font-bold ${badge}">${escapeHtml(l.accion)}</span>
            </td>
            <td class="px-6 py-3 text-xs text-slate-400 max-w-md">${detalle}</td>
            <td class="px-6 py-3 text-[10px] text-slate-500 font-mono">${escapeHtml(l.ip || '—')}</td>
        </tr>`;
            }).join('');

            const pag = document.getElementById('auditPagination');
            const prevDisabled = data.page <= 1;
            const nextDisabled = data.page >= data.total_pages;
            pag.innerHTML = `
        <div>Mostrando página <b class="text-white">${escapeHtml(String(data.page))}</b> de <b class="text-white">${escapeHtml(String(data.total_pages || 1))}</b> · <b class="text-white">${escapeHtml(String(data.total))}</b> registros</div>
        <div class="flex gap-2">
            <button onclick="cambiarPaginaAudit(-1)" ${prevDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-700 ${prevDisabled ? 'opacity-30 cursor-not-allowed' : 'hover:bg-slate-800 hover:text-white'}">← Anterior</button>
            <button onclick="cambiarPaginaAudit(1)" ${nextDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-700 ${nextDisabled ? 'opacity-30 cursor-not-allowed' : 'hover:bg-slate-800 hover:text-white'}">Siguiente →</button>
        </div>`;
        }

        function cambiarPaginaAudit(delta) {
            auditState.page = Math.max(1, auditState.page + delta);
            fetchAudit();
        }
    </script>
</body>

</html>